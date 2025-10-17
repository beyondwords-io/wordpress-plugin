# PHPUnit Test Coverage Improvement Plan

**Date Started:** 2025-10-17
**Current Coverage:** 64.42% (was 60.57%)
**Target Coverage:** 85%
**Status:** 🔄 In Progress

---

## 🚀 How to Continue

To resume improving test coverage, use this prompt:

```
Continue with doc/TEST_COVERAGE_IMPROVEMENT.md
```

**Next Recommended Task:** Expand tests for Sync.php (2nd highest impact)

---

## 📊 Current Status

### Coverage Summary
- **Current:** 64.42% (as of 2025-10-17)
- **Previous:** 60.57%
- **Improvement:** +3.85 percentage points
- **Target:** 85%
- **Gap:** 20.58 percentage points
- **Threshold:** 55% (currently passing ✅)

### Coverage Commands
```bash
# Run tests with coverage
yarn test:phpunit

# Check coverage percentage
composer test:coverage-check

# View HTML report
open tests/phpunit/_report/index.html
```

---

## 🎯 Priority Files - Sorted by Impact

### Calculation Method
Files prioritized by: (file_size × uncovered_percentage) = impact score

Higher score = higher priority for maximum coverage improvement.

### 🔴 HIGH PRIORITY - Large Files, Low Coverage

| Priority | File | Coverage | Uncovered Lines | File Size | Impact Score | Test File Status |
|----------|------|----------|-----------------|-----------|--------------|------------------|
| 1 | `PlayerColors.php` | ~~6.1%~~ **90%+** | ~~155/165~~ ~10/165 | 344 lines | ~~**53,330**~~ **3,440** | ✅ Complete |
| 2 | `Settings/Sync.php` | 8.9% | 113/124 | 339 lines | **38,307** | ⏭️ **Next** |
| 3 | `WidgetStyle.php` | 4.6% | 62/65 | 148 lines | **9,176** | ❌ Missing |
| 4 | `TextHighlighting.php` | 5.4% | 53/56 | - | **2,968** | ❌ Missing |
| 5 | `WidgetPosition.php` | 5.9% | 48/51 | - | **2,448** | ❌ Missing |

### 🟡 MEDIUM PRIORITY - Small Files, Zero Coverage

| Priority | File | Coverage | Uncovered Lines | Test File Status |
|----------|------|----------|-----------------|------------------|
| 6 | `AutoPublish.php` | 0.0% | 38/38 | ❌ Missing |
| 7 | `IncludeTitle.php` | 0.0% | 34/34 | ❌ Missing |
| 8 | `Content/Content.php` | 0.0% | 20/20 | ❌ Missing |
| 9 | `Voice/Voice.php` | 0.0% | 9/9 | ❌ Missing |

### 🟢 LOW PRIORITY - Partial Coverage

| File | Coverage | Status |
|------|----------|--------|
| `Language.php` | 6.0% | Could improve |
| `PlaybackControls.php` | 7.1% | Could improve |
| `CallToAction.php` | 11.5% | Could improve |
| `BodyVoice.php` | 6.8% | Could improve |
| `TitleVoice.php` | 6.8% | Could improve |

---

## 📈 Estimated Impact Analysis

### If We Complete Top 5 Priority Files

| File | Current Coverage | Lines to Cover | Estimated New Coverage |
|------|------------------|----------------|------------------------|
| PlayerColors.php | 6.1% | ~155 | → 90% |
| Sync.php | 8.9% | ~113 | → 75% |
| WidgetStyle.php | 4.6% | ~62 | → 90% |
| TextHighlighting.php | 5.4% | ~53 | → 90% |
| WidgetPosition.php | 5.9% | ~48 | → 90% |

**Total New Covered Lines:** ~431 statements

**Projected Overall Coverage Increase:**
- From: 60.57%
- To: **~67-68%** (after top 5 files)

**To reach 85% target:**
- Need to cover an additional ~15-17 percentage points
- Requires testing ~10-15 more medium-priority files

---

## 🗺️ Roadmap to 85% Coverage

### Phase 1: High Impact Files (Target: 68%)
**Effort:** 2-3 days
**Expected Gain:** +7-8 percentage points

- [x] **PlayerColors.php** - ✅ Complete (36 tests, 102 assertions, +3.85% coverage)
- [ ] Sync.php - Expand existing tests (integration tests needed)
- [ ] WidgetStyle.php - Create test file
- [ ] TextHighlighting.php - Create test file
- [ ] WidgetPosition.php - Create test file

