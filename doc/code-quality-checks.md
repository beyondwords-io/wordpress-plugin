#   Code quality checks

We use [GrumPHP](https://github.com/phpro/grumphp) to run code quality checks
before every commit.

> When somebody commits changes, GrumPHP will run some tests on the committed
> code. If the tests fail, you won't be able to commit your changes.

The GrumPHP config file is at [`grumphp.yml`](../grumphp.yml).

The checks are:

- `git_commit_message`: Require a brief commit message (under 120 chars)
- `phpversion`: Check for PHP version >= 8.0
- `composer`: Validate `composer.json`
- `composer_normalize`: Keep `composer.json` nice and tidy
- `phpcs`: Run [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
using the config at `phpcs.xml`, to *PSR-12* and *WordPress VIP* standards.
- `phplint`: Check source files for syntax errors
- `phpmd`: Check source files for bad coding standards
- `rector`: Check source files for refactoring opportunities
- `test_phpunit`: PHPUnit tests
- `coverage_check`: PHPUnit code coverage

If you see a big "nope" then fix the reported problems before attempting to
commit your code again.

## Bypassing the checks

Adding `-n` as an argument to your `git commit` command will skip the GrumPHP
checks for the current commit on your development machine, but
**GrumPHP always runs in the GitHub Actions workflow**.

We urge you to fix any reported problems locally before pushing your commits.

We are unable to merge and deploy PRs that fail code quality checks.
