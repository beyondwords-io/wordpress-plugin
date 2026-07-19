##  WordPress VIP

[VIP](https://wpvip.com) is a fully managed WordPress cloud platform from
[Automattic](https://automattic.com).

##  VIP Compatibility

Some of our existing clients are on WordPress VIP. Our plugin has been tested on
the platform from plugin version `4.0.0`.

If you experience any problems on WordPress VIP please contact us at
[support@beyondwords.io](mailto:support@beyondwords.io).

##  Developing with VIP

> VIP’s code review focuses on the performance and security considerations in
PHP, custom JavaScript, and SVG files. We do not review HTML, CSS, SASS, many
popular third-party JavaScript libraries, or built JavaScript files.

See [Developing with VIP](https://wpvip.com/documentation/developing-with-vip)

##  Object-cache notes

On hosts with an external object cache (VIP uses Memcached), transients are not
stored as `_transient_*` rows in the options table, so they cannot be
enumerated or bulk-deleted. This is why:

- the uninstaller's transient cleanup only deletes known keys — anything else
  holds a TTL and self-expires;
- the API client salts its cache keys with the project ID + API key
  (`Client::cache_key()`), so changing credentials invalidates the cache
  implicitly with no flush step.

See also [async-rest-migration.md](./async-rest-migration.md) for the
VIP-gated background (cron) audio generation.
