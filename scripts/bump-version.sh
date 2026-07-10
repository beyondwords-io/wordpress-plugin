#!/usr/bin/env bash
#
# bump-version.sh — set the plugin version in every place it lives, in one command.
#
# Usage:
#   scripts/bump-version.sh <version>
#
# Example:
#   scripts/bump-version.sh 7.0.0-beta.1
#   scripts/bump-version.sh 7.0.0
#
# Updates, atomically-ish (all-or-nothing after validation):
#   - speechkit.php       plugin header `Version:` line
#   - speechkit.php       BEYONDWORDS__PLUGIN_VERSION constant
#   - package.json        "version"
#   - package-lock.json   root "version" and packages[""]."version"
#   - readme.txt          "Stable tag:"
#
# It does NOT touch the changelog headings (those are curated by hand) and does
# not create a git commit or tag — run it, review the diff, then commit.

set -euo pipefail

# --- locate the repo root relative to this script, so it works from any cwd ---
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SPEECHKIT="speechkit.php"
PACKAGE_JSON="package.json"
PACKAGE_LOCK="package-lock.json"
README="readme.txt"

usage() {
  cat >&2 <<EOF
Usage: scripts/bump-version.sh <version>

  <version>  Semver, with optional pre-release/build metadata.
             e.g. 7.0.0  ·  7.0.0-beta.1  ·  7.1.0-rc.2

Sets the version in speechkit.php (header + constant), package.json,
package-lock.json and the readme.txt stable tag. Review the diff, then commit.
EOF
  exit 2
}

die() { echo "bump-version: error: $*" >&2; exit 1; }

# --- args -------------------------------------------------------------------
[ $# -eq 1 ] || usage
NEW="$1"

case "$NEW" in
  -h|--help) usage ;;
esac

# Semver: MAJOR.MINOR.PATCH with optional -prerelease and +build (dot-separated
# alphanumeric identifiers). Rejects a leading "v", trailing spaces, etc.
if ! printf '%s' "$NEW" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z]+(\.[0-9A-Za-z]+)*)?(\+[0-9A-Za-z]+(\.[0-9A-Za-z]+)*)?$'; then
  die "'$NEW' is not a valid semver version (expected e.g. 7.0.0 or 7.0.0-beta.1)"
fi

# --- preflight: required tools and files ------------------------------------
command -v node >/dev/null 2>&1 || die "node is required (used to edit the JSON files safely)"
command -v perl >/dev/null 2>&1 || die "perl is required"
for f in "$SPEECHKIT" "$PACKAGE_JSON" "$PACKAGE_LOCK" "$README"; do
  [ -f "$f" ] || die "expected file not found: $f (is the repo layout as expected?)"
done

# --- current canonical version (from the plugin constant) -------------------
OLD="$(perl -ne "print \$1 if /BEYONDWORDS__PLUGIN_VERSION\x27\s*,\s*\x27([^\x27]*)\x27/" "$SPEECHKIT")"
[ -n "$OLD" ] || die "could not read the current version from $SPEECHKIT"

if [ "$OLD" = "$NEW" ]; then
  echo "bump-version: already at $NEW — nothing to do."
  exit 0
fi

echo "bump-version: $OLD -> $NEW"

# --- edit speechkit.php: header line + constant -----------------------------
NEW="$NEW" perl -i -pe 's{^(\s*\*\s*Version:\s*)\S+}{$1$ENV{NEW}}' "$SPEECHKIT"
NEW="$NEW" perl -i -pe 's{(BEYONDWORDS__PLUGIN_VERSION\x27\s*,\s*\x27)[^\x27]*}{$1$ENV{NEW}}' "$SPEECHKIT"

# --- edit readme.txt: Stable tag --------------------------------------------
NEW="$NEW" perl -i -pe 's{^(Stable tag:\s*)\S+}{$1$ENV{NEW}}' "$README"

# --- edit package.json + package-lock.json via node (targeted, keeps format) -
# Editing the exact JSON paths avoids clobbering any dependency's "version".
NEW="$NEW" node -e '
  const fs = require("fs");
  const v = process.env.NEW;
  const edits = [
    ["package.json", (j) => { j.version = v; }],
    ["package-lock.json", (j) => {
      j.version = v;
      if (j.packages && j.packages[""]) j.packages[""].version = v;
    }],
  ];
  for (const [path, apply] of edits) {
    const json = JSON.parse(fs.readFileSync(path, "utf8"));
    apply(json);
    fs.writeFileSync(path, JSON.stringify(json, null, 2) + "\n");
  }
'

# --- verify every location now reads NEW ------------------------------------
declare -a checks=(
  "speechkit.php (header)|$(perl -ne 'print $1 if /^\s*\*\s*Version:\s*(\S+)/' "$SPEECHKIT")"
  "speechkit.php (constant)|$(perl -ne "print \$1 if /BEYONDWORDS__PLUGIN_VERSION\x27\s*,\s*\x27([^\x27]*)\x27/" "$SPEECHKIT")"
  "package.json|$(node -p "require('$ROOT/package.json').version")"
  "package-lock.json (root)|$(node -p "require('$ROOT/package-lock.json').version")"
  "package-lock.json (packages)|$(node -p "require('$ROOT/package-lock.json').packages[''].version")"
  "readme.txt (Stable tag)|$(perl -ne 'print $1 if /^Stable tag:\s*(\S+)/' "$README")"
)

failed=0
for entry in "${checks[@]}"; do
  label="${entry%%|*}"
  got="${entry#*|}"
  if [ "$got" = "$NEW" ]; then
    printf '  ok   %-32s %s\n' "$label" "$got"
  else
    printf '  FAIL %-32s got "%s" (expected "%s")\n' "$label" "$got" "$NEW" >&2
    failed=1
  fi
done

[ "$failed" -eq 0 ] || die "one or more files did not update — review the diff and re-run"

echo "bump-version: done. Review 'git diff' and commit."
