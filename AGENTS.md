# AGENTS.md

Conventions for working in the BeyondWords WordPress plugin codebase. These apply to humans and AI agents alike. Keep this file authoritative — update it when conventions change.

## Non-negotiables

1. **WordPress VIP compatibility is paramount.** All new and modified code must pass the `WordPress-VIP-Go` PHPCS ruleset without `phpcs:ignore` exceptions. If a sniff fires, fix the underlying code, not the comment.
2. **WordPress coding standards must be followed for both PHP and JavaScript — no exceptions.** Format new code accordingly; reformat surrounding code when you touch it.

## File structure

All PHP under `src/` follows the same WordPress-style layout:

```
src/
  compatibility/   class-{name}.php       — third-party plugin compatibility shims
  core/            class-{name}.php       — bootstrap, post-lifecycle, API client, env, request, updater, uninstaller
  editor/          {feature}/index.js     — block-editor `@wordpress/plugins` slot registrations (JS-only)
  player/          class-{name}.php       — front-end player + renderer/{base,amp,javascript}
  post/            class-{name}.php       — per-post screen UI (each component in its own subfolder when JS/CSS travels with it)
  posts/           class-{name}.php       — posts list-screen UI
  settings/        class-{name}.php       — plugin settings page + REST endpoints
  site-health/     class-{name}.php       — Site Health debug panel
  index.js         build entry that requires each component's `index.js`
```

- **One class per file.** File name is `class-{kebab-case-name}.php` (e.g. `class-api-client.php` → `ApiClient`).
- **1–2 levels of nesting.** Pure-PHP utilities sit at the feature root (`src/post/class-post-meta-utils.php`). Components that bundle JS/CSS get their own subfolder so assets travel with the PHP (`src/post/add-player/{class-add-player.php,index.js,AddPlayer.css}`).
- **JS folders use kebab-case** to match the PHP folder convention (`src/post/add-player/`, `src/editor/document-setting/`).
- **Group by feature, not by class type.** `Fields` lives next to `Tabs` because they're both part of the settings feature, not in a separate `Fields/` folder.

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
		// Cross-namespace references use FQN — see "Class references" below.
		\BeyondWords\Core\CoreUtils::is_edit_screen();
	}
}
```

Rules:

- **Namespaces are required.** Top-level namespace is `BeyondWords\`, then the feature (e.g. `BeyondWords\Settings`). Class names stay short.
- **Static methods only.** No instances, no constructors, no DI. State lives in WordPress options/transients/post meta, not in objects.
- **`init()` is the entry point.** It owns all `add_action`/`add_filter` registrations for the class. It must be idempotent.
- **No bottom-of-file `Class::init();` self-call.** Autoloading wouldn't load the file until the class is referenced anyway, so `init()` is invoked explicitly from the plugin bootstrap ([src/core/class-plugin.php](src/core/class-plugin.php)).
- **snake_case for methods, variables, hook names, option keys.** Class names stay PascalCase to match WordPress conventions for class identifiers.
- **PHPDoc blocks on every public method.** Param/return types are required where they aren't obvious from signature.

## Class references

**Use fully-qualified names inline** at every call site rather than top-of-file `use` imports. We deliberately removed all `use` statements from `src/` for two reasons:

1. **Locality.** Reading `\BeyondWords\Settings\Utils::has_valid_api_connection()` at the call site tells you exactly which class is being invoked without scrolling to the imports block.
2. **Refactor safety.** Moving a class to a new namespace requires updating every call site explicitly — no risk of an aliased import quietly resolving to the wrong class after a rename.

```php
// preferred — FQN inline.
$result = \BeyondWords\Core\Environment::get_api_url();
$post   = \WPGraphQL\Model\Post::class;

