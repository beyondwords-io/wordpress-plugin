#   BeyondWords WordPress Plugin

![Banner](.wordpress-org/banner-1544x500.png)

[![Github Actions Workflow](https://github.com/beyondwords-io/wordpress-plugin/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/beyondwords-io/wordpress-plugin/actions/workflows/main.yml)
[![PHPUnit Code Coverage](https://beyondwords-io.github.io/wordpress-plugin/coverage-badge.svg)](https://beyondwords-io.github.io/wordpress-plugin/dashboard.html)
[![Supported WordPress Versions](https://img.shields.io/static/v1?label=&message=6.6+-+7.0&color=blue&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![Supported PHP Versions](https://img.shields.io/static/v1?label=&message=8.0+-+8.5&color=777bb4&logo=php&logoColor=white)](https://www.php.net/)

##  Description

BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses. Automatically create audio versions of WordPress posts and pages and embed via a customizable player. Lifelike neural voices and customizable text-to-speech algorithms deliver realistic speech that keeps listeners coming back for more.

##  Documentation

The [doc/](./doc/) directory contains these useful resources:

1. [Getting started](./doc/getting-started.md): Set up a development environment
for our plugin using *wp-env*.
2. [Code quality checks](./doc/code-quality-checks.md): Automated code quality
checks that run for every commit.
3. [Running tests](./doc/running-tests.md): How to manually run our unit and e2e tests in your
local development environment.
4. [WordPress VIP](./doc/wordpress-vip.md): Our *WordPress VIP* compatibility.
5. [Plugin features](./doc/plugin-features.md): An overview of the features of
our plugin.
6. [wp-config.php](./doc/wp-config.md): Our *wp-config.php* settings.
7. [Async REST migration](./doc/async-rest-migration.md): API response caching
and background (cron) audio generation on WordPress VIP.
8. [Legacy meta migration](./doc/legacy-meta-migration.md): The v7 post-meta
cleanup and downgrade-safety guarantees.
9. [REST meta visibility](./doc/rest-meta-visibility.md): How BeyondWords post
meta is exposed (or hidden) over the WordPress REST API.
10. [Video settings payload](./doc/video-settings-payload.md): Why the plugin
sends a full `video_settings` object to the content endpoint.
11. [Preselect "Generate audio"](./doc/preselect-generate-audio.md): The
setting's stored format and how the editor and save path honour it.
12. [Settings internals](./doc/settings-internals.md): Settings-error
transport and the API connection check.

##  Links

- [BeyondWords](https://beyondwords.io/): Create your free account or manage
your BeyondWords projects here.
- [Online docs](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress):
The online docs for installing and configuring our WordPress plugin.
- [WordPress Plugin repo page](https://wordpress.org/plugins/speechkit/): Our
plugin page on *wordpress.org*.

##  License

This WordPress plugin is released under the [GPL](https://www.gnu.org/licenses/licenses.html#GPL),
which is the same license that [WordPress itself uses](https://wordpress.org/about/license/).
