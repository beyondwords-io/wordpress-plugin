# Settings internals

Non-obvious plumbing behind the plugin settings screens, implemented in
[src/settings/class-utils.php](../src/settings/class-utils.php) and
[src/settings/class-tabs.php](../src/settings/class-tabs.php).

## Settings-error transport (`Utils::add_settings_error_message()`)

Error notices raised while saving settings must survive the Settings API's
`wp_redirect()` back to the settings page. They are carried in a **transient**,
not `wp_cache_*`: WordPress's default object cache is request-scoped, so on
hosts without a persistent Redis/Memcached drop-in a cache entry would be empty
after the redirect. Transients fall back to the options table, which does
persist. The 30-second TTL deliberately matches core's own `settings_errors`
transient.

## API connection check (`Utils::validate_api_connection()`)

`beyondwords_valid_api_connection` stores a **timestamp** recording the last
successful credential validation. It gates visibility of the Integration /
Preferences tabs via `Tabs::get_visible_tabs()`.

- The validation request relies on `Client::DEFAULT_REQUEST_TIMEOUT`, so a slow
  or unreachable API cannot block admin rendering.
- Transient failures (timeout, DNS, 5xx, `WP_Error`) intentionally leave the
  last known-good flag in place — a blip should not lock the operator out of
  the settings tabs.
- Only authentication failures clear it: a 401 (in `Client::call_api()`) or a
  403 (in the validation itself), after which the settings page re-runs
  validation.
