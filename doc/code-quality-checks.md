#   Code quality checks

We use [GrumPHP](https://github.com/phpro/grumphp) to run code quality checks
before every commit.

> When somebody commits changes, GrumPHP will run some tests on the committed
> code. If the tests fail, you won't be able to commit your changes.

The GrumPHP config file is at [`grumphp.yml`](../grumphp.yml). It declares a
list of tasks and a set of testsuites, and each git hook runs one named
testsuite: the `commit-msg` hook runs `git_commit_msg`, and the `pre-commit`
hook runs `git_pre_commit`. Tasks that are not in the testsuite for a hook do
not run on commit.

`stop_on_failure` is enabled, so the run halts at the first failing task.

## What runs on commit

The `git_commit_msg` testsuite runs one task:

- `git_commit_message`: Cap the width of the commit subject line
(`max_subject_width: 120`) and do not require the subject to be capitalised
(`enforce_capitalized_subject: false`). `max_body_width` is not set in
`grumphp.yml`, so GrumPHP's default of 72 applies and every body line wider
than that fails the hook

The `git_pre_commit` testsuite runs five tasks:

- `composer`: Validate `composer.json`
- `composer_normalize`: Keep `composer.json` nice and tidy (4-space indent)
- `phpcs`: Run [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
over the plugin source using the config at [`.phpcs.xml`](../.phpcs.xml)
- `lint_plugins`: Run PHP_CodeSniffer over the companion plugins in
[`plugins/`](../plugins) via the `lint:plugins` composer script
- `phplint`: Check source files for syntax errors, excluding `vendor` and
`plugins`

If you see a big "nope" then fix the reported problems before attempting to
commit your code again.

### The `phpcs` ruleset

[`.phpcs.xml`](../.phpcs.xml) scans [`src`](../src),
[`speechkit.php`](../speechkit.php), [`uninstall.php`](../uninstall.php) and
[`index.php`](../index.php). It layers four WordPress rulesets in increasing
order of strictness — *WordPress-Core*, *WordPress-Docs*, *WordPress-Extra* and
*WordPress-VIP-Go* — plus *VariableAnalysis* for unused, undefined and
re-declared variables. Later rulesets override earlier ones.

On top of those it sets some project conventions (short array syntax, K&R brace
placement, the `speechkit` text domain) and silences a set of rules, many of
them PHPDoc requirements. Most are grouped under a comment giving the
reason — legacy code under `src/` that predates the strict ruleset, and PHPDoc
rules whose retroactive satisfaction would be a sprint of mechanical typing
without functional value — and a few later ones carry their own note. Two
array-spacing sniffs
(`WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound` and
`NormalizedArrays.Arrays.ArrayBraceSpacing.SpaceBeforeArrayCloserSingleLine`)
are silenced without a stated reason.

The `phpcs` task ignores `/tests/*`, `/plugins/*` and
`/wp-tests-config-sample.php`. The `plugins/` directory is covered separately by
`lint_plugins`, which uses [`plugins/.phpcs.xml`](../plugins/.phpcs.xml) — a
smaller ruleset based on *WordPress-VIP-Go* alone.

## What exists but is not run on commit

Two further tasks are declared in [`grumphp.yml`](../grumphp.yml) but are not
members of any testsuite:

- `test_phpunit`: runs the `test` composer script (PHPUnit, then the coverage
check)
- `coverage_check`: runs the `test:coverage-check` composer script, which reads
`tests/phpunit/_report/clover.xml` and fails below 85% coverage

Because they are in no testsuite, neither git hook runs them. They only run when
GrumPHP is invoked with no `--testsuite` option (`./vendor/bin/grumphp run`),
which runs every declared task. In practice PHPUnit is run directly — see
[running-tests.md](running-tests.md).

## Bypassing the checks

Adding `-n` as an argument to your `git commit` command will skip the GrumPHP
checks for the current commit on your development machine, but the checks also
run in the GitHub Actions workflow, so skipping them locally only defers the
failure.

## What runs in GitHub Actions

[`.github/workflows/main.yml`](../.github/workflows/main.yml) does not run all
of GrumPHP. The `grumphp` job runs a single testsuite:

```sh
./vendor/bin/grumphp run --testsuite code_quality
```

The `code_quality` testsuite holds the same five tasks as `git_pre_commit`:
`composer`, `composer_normalize`, `phpcs`, `lint_plugins` and `phplint`.

The remaining checks are separate jobs in the same workflow:

- `lint-js`: ESLint over `src` via `npm run lint:js`, after asserting that
prettier resolves to wp-prettier
- `jest-tests`: Jest unit tests via `npm run test:unit`
- `phpunit-tests`: PHPUnit across a PHP 8.0 / 8.5 matrix, followed by
`composer test:coverage-check`
- `plugin-check`: the WordPress plugin-check action, run against the built
plugin ZIP
- `cypress-tests`: Cypress end-to-end tests against a real WordPress install

We urge you to fix any reported problems locally before pushing your commits.

We are unable to merge and deploy PRs that fail code quality checks.
