# Copilot instructions

**[AGENTS.md](../AGENTS.md) is the authoritative set of conventions for this repo. Read it before writing code.**

This file exists only so that GitHub Copilot (IDE chat and the cloud coding agent) is pointed at it. Don't duplicate conventions here — add them to [AGENTS.md](../AGENTS.md).

The non-negotiables, in short:

1. **WordPress VIP compatibility is paramount.** New and modified code passes the `WordPress-VIP-Go` PHPCS ruleset with no `phpcs:ignore` exceptions.
2. **WordPress coding standards for both PHP and JavaScript** — no exceptions.
3. **Keep documentation minimal — comments explain *why*, never *how*.** Default to no comment. One line per inline comment; docblocks are a one-line summary and don't restate a typed signature. Anything needing a detailed explanation goes in a [doc/](../doc/) markdown file, not a comment header. Over-documenting is a defect, not diligence. Full rules: [AGENTS.md § Documentation](../AGENTS.md#documentation).
4. **Every PR is listed in the changelog before it merges into `main`.** One bullet in the current unreleased version block of [readme.txt](../readme.txt) — not `changelog.txt`, which holds released versions only. The PR link plus a one-line, user-facing title; the linked PR is the complete detail. Add an indented sub-bullet only to flag a breaking change, migration step, changed default or security fix. Full rules: [AGENTS.md § rule 5](../AGENTS.md#5-every-pr-gets-one-changelog-entry-before-it-merges).

Also in [AGENTS.md](../AGENTS.md): file/class structure, fully-qualified class references (no `use` imports), autoloading, test commands, and the Cypress `@group` / `@covers` convention (**never run the full Cypress suite** — it takes 20+ minutes).
