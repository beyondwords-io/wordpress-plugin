#   Running tests

If you haven't already got the [Mockoon](https://mockoon.com/) mock API server
running, make sure port `3000` is free and run:

```bash
yarn mockoon:start
```

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
composer test:phpunit
```

This will:

1. Run the PHPUnit test suite
2. Generate a code coverage HTML report.
3. Output the code coverage % value to the terminal.

To view the HTML report:

```bash
open tests/phpunit/_output/html/index.html
```

To run code coverage independently:

```bash
composer test:coverage-check
```
