#   Running tests

##  Prerequisites

###  1. Start mock API server

Before you run any tests or push commits to Git you will need to start a mock API server, so
the tests in our pre-commit [automated code quality checks](../doc/code-quality-checks.md)
will pass.

Make sure port `3000` is free for the [Mockoon](https://mockoon.com/) mock API
server, then run:

```bash
yarn mockoon:start
```

###  2. Create test audio in BeyondWords dashboard

- Locate some published audio in your
[BeyondWords dashboard](https://dash.beyondwords.io/auth/login). If you haven't
generated any audio yet, then generate your first audio using our
[TTS editor](https://docs.beyondwords.io/docs-and-guides/content/generate-audio/generate-via-tts-editor).
- Make a note of the **Project ID** and the **Content ID** for the audio - we
need these for the automated PHPUnit and Cypress tests to pass.
- Also make a note of your **API Key**.

###  3. Provide your test Project and Content IDs

Now copy and edit the **wp-env** and **Cypress** config files:

```bash
cp .wp-env.override.json.example .wp-env.override.json
cp cypress.env.json.example cypress.env.json
```

Edit both files, providing the **API Key**, **Project ID** and **Content ID**`
you noted earlier.

##  Cypress e2e tests

`/tests/cypress/`

To open the Cypress app:

```bash
yarn cypress:open
```

Or to run all tests in terminal (like we do in CI):

```bash
yarn cypress:run
```

##  PHPUnit tests

`/tests/phpunit/`

```bash
yarn composer test:phpunit
```

This will:

1. Run the PHPUnit test suite
2. Generate a code coverage HTML report.
3. Output the code coverage % value to the terminal.

To view the HTML report:

```bash
open tests/phpunit/_report/index.html
```

To run code coverage independently:

```bash
yarn composer test:coverage-check
```

##  Further reading

* [Xdebug IDE support](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support).
