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

##  5. Install PHP dependencies

```bash
npm run composer -- install
```

##  6. Start wp-env

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

Log into WordPress admin and activate our plugin to get started.

* The default WordPress credentials are Username: `admin`, Password: `password`
* The default database credentials are: Username: `root`, Password: `password`

Before you push any commits in Git our [automated code quality checks](../doc/code-quality-checks.md)
need to pass. See [running tests](../doc/running-tests.md) to get the tests running in your wp-env.

##  Further reading

* [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
