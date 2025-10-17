# Rector Setup & Usage Guide

## What is Rector?

Rector is an automated PHP refactoring tool that can:
- Remove redundant PHPDoc tags when native types exist
- Modernize PHP code automatically
- Fix code quality issues
- Upgrade code to newer PHP versions

**In our case:** We're using it to clean up redundant PHPDoc when we have native type hints.

---

## Installation

### Step 1: Install Rector via Composer

```bash
yarn composer require rector/rector --dev
```

This uses the wp-env PHP environment (via the yarn script we set up).

**Expected output:**
```
Using version ^1.0 for rector/rector
./composer.json has been updated
Running composer update rector/rector
...
```

---

## Configuration

### The Config File: `rector.php`

I've created `rector.php` in your project root with these rules:

```php
<?php
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',           // Process all files in src/
        __DIR__ . '/uninstall.php', // Process root PHP files
    ])
    ->withSkip([
        __DIR__ . '/tests',   // Skip test files
        __DIR__ . '/vendor',  // Skip dependencies
    ])
    ->withRules([
        RemoveUselessParamTagRector::class,   // Remove @param when redundant
        RemoveUselessReturnTagRector::class,  // Remove @return when redundant
        RemoveUselessVarTagRector::class,     // Remove @var when obvious
    ]);
```

