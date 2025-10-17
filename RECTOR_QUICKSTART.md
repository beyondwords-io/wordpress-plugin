# Rector Quick Start Guide

## 30-Second Version

```bash
# 1. Install Rector
yarn composer require rector/rector --dev

# 2. See what would change (safe - no modifications)
yarn rector

# 3. Apply changes
yarn rector:fix

# 4. Run tests
yarn test:phpunit

# 5. Review & commit
git diff
git add -A
git commit -m "refactor: remove redundant PHPDoc tags"
```

---

## What Will Happen

### Before
```php
/**
 * @param int $postId WordPress Post ID
 * @return bool
 */
public static function hasContent(int $postId): bool
```

### After
```php
/**
 * @return bool
 */
public static function hasContent(int $postId): bool
```

**Removed:** `@param int $postId` (redundant - already in signature)
**Kept:** `@return bool` (can add description later)

---

## Commands Added to package.json

| Command | Purpose | Safe? |
|---------|---------|-------|
| `yarn rector` | Preview only (no changes) | ✅ Yes |
| `yarn rector:fix` | Apply changes | ⚠️ Review after |
| `yarn rector:fix:diff` | Apply with detailed diff | ⚠️ Review after |

---

## What Gets Removed vs Kept

### ✅ KEPT (Valuable PHPDoc)
```php
/**
 * @param int[] $postIds Array of post IDs          ← KEPT (PHP can't do int[])
 * @return array{id: int, name: string}|false       ← KEPT (array shape)
 */
```

### ❌ REMOVED (Redundant PHPDoc)
```php
/**
 * @param int $id      ← REMOVED (same as native type)
 * @param string $name ← REMOVED (same as native type)
 * @return bool        ← REMOVED (same as native type)
 */
public function example(int $id, string $name): bool
```

---

## Expected Impact

Based on your codebase:
- **~50-100 lines removed** across all files
- **~40 files touched**
- **0 functionality changes** (pure cleanup)
- **Tests still passing** ✅

---

## Files Created

1. **[rector.php](rector.php)** - Configuration file (customize as needed)
2. **[RECTOR_GUIDE.md](RECTOR_GUIDE.md)** - Full documentation
3. **package.json** - Added 3 yarn scripts

---

## First Run

```bash
# Install
yarn composer require rector/rector --dev

# Preview what would change
yarn rector

# Review the output, then if happy:
yarn rector:fix

# Verify nothing broke
yarn test:phpunit
```

---

## When to Use Rector

**Good for:**
- ✅ Cleaning up redundant PHPDoc after adding native types
- ✅ Upgrading code to newer PHP versions
- ✅ Removing dead code
- ✅ Modernizing old codebases

**Not needed for:**
- ❌ New code you write (just don't add redundant PHPDoc)
- ❌ Files that already have clean, non-redundant PHPDoc

---

## Safety Tips

1. **Always run dry-run first:** `yarn rector`
2. **Review changes before committing:** `git diff`
3. **Run tests after:** `yarn test:phpunit`
4. **Process incrementally:** Start with one file, then expand
5. **Use version control:** Easy to revert if needed

---

## Need More Details?

See **[RECTOR_GUIDE.md](RECTOR_GUIDE.md)** for:
- Detailed explanation of each rule
- Customization options
- Troubleshooting
- Integration with CI/CD
- Before/after examples

---

## Alternative: Don't Use Rector

You can also clean up PHPDoc manually:

```bash
# Find redundant @param tags
grep -r "@param int \$" src/

# Then remove them manually
```

**Rector just automates this.**

---

**Ready?** Run: `yarn composer require rector/rector --dev`
