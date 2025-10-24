# PHPUnit Test Coverage Improvement Plan

**Current Coverage:** 86.57%
**Target Coverage:** 85%
**Status:** ✅ Target Achieved

---

## 🎯 Current Status

Target exceeded! Coverage is at **86.57%**, surpassing the 85% goal.

### Future Improvements (Optional)

If pursuing 90%+ coverage, focus on:
- Core player rendering logic
- API client edge cases
- Site health checks
- Updater/migration paths

## 📊 Coverage Commands

```bash
# Run tests with coverage
yarn test:phpunit

# Check coverage percentage
composer test:coverage-check

# View HTML report
open tests/phpunit/_report/index.html
```

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

## 📝 Testing Philosophy

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

## 📚 Resources

### Useful Commands

```bash
# Run only Settings tests
./vendor/bin/phpunit --filter Settings

# Run specific test file
./vendor/bin/phpunit tests/phpunit/Settings/Fields/PlayerColors/PlayerColorsTest.php

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

**Last Updated:** 2025-10-24
