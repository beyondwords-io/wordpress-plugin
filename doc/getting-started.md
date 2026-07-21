#   Getting started

*Please note that setup has only been tested on macOS.*

*You may need to modify the following steps for other operating systems.*

##  1. Clone this repo

For legacy reasons, our WordPress plugin uses the `speechkit` slug in WordPress.
Clone this repo into a `speechkit` directory as follows:

```bash
git clone git@github.com:beyondwords-io/wordpress-plugin.git speechkit
cd speechkit
```

##  2. Get Docker

[Get Docker](https://www.docker.com/get-started).

After installing, start Docker.

##  3. Get NVM & install Node version

[Get NVM](https://nvm.sh).

```bash
nvm install
nvm use
```

##  4. Install Node dependencies

```bash
npm install
npm run build
```

##  5. Get PHP & Composer

The next step runs Composer on your host machine, so you need a host PHP and a
host Composer. [composer.json](../composer.json) requires `php >=8.0`.

[Get Composer](https://getcomposer.org/download/). On macOS both are available
via Homebrew:

```bash
brew install php composer
```

##  6. Install PHP dependencies

Run Composer on your host machine, not inside a container:

```bash
composer install
```

This must happen before wp-env starts. `wp-env start` activates every plugin
listed in `.wp-env.json`, including this one, and
[speechkit.php](../speechkit.php) requires `vendor/autoload.php` at load time,
so activation fatals if `vendor/` is missing.

The `npm run composer` script is only usable once the environment exists — it
is `wp-env run cli ... composer`, so it dispatches into a container that
`npm run env:start` has not created yet. Use it for later Composer commands,
such as regenerating the classmap autoloader after adding, removing or renaming
files in `src/`:

```bash
npm run composer -- dump-autoload
```

##  7. Start wp-env

Ensure that Docker is running, then start both environments:

```bash
npm run env:start
```

This boots two separate `wp-env` environments side by side:

| Environment | Port  | Config                | Used for                       |
|-------------|-------|------------------------|--------------------------------|
| Development | 8888  | `.wp-env.json`         | day-to-day development         |
| Tests       | 8889  | `.wp-env.tests.json`   | PHPUnit + Cypress test suites  |

If you only need the tests environment (e.g. while iterating on a single test):

```bash
npm run env:tests:start
```

After some time you should see something like:

```
WordPress development site started at http://localhost:8888/
WordPress development site started at http://localhost:8889/
```

##  That's it!

Well done, you should now have a functional wp-env development environment for our plugin.

The plugin is already active: wp-env activates every plugin listed in
`.wp-env.json`, and `./` (this repo) is one of them.

Log into WordPress admin and go to Settings → BeyondWords to enter the API key
and Project ID that connect the plugin to your BeyondWords project. See
[src/settings/class-settings.php](../src/settings/class-settings.php).

* The default WordPress credentials are Username: `admin`, Password: `password`
* The default database credentials are: Username: `root`, Password: `password`

Our [automated code quality checks](code-quality-checks.md) run on every commit
via a git hook, and need to pass before the commit is accepted. See
[running tests](running-tests.md) to get the tests running in your wp-env.

##  Further reading

* [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
