# Autoloading during a plugin upgrade

Why [src/api/class-client.php](../src/api/class-client.php) resolves the API URL
in `init()` instead of inside `filter_http_request_args()`.

## The problem

`Plugin_Upgrader` deletes the installed `speechkit/` directory and writes the new
copy **within a request that already loaded the old version**. Our hooks are
still registered, and `upgrader_process_complete` runs `wp_update_plugins()`,
which issues an HTTP request — so `http_request_args` fires after the files
backing our classes have gone.

Any class the callback autoloads at that point is a fatal error, because
Composer's classmap still points at the deleted path:

```
Warning: include(.../speechkit/vendor/composer/../../src/core/class-urls.php): Failed to open stream: No such file or directory
Fatal error: Uncaught Error: Class "BeyondWords\Core\Urls" not found in .../src/api/class-client.php:93
```

The install itself has already succeeded by then, so the site recovers on the
next request — but the user sees a white screen and gets a fatal-error email.

It bites hardest on a **downgrade** (7.x → 6.x), where the replacement payload
has an entirely different file layout and none of the 7.x paths resolve. A
same-branch update only survives by luck: the new copy happens to contain a file
at the same path.

## The rule

**Anything reachable from a hook that can fire during an upgrade must already be
in memory.** In practice that means `http_request_args` (every outbound request,
including core's update checks) and `admin_notices` (rendered on `update.php`).

`Client::init()` runs during bootstrap, while the files are guaranteed to exist,
so resolving `Urls::get_api_url()` there loads `Urls` for the whole request. That
also covers `Settings::maybe_print_missing_creds_warning()`, which reaches for
`Urls::get_dashboard_url()` from `admin_notices`.

A fix can only ever protect *later* downgrades: the code running during the
upgrade is the version being replaced, so a release can't repair the one before
it.