// avoid — top-of-file use imports.
use BeyondWords\Core\Environment;
use WPGraphQL\Model\Post;
```

**Exceptions** (when bare references are fine):

- **Same-namespace references.** Inside `BeyondWords\Core`, calling `Updater::run()` or `extends Base` resolves correctly without a leading `\` and without a `use` import.
- **`self::`, `static::`, `parent::`** are language constructs, not class references.
- **`\WP_Post`, `\WP_Error`, `\WP_REST_Response`** etc. are root-namespaced WordPress classes and the leading `\` is enough on its own — no further qualification needed.

If you find yourself wanting a `use` to shorten a deeply-nested namespace, that's usually a signal that the call site is doing too much — extract a helper.

## Autoloading

Composer **classmap** autoload covers the whole `src/` tree:

```json
"autoload": {
    "classmap": ["src/"]
}
```

Classmap (not PSR-4) is required because PSR-4 expects file names to match class names — incompatible with `class-tabs.php` style. Run `composer dump-autoload` after adding, removing, or renaming files in `src/`.

The bootstrap is [src/core/class-plugin.php](src/core/class-plugin.php) (`BeyondWords\Core\Plugin::init()`), invoked from [speechkit.php](speechkit.php). It calls each class's `init()` in dependency order — keep new classes' init wiring there.

## PHP standards

- **PHPCS ruleset:** `WordPress-VIP-Go` (via `automattic/vipwpcs`). Configured in [.phpcs.xml](.phpcs.xml). The PHPCS run is the source of truth — fix whatever it complains about.
- **Indentation:** tabs (per WordPress core standard).
- **Yoda conditions:** disabled in the ruleset (matches existing convention). Use whichever reads more naturally.
- **Escaping:** all output must be escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). All input must be sanitised (`sanitize_text_field`, `absint`, `rest_sanitize_boolean`).
- **Nonces:** every form submission and admin-side state change verifies a nonce.
- **Translation:** every user-facing string uses `__()` / `esc_html__()` with the `'speechkit'` text domain.
- **i18n placeholders:** numbered (`%1$s`, `%2$s`) when there's more than one — never positional.
- **Arrays:** always short syntax — `[]`, never `array()`. Enforced by `Generic.Arrays.DisallowLongArraySyntax` in `.phpcs.xml`; `npm run phpcbf` auto-fixes any long-form leftovers.
- **No `extract()`, `eval()`, `query_posts()`, `wp_reset_query()`, or direct `$wpdb` queries** unless there is no WP API equivalent.
- **Caching:** anything that hits the database in a hot path must be cached. VIP fails the build otherwise.

Run before commit:

```bash
npm run phpcs              # WordPress-VIP-Go check
npm run phpcbf             # auto-fix what can be fixed
npm run composer:tests -- test   # PHPUnit test suite + coverage check
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
- For npm scripts that themselves accept arguments (notably the `composer` / `composer:tests` passthroughs), forward args with `--`: `npm run composer:tests -- test`.

## Deprecating settings

When removing a setting:

1. Delete the field/tab classes and their tests.
2. Move the option key from the `current` array to the `deprecated` array in [src/core/class-core-utils.php](src/core/class-core-utils.php) `get_options()`. The uninstaller cleans up deprecated keys, so users upgrading lose stale data on plugin removal.
3. Delete every `get_option()` call and any code paths gated on it.
4. Replace runtime behaviour with one of: a hard-coded default, a `apply_filters()` hook, or per-post meta (whichever the spec calls for).
5. Add a version-gated migration to [src/core/class-updater.php](src/core/class-updater.php) if existing data needs to be transformed. To also strip deprecated options from the DB on update, call `Updater::delete_deprecated_options()` from inside the version block.

## Tests

### PHPUnit

Source: `tests/phpunit/` — folder layout mirrors `src/` (e.g. `tests/phpunit/Core/`, `tests/phpunit/Component/Post/...`). When a class is deleted, delete its test.

Run the suite (PHPUnit + clover coverage report + 80% coverage gate):

```bash
npm run composer:tests -- test
```

`composer:tests` dispatches into the tests-cli container ([.wp-env.tests.json](.wp-env.tests.json), port 8889), separate from the dev env, so PHPUnit doesn't disturb whatever you're working on locally. Use `npm run composer -- ...` (no `:tests` suffix) for composer commands targeting the dev env.

**Test secrets** — three constants are needed (`BEYONDWORDS_TESTS_API_KEY`, `BEYONDWORDS_TESTS_PROJECT_ID`, `BEYONDWORDS_TESTS_CONTENT_ID`). Provide them via:

- **Local**: copy [.wp-env.tests.override.json.example](.wp-env.tests.override.json.example) to `.wp-env.tests.override.json` and fill in. Read by the PHPUnit bootstrap directly — no env-var export needed.
- **CI**: GitHub Actions secrets (`BEYONDWORDS_TESTS_API_KEY` etc.) exported as env vars before phpunit runs.

**Coverage**: 80% gate enforced by `vendor/bin/coverage-check`. New code shouldn't lower it. HTML report lands in `tests/phpunit/_report/index.html`.

### Cypress

Source: `tests/cypress/e2e/` — same delete-when-the-UI-is-removed rule. (Detailed Cypress workflow is documented in [doc/running-tests.md](doc/running-tests.md); the suite is mid-rewrite for v7.0.0.)

## Versioning

Plugin version lives in two places — keep them in sync:

- [speechkit.php](speechkit.php) plugin header `Version:` line
- `BEYONDWORDS__PLUGIN_VERSION` constant in the same file
- `version` in [package.json](package.json) (informational; npm publish is not used)

Bump on any release. Pre-release versions use `7.0.0-dev-1.0` style (matches existing convention).