### Phase 2: Zero Coverage Files (Target: 75%)
**Effort:** 2-3 days
**Expected Gain:** +7-8 percentage points

- [ ] AutoPublish.php
- [ ] IncludeTitle.php
- [ ] Content/Content.php
- [ ] Voice/Voice.php
- [ ] BodyVoice.php
- [ ] TitleVoice.php
- [ ] PlaybackControls.php
- [ ] Language.php

### Phase 3: Partial Coverage Improvements (Target: 85%)
**Effort:** 2-3 days
**Expected Gain:** +10 percentage points

- [ ] Improve existing test files
- [ ] Add edge case tests
- [ ] Add integration tests for complex workflows
- [ ] Test error handling paths

### Phase 4: Polish (Target: 90%+)
**Effort:** 1-2 days (optional)
**Expected Gain:** +5 percentage points

- [ ] Cover remaining edge cases
- [ ] Add mutation testing
- [ ] Test rarely-used code paths

---

## 📝 Task Details

### Task 1: PlayerColors.php Test Suite ✅ COMPLETE

**File:** `src/Component/Settings/Fields/PlayerColors/PlayerColors.php`
**Test File:** `tests/phpunit/Settings/Fields/PlayerColors/PlayerColorsTest.php` ✅
**Initial Coverage:** 6.1% (10/165 statements)
**Final Coverage:** ~90%+ (estimated 155+/165 statements)
**Tests Created:** 36 tests with 102 assertions
**Coverage Impact:** +3.85 percentage points overall

#### What to Test

1. **init() method**
   - Verify WordPress hooks are registered
   - Check `admin_init` actions
   - Verify `pre_update_option` hooks for all 4 theme options

2. **addPlayerThemeSetting() method**
   - Registers setting correctly
   - Sanitization callback works

3. **addPlayerColorsSetting() method**
   - Registers all 3 color settings (light, dark, video)
   - Sanitization callbacks work

4. **render() method**
   - HTML output contains expected elements
   - Default values displayed correctly
   - Help text rendered

5. **sanitizePlayerTheme() method**
   - Valid values pass through: 'light', 'dark', 'video'
   - Invalid values rejected
   - Empty strings handled

6. **sanitizePlayerColors() method**
   - Valid color arrays accepted
   - Invalid colors rejected
   - Empty arrays handled
   - Partial arrays merged with defaults

7. **colorInput() helper**
   - Renders color input HTML
   - Handles empty values
   - Escapes HTML properly

#### Test Structure Example

```php
<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerColors\PlayerColors;

class PlayerColorsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Clean up options
        delete_option(PlayerColors::OPTION_NAME_THEME);
        delete_option(PlayerColors::OPTION_NAME_LIGHT_THEME);
        delete_option(PlayerColors::OPTION_NAME_DARK_THEME);
        delete_option(PlayerColors::OPTION_NAME_VIDEO_THEME);
    }

    /**
     * @test
     */
    public function init_registers_hooks()
    {
        PlayerColors::init();

        $this->assertEquals(10, has_action('admin_init', [PlayerColors::class, 'addPlayerThemeSetting']));
        $this->assertEquals(10, has_action('admin_init', [PlayerColors::class, 'addPlayerColorsSetting']));
    }

    /**
     * @test
     * @dataProvider validThemeProvider
     */
    public function sanitizePlayerTheme_accepts_valid_themes($theme)
    {
        $result = PlayerColors::sanitizePlayerTheme($theme);
        $this->assertSame($theme, $result);
    }

    public function validThemeProvider()
    {
        return [
            'light' => ['light'],
            'dark' => ['dark'],
            'video' => ['video'],
        ];
    }

    /**
     * @test
     */
    public function sanitizePlayerTheme_rejects_invalid_theme()
    {
        $result = PlayerColors::sanitizePlayerTheme('invalid');
        $this->assertSame('', $result);
    }

    // ... more tests
}
```

**Estimated Effort:** 3-4 hours
**Impact:** +155 covered statements

---

### Task 2: Expand Sync.php Tests

**File:** `src/Component/Settings/Sync.php`
**Test File:** `tests/phpunit/Settings/SyncTest.php` (expand)
**Current Coverage:** 8.9% (11/124 statements)
**Target Coverage:** 75%+

#### Current Test Analysis

Existing test file needs expansion - currently only tests basic initialization.

#### What to Test

1. **syncOptionToDashboard() method**
   - Successful sync with valid credentials
   - Failed sync with invalid credentials
   - Handle various option types
   - API request structure validation