**What it does:**
- ✅ Removes `@param int $id` when signature has `int $id`
- ✅ Removes `@return bool` when signature has `: bool`
- ✅ Keeps complex types like `@param int[]` (PHP can't express this)
- ✅ Keeps descriptions and `@since` tags
- ✅ Keeps array shapes like `@return array{id: int, name: string}`

---

## Yarn Scripts (Already Added to package.json)

### 1. Preview Changes (Dry Run)
```bash
yarn rector
```

**What it does:** Shows what would be changed WITHOUT modifying files.

**Output example:**
```
[OK] Rector is done! 42 files would be changed.

-------- src/Core/ApiClient.php --------
     Old: @param int $postId WordPress Post ID
     New: (removed - redundant with native type)
```

### 2. Apply Changes
```bash
yarn rector:fix
```

**What it does:** Actually modifies your files.

**⚠️ Always review changes before committing!**

### 3. Apply with Debug Output
```bash
yarn rector:fix:diff
```

**What it does:** Shows detailed diff of each change as it applies.

---

## Step-by-Step Usage

### First Time Setup

1. **Install Rector:**
   ```bash
   yarn composer require rector/rector --dev
   ```

2. **Preview what will change:**
   ```bash
   yarn rector
   ```

3. **Review the output carefully** - Check if changes make sense

4. **Apply changes to a single file first (test):**
   ```bash
   yarn rector:fix src/Core/Response.php
   ```

5. **Check the diff:**
   ```bash
   git diff src/Core/Response.php
   ```

6. **If happy, apply to all files:**
   ```bash
   yarn rector:fix
   ```

7. **Run tests to verify nothing broke:**
   ```bash
   yarn test:phpunit
   ```

8. **Review and commit:**
   ```bash
   git add -p  # Review each change
   git commit -m "refactor: remove redundant PHPDoc tags"
   ```

---

## Example: Before & After

### Before Rector
```php
/**
 * POST /projects/:id/content.
 *
 * @since 3.0.0
 * @since 5.2.0 Make static.
 *
 * @param int $postId WordPress Post ID
 *
 * @return mixed JSON-decoded response body
 */
public static function createAudio(int $postId): array|null|false
{
    // ...
}
```

### After Rector
```php
/**
 * POST /projects/:id/content.
 *
 * @since 3.0.0
 * @since 5.2.0 Make static.
 *
 * @return array|null|false JSON-decoded response body
 */
public static function createAudio(int $postId): array|null|false
{
    // ...
}
```

**What changed:**
- ❌ Removed: `@param int $postId` (redundant - already in signature)
- ✅ Kept: `@return` (adds value - explains JSON-decoded)
- ✅ Kept: `@since` tags (historical context)
- ✅ Kept: Description

---

## What Rector WON'T Remove (Safe!)

### ✅ Complex Array Types
```php
/**
 * @param int[] $postIds Array of post IDs
 * @return array{success: int[], failed: int[]}
 */
public function batch(array $postIds): array
```
**Why:** PHP can't express `int[]` natively - PHPDoc adds value

### ✅ Detailed Descriptions
```php
/**
 * Get the Content ID for a WordPress Post.
 *
 * Over time there have been various approaches to storing the Content ID.
 * This function tries each approach in reverse-date order.
 *
 * @since 3.0.0
 * @since 3.5.0 Moved from Core\Utils
 *
 * @return string|int Content ID (string for UUID, int when fallback=true)
 */
public static function getContentId(int $postId, bool $fallback = false): string|int|false
```
**Why:** Description adds context beyond the type

### ✅ Array Shapes
```php
/**
 * @return array{id: string, status: string, created_at: string}|false
 */
public function getContent(): array|false
```
**Why:** Describes the array structure

### ✅ Generic Types
```php
/**
 * @param array<string, mixed> $settings
 */
public function configure(array $settings): void
```
**Why:** Specifies array keys and value types

---

## What Rector WILL Remove (Redundant)

### ❌ Simple Scalar Params
```php
/**
 * @param int $id      ← REMOVED (redundant)
 * @param string $name ← REMOVED (redundant)
 */
public function test(int $id, string $name): void
```

### ❌ Simple Scalar Returns
```php
/**
 * @return bool  ← REMOVED (redundant)
 */
public function isEnabled(): bool
```

### ❌ Obvious Variables
```php
/** @var bool $enabled */  ← REMOVED (type is obvious)
$enabled = true;
```

---

## Customizing Rector

### Only Process Specific Files
Edit `rector.php`:
```php
->withPaths([
    __DIR__ . '/src/Core/ApiClient.php',  // Only this file
])
```

### Skip Specific Rules
Edit `rector.php`:
```php
->withSkip([
    RemoveUselessReturnTagRector::class => [
        __DIR__ . '/src/Core/ApiClient.php',  // Keep @return in this file
    ],
])
```

### More Conservative (Keep More PHPDoc)
Edit `rector.php` and remove rules:
```php
->withRules([
    RemoveUselessParamTagRector::class,  // Only remove @param, keep @return
])
```

---

## Common Issues & Solutions

### Issue: "Rector not found"
**Solution:** Install it first:
```bash
yarn composer require rector/rector --dev
```

### Issue: "Files not being processed"
**Solution:** Check the paths in `rector.php` match your structure:
```bash
# Check what files Rector sees:
wp-env run cli --env-cwd=/var/www/html/wp-content/plugins/speechkit ./vendor/bin/rector process --dry-run --debug
```

### Issue: "Too many changes, overwhelming"
**Solution:** Process one directory at a time:
```bash
# Edit rector.php:
->withPaths([
    __DIR__ . '/src/Core',  // Just Core directory first
])
```

### Issue: "Changes broke tests"
**Solution:** Rector is imperfect. Review each file and revert bad changes:
```bash
git checkout src/problematic/File.php
```

---

## Integration with Git Workflow

### Safe Workflow
```bash
# 1. Create a branch
git checkout -b refactor/remove-redundant-phpdoc

# 2. Preview changes
yarn rector

# 3. Apply changes
yarn rector:fix

# 4. Review changes file-by-file
git diff

# 5. Run tests
yarn test:phpunit

# 6. Stage changes selectively
git add -p

# 7. Commit
git commit -m "refactor: remove redundant PHPDoc tags

- Remove @param when types match native signature
- Remove @return for simple scalar types
- Keep complex array types and descriptions
- All tests passing"

# 8. Push and create PR
git push origin refactor/remove-redundant-phpdoc
```

### Add to CI/CD (Optional)
Add to `.github/workflows/php.yml`:
```yaml
- name: Check for redundant PHPDoc
  run: |
    yarn rector --dry-run
    if [ $? -ne 0 ]; then
      echo "❌ Found redundant PHPDoc. Run 'yarn rector:fix' locally."
      exit 1
    fi
```

---

## Expected Results for Your Codebase

Based on the files we just updated, Rector will likely:

### ApiClient.php (~15 removals)
- Remove `@param int $postId` × ~15 times
- Remove `@param array $settings` × 3 times
- Keep `@return array|null|false` descriptions (add value)

### PostMetaUtils.php (~10 removals)
- Remove `@param int $postId` × ~14 times
- Keep `@param string $name` when it explains purpose
- Keep all `@return` (they explain mixed types)

### Settings.php (~8 removals)
- Remove simple `@param` tags
- Remove `@return void` (redundant)
- Keep array type descriptions

### Total Expected Changes
- ~50-100 redundant lines removed
- ~40 files touched
- 0 functionality changes (pure cleanup)
- Tests still passing ✅

---

## Alternative: Manual Cleanup (If Rector Too Aggressive)

If you prefer more control, you can manually clean up using this pattern:

```bash
# Find all redundant @param int
grep -r "@param int \$" src/

# Then manually remove them file by file
```

---

## Rector Documentation

- Official Docs: https://getrector.com/documentation
- Rules List: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
- Community Support: https://github.com/rectorphp/rector/discussions

---

## Quick Reference

| Command | What it does | Safe? |
|---------|-------------|-------|
| `yarn rector` | Preview changes only | ✅ 100% safe |
| `yarn rector:fix` | Apply all changes | ⚠️ Review after |
| `yarn rector:fix:diff` | Apply with detailed output | ⚠️ Review after |
| `yarn rector src/Core/File.php` | Process one file | ⚠️ Review after |

---

## My Recommendation

**Start conservative:**

1. **Day 1:** Run `yarn rector` and review the preview
2. **Day 2:** Apply to just one file: `yarn rector:fix src/Core/Response.php`
3. **Day 3:** If happy, apply to all Core files
4. **Day 4:** Apply to all files, review, test, commit

**This lets you:**
- ✅ See results before committing
- ✅ Learn what Rector does
- ✅ Catch any issues early
- ✅ Build confidence in the tool

---

**Ready to try?** Run: `yarn composer require rector/rector --dev`
