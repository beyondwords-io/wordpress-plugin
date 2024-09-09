#   Getting started

*Please note that setup has only been tested on macOS.*

*You may need to modify the following steps for other operating systems.*

##  Prerequisites

* [Get NVM](https://nvm.sh).
* [Get Yarn](https://classic.yarnpkg.com/lang/en/docs/install/).
* [Get Docker](https://www.docker.com/get-started).

##  1. Create test audio in BeyondWords dashboard

- Locate some published audio in your
[BeyondWords dashboard](https://dash.beyondwords.io/auth/login). If you haven't
generated any audio yet, then generate your first audio using our
[TTS editor](https://docs.beyondwords.io/docs-and-guides/content/generate-audio/generate-via-tts-editor).
- Make a note of the **Project ID** and the **Content ID** for the audio - we
need these for the automated PHPUnit and Cypress tests to pass.
- Also make a note of your **API Key**.

##  2. Clone this repo

For legacy reasons, our WordPress plugin uses the `speechkit` slug in WordPress.
Clone this repo into a `speechkit` directory as follows:

```bash
git clone git@github.com:beyondwords-io/wordpress-plugin.git speechkit
cd speechkit
```

##  3. Provide your test Project and Content IDs

Now copy and edit the **wp-env** and **Cypress** config files:

```bash
cp .wp-env.override.json.example .wp-env.override.json
cp cypress.env.json.example cypress.env.json
```

Edit both files, providing the **API Key**, **Project ID** and **Content ID**`
you noted earlier.

##  4. Install dependencies

```bash
nvm install
nvm use
yarn install
yarn composer install
```

##  5. Build

```bash
yarn build
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

* The default WordPress credentials are Username: `admin`, Password: `password`
* The default database credentials are: Username: `root`, Password: `password`

##  7. Start mock API server

Before you make any commits in Git you will need to start a mock API server, so
the tests in our pre-commit [automated code quality checks](../doc/code-quality-checks.md)
will pass.

Make sure port `3000` is free for the [Mockoon](https://mockoon.com/) mock API
server, then run:

```bash
yarn mockoon:start
```

##  Further reading

* [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
* [Xdebug IDE support](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support).
