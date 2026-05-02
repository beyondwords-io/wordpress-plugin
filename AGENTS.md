# AGENTS.md

Conventions for working in the BeyondWords WordPress plugin codebase. These apply to humans and AI agents alike. Keep this file authoritative — update it when conventions change.

## Non-negotiables

1. **WordPress VIP compatibility is paramount.** All new and modified code must pass the `WordPress-VIP-Go` PHPCS ruleset without `phpcs:ignore` exceptions. If a sniff fires, fix the underlying code, not the comment.
2. **WordPress coding standards must be followed for both PHP and JavaScript — no exceptions.** Format new code accordingly; reformat surrounding code when you touch it.

## File structure

New code lives under `src/{feature}/`, with WordPress-style file names:

```
src/
  settings/
    class-settings.php
    class-tabs.php
    class-fields.php
    class-preselect.php
    class-utils.php
  {other-feature}/
    class-{name}.php
```

- One class per file. File name is `class-{kebab-case-name}.php`. Class name is the PascalCase form (e.g. `class-tabs.php` defines `Tabs`).
- Group by feature, not by class type. `Fields` lives next to `Tabs` because they're both part of the settings feature, not in a separate `Fields/` folder.
- The legacy code elsewhere under `src/` follows an older PSR-12-with-deep-namespaces convention (e.g. `src/Component/...`, `src/Core/...`). **When you rewrite a class, move it to a `src/{feature}/class-{name}.php` location.** Don't migrate untouched code.

## Class structure

```php
<?php
declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Tab registration for the BeyondWords settings page.
 */
class Tabs {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register' ) );
	}

	public static function register(): void {
		// ...
	}
}
```

Rules:

- **Namespaces are required.** Top-level namespace is `BeyondWords\`, then the feature (e.g. `BeyondWords\Settings`). Class names stay short.
- **Static methods only.** No instances, no constructors, no DI. State lives in WordPress options/transients/post meta, not in objects.
- **`init()` is the entry point.** It owns all `add_action`/`add_filter` registrations for the class. It must be idempotent.
- **No bottom-of-file `Class::init();` self-call.** Autoloading wouldn't load the file until the class is referenced anyway, so `init()` is invoked explicitly from the plugin bootstrap (`src/Plugin.php`).
- **snake_case for methods, variables, hook names, option keys.** Class names stay PascalCase to match WordPress conventions for class identifiers.
- **PHPDoc blocks on every public method.** Param/return types are required where they aren't obvious from signature.

## Autoloading

Two autoload entries in [composer.json](composer.json) coexist:

```json
"autoload": {
    "psr-4": {
        "Beyondwords\\Wordpress\\": "src/"
    },
    "classmap": ["src/settings/"]
}
```

- **PSR-4** continues to handle the legacy `Beyondwords\Wordpress\…` namespaces under `src/Component/`, `src/Core/`, etc.
- **Classmap** handles the new WordPress-style files (`class-tabs.php` etc.) — PSR-4 can't because file names don't match class names. Add a new classmap entry for each new feature directory, e.g. `"src/{feature}/"`.

Run `composer dump-autoload` after adding, removing, or renaming files in any classmap directory.

The bootstrap in `src/Plugin.php` invokes each class's `init()`:

```php
\BeyondWords\Settings\Settings::init();
\BeyondWords\Settings\Tabs::init();
\BeyondWords\Settings\Fields::init();
```

## PHP standards

- **PHPCS ruleset:** `WordPress-VIP-Go` (via `automattic/vipwpcs`). Configured in [.phpcs.xml](.phpcs.xml). The PHPCS run is the source of truth — fix whatever it complains about.
- **Indentation:** tabs (per WordPress core standard).
- **Yoda conditions:** disabled in the ruleset (matches existing convention). Use whichever reads more naturally.
- **Escaping:** all output must be escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). All input must be sanitised (`sanitize_text_field`, `absint`, `rest_sanitize_boolean`).
- **Nonces:** every form submission and admin-side state change verifies a nonce.
- **Translation:** every user-facing string uses `__()` / `esc_html__()` with the `'speechkit'` text domain.
- **i18n placeholders:** numbered (`%1$s`, `%2$s`) when there's more than one — never positional.
- **Arrays:** short syntax (`array()` and `[]` both accepted by the ruleset; existing code mixes them). Prefer long `array()` in new code under `src/{feature}/` to match WordPress core style.
- **No `extract()`, `eval()`, `query_posts()`, `wp_reset_query()`, or direct `$wpdb` queries** unless there is no WP API equivalent.
- **Caching:** anything that hits the database in a hot path must be cached. VIP fails the build otherwise.

Run before commit:

```bash
npm run phpcs       # WordPress-VIP-Go check
npm run phpcbf      # auto-fix what can be fixed
composer test:phpunit
```

## JavaScript standards

- **ESLint config:** `@wordpress/eslint-plugin` (already in `package.json`). Configured for WordPress JS Coding Standards.
- **Build tooling:** `@wordpress/scripts` (`wp-scripts`). Use `npm run build` / `npm start`.
- **Formatting:** `wp-scripts format` — Prettier with WordPress config.
- **i18n:** `@wordpress/i18n` (`__`, `_x`, `sprintf`) with the `'speechkit'` text domain.
- **No jQuery in new code.** Use vanilla DOM or `@wordpress/element` (React).

Run before commit:

```bash
npm run lint:js
npm run lint:css
npm run format
```

## Package management

- **Use npm.** The lockfile is `package-lock.json`. CI runs `npm ci`. Don't introduce `yarn.lock`, `pnpm-lock.yaml`, or `bun.lockb`.
- For npm scripts that themselves accept arguments (notably the `composer` passthrough), forward args with `--`: `npm run composer -- test:phpunit`.

## Deprecating settings

When removing a setting:

1. Delete the field/tab classes and their tests.
2. Move the option key from the `current` array to the `deprecated` array in [src/Core/CoreUtils.php](src/Core/CoreUtils.php) `getOptions()`. The uninstaller cleans up deprecated keys, so users upgrading lose stale data on plugin removal.
3. Delete every `get_option()` call and any code paths gated on it.
4. Replace runtime behaviour with one of: a hard-coded default, a `apply_filters()` hook, or per-post meta (whichever the spec calls for).
5. Add a version-gated migration to [src/Core/Updater.php](src/Core/Updater.php) if existing data needs to be transformed.

## Tests

- **PHPUnit** under `tests/phpunit/` — keep the existing folder layout for now (`Settings/Fields/{Field}/Test.php`). When a class is deleted, delete its test.
- **Cypress** under `tests/cypress/e2e/` — same rule. Delete tests for removed UI.
- **Coverage:** targeted at 80% (`vendor/bin/coverage-check`). New code should not lower the bar.

## Versioning

Plugin version lives in two places — keep them in sync:

- [speechkit.php](speechkit.php) plugin header `Version:` line
- `BEYONDWORDS__PLUGIN_VERSION` constant in the same file
- `version` in [package.json](package.json) (informational; npm publish is not used)

Bump on any release. Pre-release versions use `7.0.0-dev-1.0` style (matches existing convention).