2. **syncToDashboard() method**
   - Full settings sync workflow
   - Player settings sync
   - Voice settings sync
   - Project settings sync
   - Error handling

3. **updateOptionsFromResponses() method**
   - Parse API responses correctly
   - Update WordPress options
   - Handle partial responses
   - Handle API errors

4. **Integration Tests Required**
   - Mock API responses
   - Test with real WordPress options
   - Verify WordPress hooks fire

**Complexity:** HIGH - Requires mocking API calls and WordPress environment

**Estimated Effort:** 4-6 hours
**Impact:** +113 covered statements

---

### Task 3: WidgetStyle.php Test Suite

**File:** `src/Component/Settings/Fields/WidgetStyle/WidgetStyle.php`
**Test File:** `tests/phpunit/Settings/Fields/WidgetStyle/WidgetStyleTest.php` (create)
**Current Coverage:** 4.6% (3/65 statements)
**Target Coverage:** 90%+

#### What to Test

1. **init() method** - Hook registration
2. **addSetting() method** - Setting registration
3. **render() method** - HTML output
4. **sanitize() method** - Valid widget styles: 'small', 'standard', 'large'

**Estimated Effort:** 2-3 hours
**Impact:** +62 covered statements

---

### Task 4: TextHighlighting.php Test Suite

**File:** `src/Component/Settings/Fields/TextHighlighting/TextHighlighting.php`
**Test File:** `tests/phpunit/Settings/Fields/TextHighlighting/TextHighlightingTest.php` (create)
**Current Coverage:** 5.4% (3/56 statements)
**Target Coverage:** 90%+

**Estimated Effort:** 2-3 hours
**Impact:** +53 covered statements

---

### Task 5: WidgetPosition.php Test Suite

**File:** `src/Component/Settings/Fields/WidgetPosition/WidgetPosition.php`
**Test File:** `tests/phpunit/Settings/Fields/WidgetPosition/WidgetPositionTest.php` (create)
**Current Coverage:** 5.9% (3/51 statements)
**Target Coverage:** 90%+

**Estimated Effort:** 2-3 hours
**Impact:** +48 covered statements

---

## 🧪 Testing Patterns for Settings Fields

Most Settings Field classes follow a similar pattern. Use this template:

### Standard Settings Field Structure

```php
class SomeFieldTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        delete_option(SomeField::OPTION_NAME);
    }

    /** @test */
    public function init_registers_hooks() { }

    /** @test */
    public function addSetting_registers_correctly() { }

    /** @test */
    public function render_outputs_html() {
        // Use captureOutput() helper
        $html = $this->captureOutput(function () {
            SomeField::render();
        });

        $this->assertStringContainsString('expected-id', $html);
    }

    /** @test */
    public function sanitize_accepts_valid_values() { }

    /** @test */
    public function sanitize_rejects_invalid_values() { }
}
```

### Common Test Scenarios

1. **Hook Registration**
   ```php
   SomeField::init();
   $this->assertEquals(10, has_action('admin_init', [SomeField::class, 'addSetting']));
   ```

2. **Setting Registration**
   ```php
   global $wp_registered_settings;
   SomeField::addSetting();
   $this->assertArrayHasKey('option_name', $wp_registered_settings);
   ```

3. **HTML Rendering**
   ```php
   $html = $this->captureOutput(fn() => SomeField::render());
   $crawler = new Crawler($html);
   $this->assertCount(1, $crawler->filter('input#some-field'));
   ```

4. **Sanitization**
   ```php
   $result = SomeField::sanitize('valid-value');
   $this->assertSame('valid-value', $result);
   ```

---

## 📊 Progress Tracking

### Completed ✅

**Phase 1 Completed: 1/5**
- [x] PlayerColors.php - 36 tests, 102 assertions

### In Progress 🔄

**Current Task:** None - Ready for next priority file

### Statistics

- **Tests Added:** 36 tests with 102 assertions
- **Statements Covered:** ~155 statements (PlayerColors.php)
- **Coverage Improvement:** +3.85 percentage points
- **Current Coverage:** 64.42%
- **Progress to Target:** 18.7% complete (4.85 of 25.43 points gained)

---

## 🎯 Success Metrics

### Coverage Milestones

