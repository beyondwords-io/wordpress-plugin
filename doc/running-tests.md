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
npm run env:tests run cli wp option get siteurl
```

##  Prerequisites

###  1. Ensure Mock API is enabled

The tests environment has `BEYONDWORDS_MOCK_API` set to `true` by default in [`.wp-env.tests.json`](../.wp-env.tests.json). To override anything per developer, create a `.wp-env.override.json` file (for example, using the `config` section). Restart with `npm run env:tests:start` after editing.

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

To open the Cypress app:

```bash
npm run cypress:open
```

Or to run all tests in terminal (like we do in CI):

```bash
npm run cypress:run
```

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

##  Further reading

* [Xdebug IDE support](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support).
