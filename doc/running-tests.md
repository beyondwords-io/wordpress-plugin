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

The full suite takes 20+ minutes, so the normal workflow is to run just the
specs that exercise the source you changed. Every spec starts with a `@group`
and one or more `@covers` header tags, so grep for them:

```bash
# Specs that cover a file or directory
grep -rl '@covers .*content-id' tests/cypress/e2e/

# All specs in a group
grep -rl '@group block-editor' tests/cypress/e2e/
```

Then run only those:

```bash
# One spec
npm run cypress:run -- --browser chrome --spec 'tests/cypress/e2e/block-editor/content-id.cy.js'

# Multiple (comma-separated, no spaces)
npm run cypress:run -- --browser chrome --spec 'tests/cypress/e2e/block-editor/content-id.cy.js,tests/cypress/e2e/classic-editor/content-id.cy.js'

# Whole group via glob
npm run cypress:run -- --browser chrome --spec 'tests/cypress/e2e/settings/*.cy.js'
```

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

Run the test suite (PHPUnit + coverage HTML report + 85% coverage gate):

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

- **`cy.getEditorCanvasBody()`** (`tests/cypress/support/commands.js`) must not
  introduce a `.then( cy.wrap )` boundary. Pinning the resolved `<body>`
  subject breaks Cypress's retry-ability when the block-editor iframe
  re-renders during hydration — it fails as *"subject is no longer attached to
  the DOM"*, seen mostly on slower PHP 8.0 CI runs. Whether the editor canvas
  is an iframe is stable for a given WordPress version, so it is detected once
  synchronously via `Cypress.$` instead.
- **Stubbing voices in the block editor doesn't work**: `cy.intercept` on the
  REST voices route stubs fine in the classic editor, but the block editor's
  `wp.data` store ends up empty. Branches that depend on voice-list contents
  (e.g. the single-bucket Model/Voice case) are covered by the classic-editor
  spec plus Jest unit tests instead.

##  Further reading

* [Xdebug IDE support](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support).
