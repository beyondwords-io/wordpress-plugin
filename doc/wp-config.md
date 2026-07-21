#   wp-config.php

To override default behaviour, the following constants can be defined in
`wp-config.php`.

##  Behaviour

###  BEYONDWORDS_AUTOREGENERATE

Setting this to a falsy value stops the plugin regenerating audio for posts
that already have a BeyondWords content ID. The check runs in
[src/post/class-sync.php](../src/post/class-sync.php) only after an existing
content ID has been found, so initial generation for posts without audio is
unaffected. Defaults to regenerating.

```php
define('BEYONDWORDS_AUTOREGENERATE', false);
```

The value is reported in the "BeyondWords - Text-to-Speech" section of the Site
Health Info screen, see
[src/site-health/class-site-health.php](../src/site-health/class-site-health.php).

##  URL overrides

The URLs the plugin uses are defined as class constants in
[src/core/class-urls.php](../src/core/class-urls.php). Each one can be
overridden by defining a constant of the same name in `wp-config.php`. These
are intended for local development and testing against non-production
environments.

An override is only applied if it is a non-empty string — the accessors guard
with `strlen()`, so defining a constant as `''` leaves the built-in default in
place.

| Constant | Default |
| --- | --- |
| `BEYONDWORDS_API_URL` | `https://api.beyondwords.io/v1` |
| `BEYONDWORDS_BACKEND_URL` | `''` (empty) |
| `BEYONDWORDS_JS_SDK_URL` | `https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js` |
| `BEYONDWORDS_AMP_PLAYER_URL` | `https://audio.beyondwords.io/amp/%d?podcast_id=%s` |
| `BEYONDWORDS_AMP_IMG_URL` | `https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/assets/logo.svg` |
| `BEYONDWORDS_DASHBOARD_URL` | `https://dash.beyondwords.io` |

`BEYONDWORDS_AMP_PLAYER_URL` is a format string: `%d` is the project ID and
`%s` is the content ID.

`BEYONDWORDS_BACKEND_URL` is legacy: `Urls::get_backend_url()` has no callers in
`src/`, so overriding it changes nothing at runtime. Note that the build also
reads a `BEYONDWORDS_BACKEND_URL` *environment* variable via `DefinePlugin` in
[webpack.config.js](../webpack.config.js) (defaulting to
`https://audio.beyondwords.io`); that is a separate, build-time value and is
unrelated to the `wp-config.php` constant.

```php
define('BEYONDWORDS_API_URL', 'https://api.staging.example.com/v1');
define('BEYONDWORDS_DASHBOARD_URL', 'https://dash.staging.example.com');
```
