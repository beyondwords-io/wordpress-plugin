# REST meta visibility

How BeyondWords post meta is exposed (or hidden) over the WordPress REST API.
Implemented in [src/post/class-sync.php](../src/post/class-sync.php)
(`register_meta()`, `hide_private_meta_from_rest()`).

## Registration model

`Sync::register_meta()` registers **every** BeyondWords post-meta key, per
compatible post type, so that:

- all writes are sanitised (`sanitize_text_field`, or the strict
  `Meta::sanitize_content_id()` charset check for `beyondwords_content_id`,
  whose value is interpolated into API URL paths);
- the legacy "Custom Fields" panel stays hidden via `is_protected_meta()`
  (the block editor's panel can break when plugin meta renders there —
  see [gutenberg#23078](https://github.com/WordPress/gutenberg/issues/23078));
- only the keys the block editor actually reads/writes over REST get
  `show_in_rest => true`.

Secrets and internal data — the legacy `speechkit_access_key`, cached API
responses, player/voice config — are registered `show_in_rest => false` and
never reach the REST API.

## Legacy keys still exposed (`Sync::REST_LEGACY_META_KEYS`)

On a site upgraded from the legacy SpeechKit plugin, deprecated keys
(`speechkit_project_id`, `speechkit_podcast_id`, `_speechkit_link`, …) can hold
a post's only BeyondWords data until audio is regenerated under v7. The block
editor reads them in the authenticated `edit` context as a fallback — the error
notice, pending notice, play/preview controls, the "Generate audio" toggle and
the legacy player link all depend on them (see the components under
[src/editor/components/](../src/editor/components/)).

## Hiding private meta from public REST (`Sync::REST_PRIVATE_META_KEYS`)

A handful of REST-exposed keys hold secrets or internal data: BeyondWords API
error strings, the audio preview token, and the legacy player URL.

`WP_REST_Meta_Fields` returns `show_in_rest` meta in the public `view` context
too, with **no capability check** — so an anonymous `GET /wp/v2/posts/:id`
would disclose them. Requesting the `edit` context is itself permission-gated
by the posts controller, so `Sync::hide_private_meta_from_rest()` keeps those
keys only for `edit` requests and strips them from every other response —
closing the anonymous disclosure while leaving the block editor unaffected.
The filter is registered on `rest_api_init` so it only loads for REST requests.

See also: [legacy-meta-migration.md](./legacy-meta-migration.md) for how keys
moved between the `current` and `deprecated` sets.
