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

[Get Yarn](https://classic.yarnpkg.com/lang/en/docs/install/).

```bash
yarn install
yarn build
```

##  5. Install PHP dependencies

```bash
yarn composer install
```

##  6. Start wp-env development server

Ensure that Docker is running, then:

```bash
yarn wp-env:start
```

After a few minutes, you should see something like:

```
WordPress development site started at http://localhost:8888/
WordPress test site started at http://localhost:8889/
MySQL is listening on port 60920
MySQL for automated testing is listening on port 60931
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
