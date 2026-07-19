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

Validation runs when the Authentication tab loads, and is throttled by the
`beyondwords_api_connection_checked` transient
(`Utils::CONNECTION_CHECK_TRANSIENT`), whose TTL is
`Utils::CONNECTION_CHECK_TTL` — 5 minutes. The transient stores an `md5()`
**fingerprint of the credentials**, not a boolean:

- Inside the window, a stored fingerprint matching the current project ID and
  API key means the last result for *those* credentials is trusted and no
  request is made.
- The fingerprint is recorded whatever the outcome, so a failing or down API is
  throttled too rather than retried on every page load.
- Editing the API key or project ID changes the fingerprint, busting the
  throttle and forcing an immediate re-check.

How the flag itself is updated:

- If the project ID or API key is missing, the flag is deleted outright and no
  request (or throttle write) happens at all.
- The validation request relies on `Client::DEFAULT_REQUEST_TIMEOUT` — 3
  seconds, the WordPress VIP ceiling for a blocking remote request — so a slow
  or unreachable API cannot block admin rendering.
- Transient failures (timeout, DNS, 5xx, `WP_Error`) intentionally leave the
  last known-good flag in place — a blip should not lock the operator out of
  the settings tabs.
- Only authentication failures clear it: a 401 (in `Client::call_api()`,
  [src/api/class-client.php](../src/api/class-client.php)) or a 403 (in the
  validation itself). Re-validation then happens on the next Authentication
  tab load that is not throttled — immediately if the credentials were edited,
  otherwise once the 5-minute window expires.
