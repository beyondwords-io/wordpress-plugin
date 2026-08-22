#   Running tests

##  Environments

There are two `wp-env` environments, defined by separate config files:

| Environment | Config file              | Port  | npm scripts                    |
|-------------|--------------------------|-------|--------------------------------|
| Development | `.wp-env.json`           | 8888  | `npm run env`, `npm run env:start` |
| Tests       | `.wp-env.tests.json`     | 8889  | `npm run env:tests`, `npm run env:tests:start` |

`npm run env:start` boots **both** environments. Use `npm run env:tests:start` if you only need the tests env (e.g. when developing locally).

To run an arbitrary `wp-env` command against a specific environment:

```bash
# dev (default)
npm run env -- run cli wp option get siteurl

# tests
npm run env:tests -- run cli wp option get siteurl
```

###  Why the plugin is mounted, not listed as a plugin

Both configs mount this directory at `wp-content/plugins/speechkit` via `mappings` rather than
listing `"./"` in `plugins`. A `plugins` entry takes its slug from the directory's basename, which
is only `speechkit` in the main checkout — in a git worktree (`.claude/worktrees/<name>`) it would
mount under the worktree's name instead, breaking `npm run composer:tests` and the Cypress
`wp plugin activate speechkit` step. A mapping names its destination outright, so every checkout
behaves the same. It has to be a swap, not an addition: keeping `"./"` alongside the mapping mounts
this directory at two slugs, wp-env activates both copies, and every wp-cli call then dies with
`Cannot redeclare class ComposerAutoloaderInit…`.

Mapped plugins are mounted but not activated, so `.wp-env.json` activates the plugin from an
`afterStart` lifecycle script. The tests env needs no equivalent: Cypress activates the plugin in
`setupDatabase`, and the PHPUnit bootstrap loads `speechkit.php` directly.

##  Prerequisites

###  1. Ensure Mock API is enabled

The tests **site** — the WordPress install Cypress drives — has
`BEYONDWORDS_MOCK_API` set to `true` in
[`.wp-env.tests.json`](../.wp-env.tests.json), so Cypress is mocked out of the
box. To change any of that per developer, add a `config` section to
`.wp-env.tests.override.json` — see
[`.wp-env.tests.override.json.example`](../.wp-env.tests.override.json.example)
— and restart with `npm run env:tests:start`.

PHPUnit does **not** inherit that setting. It boots against the WordPress test
framework's own `wp-tests-config.php`, which never sees wp-env's `wp-config.php`
defines, so [`tests/phpunit/bootstrap.php`](../tests/phpunit/bootstrap.php)
reads `BEYONDWORDS_MOCK_API` and the `BEYONDWORDS_TESTS_*` secrets from
environment variables (CI), falling back to the `config` section of
`.wp-env.tests.override.json` (local). That is why the example file sets
`BEYONDWORDS_MOCK_API` to `true`, and why you must create the override file
(step 3) — without it PHPUnit hits the real API. The bootstrap re-reads the
JSON on every run, so no restart is needed for PHPUnit.

`.wp-env.override.json` is the equivalent override for the **development** env,
and does not affect the tests env — see
[`.wp-env.override.json.example`](../.wp-env.override.json.example).

###  2. Create test audio in BeyondWords dashboard