- [x] **55%** - Current threshold (passing)
- [x] **60.57%** - Initial coverage
- [x] **64.42%** - Current coverage (+3.85 from PlayerColors.php)
- [ ] **65%** - Phase 1 milestone (0.58 points away!)
- [ ] **70%** - Phase 1 complete
- [ ] **75%** - Phase 2 complete
- [ ] **80%** - Near target
- [ ] **85%** - TARGET REACHED 🎉
- [ ] **90%** - Stretch goal

### Quality Metrics

Beyond just coverage percentage, aim for:
- ✅ All public methods have at least one test
- ✅ Happy path AND error paths tested
- ✅ Integration tests for complex workflows
- ✅ Edge cases covered
- ✅ No skipped or incomplete tests

---

## 📚 Resources

### Useful Commands

```bash
# Run only Settings tests
./vendor/bin/phpunit --filter Settings

# Run specific test file
./vendor/bin/phpunit tests/phpunit/Settings/Fields/PlayerColors/PlayerColorsTest.php

# Check coverage for specific file
./vendor/bin/phpunit --coverage-filter src/Component/Settings/Fields/PlayerColors/PlayerColors.php

# Generate fresh coverage report
yarn test:phpunit && open tests/phpunit/_report/index.html
```

### Test Helpers

- `$this->captureOutput()` - Capture HTML output without console spam
- `TestCase::factory()` - WordPress test factories for posts, users, etc.
- `Symfony\Component\DomCrawler\Crawler` - Parse and query HTML
- `update_option()` / `delete_option()` - Manage WordPress options in tests

### WordPress Test Framework Docs

- [WordPress PHPUnit Tests](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [WP_UnitTestCase Reference](https://developer.wordpress.org/reference/classes/wp_unittestcase/)

---

## 🔍 Coverage Analysis Tools

### View Coverage Report

```bash
# Generate and open HTML report
yarn test:phpunit
open tests/phpunit/_report/index.html
```

### Analyze Specific Directory

```bash
# Check Settings coverage
open tests/phpunit/_report/Component/Settings/index.html
```

### Command-Line Coverage Check

```bash
# Get exact percentage
composer test:coverage-check-percentage

# Check against threshold
composer test:coverage-check
```

---

## 📝 Notes

### 2025-10-17 - Initial Analysis

- Current coverage: 60.57%
- Identified 14 Settings files with low/zero coverage
- Prioritized by file size × uncovered percentage
- PlayerColors.php is highest impact target (155 uncovered statements)
- Estimated 7-10 days to reach 85% coverage target

### 2025-10-17 - PlayerColors.php Complete ✅

**Created:** `tests/phpunit/Settings/Fields/PlayerColors/PlayerColorsTest.php`

**Test Coverage Created:**
- 36 tests with 102 assertions
- All tests passing ✅
- Coverage increased from 60.57% → 64.42% (+3.85 points)

**Modern PHP Testing Patterns Implemented:**
1. **Strict typing** - `declare(strict_types=1);` throughout
2. **Data providers** - Used `@dataProvider` for parameterized tests (9 color validation scenarios)
3. **Descriptive test names** - Clear method names describing what's tested
4. **Comprehensive coverage** - Tests for happy paths, error paths, edge cases, and integration
5. **WordPress-specific patterns** - Hook testing with `has_action()`, option management
6. **HTML testing** - Using `captureOutput()` helper and Symfony DomCrawler
7. **Type hints** - Full return type declarations (`: void`, `: array`, `: string`)

**Test Categories:**
- Hook registration (6 tests)
- Setting registration (5 tests)
- HTML rendering (4 tests)
- Color sanitization (11 tests with data providers)
- Integration tests (3 tests)
- Edge cases (7 tests for null/empty/missing values)

**Key Patterns That Can Be Reused:**
- Data providers for validation testing
- `captureOutput()` for HTML rendering tests
- Symfony Crawler for HTML assertions
- WordPress globals testing (`$wp_registered_settings`, `$wp_settings_fields`)
- Integration tests that exercise full setting→save→retrieve workflow

**Next Steps:** Apply these patterns to remaining Settings field classes

### Testing Philosophy

Focus on:
1. **High-impact files first** - Largest files with lowest coverage
2. **Real-world scenarios** - Test how users actually interact with settings
3. **Error paths** - Not just happy paths
4. **Integration** - Settings interact with WordPress and the API

Avoid:
- ❌ Testing WordPress core functionality
- ❌ Over-mocking (test real behavior when possible)
- ❌ Brittle tests (don't test exact HTML structure, test behavior)

---

**Last Updated:** 2025-10-17
**Next Review:** After completing Phase 1 (target: 68% coverage)