- Locate some published audio in your
[BeyondWords dashboard](https://dash.beyondwords.io/auth/login). If you haven't
generated any audio yet, then generate your first audio using our
[TTS editor](https://docs.beyondwords.io/docs-and-guides/content/generate-audio/generate-via-tts-editor).
- Make a note of the **Project ID** and the **Content ID** for the audio - we
need these for the automated PHPUnit and Cypress tests to pass.
- Also make a note of your **API Key**.

###  3. Provide your test Project and Content IDs

PHPUnit reads test secrets from `.wp-env.tests.override.json` (gitignored):

```bash
cp .wp-env.tests.override.json.example .wp-env.tests.override.json
```

Cypress reads them from `cypress.env.json` (also gitignored):

```bash
cp cypress.env.json.example cypress.env.json
```

Edit both files, providing the **API Key**, **Project ID** and **Content ID**
you noted earlier.

If you also want the dev environment to pick up the same constants (e.g. to
hit the BeyondWords API from a local browser session), copy the override into
`.wp-env.override.json` as well — it's a separate file applied only to the
dev env.

##  Cypress e2e tests

`/tests/cypress/`

To open the Cypress app (Chrome):

```bash
npm run cypress:open
```

###  Run only the affected specs

The full suite takes 20+ minutes, so run just the specs covering the source you
changed. [AGENTS.md](../AGENTS.md#cypress-test-groups) has the `@group` /
`@covers` lookup and the `--spec` invocations, plus the groups in use.

The groups in use, and the header convention for new specs, are listed in
[AGENTS.md](../AGENTS.md).

Running `npm run cypress:run` with no arguments runs the whole suite in
Cypress's bundled Electron browser against the tests env on port 8889. CI runs
the suite differently — via the `cypress-io/github-action` with
`browser: chrome` against its own WordPress install — so pass
`--browser chrome` locally if you need to match CI's browser.

##  Jest unit tests

Jest covers the pure JavaScript helpers and the `@wordpress/data` settings
store — logic that is awkward or unreliable to reach through the editor UI.
Test files live next to the source they cover as `*.test.js`, for example
[src/settings/store/index.test.js](../src/settings/store/index.test.js) and
[src/editor/components/inspect-panel/helpers.test.js](../src/editor/components/inspect-panel/helpers.test.js).

```bash
npm run test:unit

# Re-run on change
npm run test:unit:watch
```

Both scripts wrap `wp-scripts test-unit-js`, which supplies the Jest config, so
there is no `jest.config.js` in the repo. The tests need no wp-env, database or
built assets. CI runs `npm run test:unit` as its own **Jest** job.

##  PHPUnit tests

`/tests/phpunit/`

Run the test suite (PHPUnit + coverage HTML report + 90% coverage gate):

```bash
npm run composer:tests -- test
```

`composer:tests` dispatches into the **tests** wp-env (port 8889) — see the [Environments](#environments) section above. It does not disturb the dev env.

To view the coverage HTML report:

```bash
open tests/phpunit/_report/index.html
```

To run the coverage gate standalone (without re-running the suite):

```bash
npm run composer:tests -- test:coverage-check
```

##  PHPUnit lore

- **AJAX handler tests** extend `WP_Ajax_UnitTestCase`, which pretends
  `DOING_AJAX` is true, routes `wp_die()` to a handler that captures the JSON
  body into `$this->_last_response` and throws a `WPAjaxDie*Exception`, and
  suppresses the "headers already sent" warning. See
  `tests/phpunit/posts-list/test-bulk-edit-ajax.php`.

##  Flaky-test lore

Hard-won details behind some non-obvious patterns in the Cypress suite:

- **`cy.getEditorCanvasBody()`** (`tests/cypress/support/commands.js`) is
  registered with `addQuery`, not `add`: only a query chain re-resolves when the
  block editor re-mounts its iframe during hydration. Anything that pins the
  resolved `<body>` — a command, or a `.then( cy.wrap )` boundary — fails as
  *"subject is no longer attached to the DOM"* on slower CI runs. For the same
  reason it throws when the body is empty rather than yielding it, so the query
  retries. Actions are not requeryable, so re-resolve the body instead of
  chaining off one (`cy.getEditorCanvasBody().find( … ).clear()`, then a fresh
  `cy.getEditorCanvasBody()` for the next step).
- **`chromeWebSecurity: false`** (`cypress.config.js`) is what keeps the block
  editor testable on WordPress 7.1. With web security on, every `cy.visit` of a
  block editor screen fails as *"Timed out after waiting `60000ms` for your
  remote page to load"* — even though the page itself finishes: inside it
  `document.readyState` is `complete`, `window.onload` has fired at ~800ms and
  no request is outstanding, but Cypress's Chrome driver never sees the load and
  `window:before:load` never fires. WordPress 7.0.4 is unaffected, as is the
  Electron browser, so the trigger is the 7.1 editor's blob-URL canvas iframe
  meeting Chrome's cross-origin rules under Cypress's proxy. Classic editor and
  settings screens have no such iframe and pass either way. Drop the setting
  once [cypress-io/cypress#28235](https://github.com/cypress-io/cypress/issues/28235)
  is fixed — re-test by removing it and running a block editor spec in Chrome.
- **Stubbing voices in the block editor doesn't work**: `cy.intercept` on the
  REST voices route stubs fine in the classic editor, but the block editor's
  `wp.data` store ends up empty. Branches that depend on voice-list contents
  (e.g. the single-bucket Model/Voice case) are covered by the classic-editor
  spec plus Jest unit tests instead.

##  Further reading

* [Xdebug IDE support](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support).
