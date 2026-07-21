# REST meta visibility

How BeyondWords post meta is exposed (or hidden) over the WordPress REST API.
Implemented in [src/post/class-sync.php](../src/post/class-sync.php)
(`register_meta()`, `register_rest_meta_visibility()`,
`hide_private_meta_from_rest()`).

## Registration model

`Sync::register_meta()` registers **every** BeyondWords post-meta key, per
compatible post type, so that:

- all writes are sanitised (`sanitize_text_field`, or the strict
  `Meta::sanitize_content_id()` charset check for `beyondwords_content_id`,
  whose value is interpolated into API URL paths);
- writes are authorised (`auth_callback` requires `edit_posts`);
- only the keys the block editor actually reads/writes over REST get
  `show_in_rest => true`.

`show_in_rest` is set from the `current` key list in
[src/core/class-utils.php](../src/core/class-utils.php)
(`Utils::get_post_meta_keys()`) plus `Sync::REST_LEGACY_META_KEYS`; every other
deprecated key gets `show_in_rest => false` and never reaches the REST API.
That hidden set covers, among others, the legacy `speechkit_access_key`, the
cached SpeechKit API state (`speechkit_response`, `speechkit_info`,
`speechkit_status`, `speechkit_retries`, `speechkit_error`), the per-post
player/voice keys deprecated in v7 (`beyondwords_player_style`,
`beyondwords_player_content`, `beyondwords_title_voice_id`,
`beyondwords_summary_voice_id`, `beyondwords_disabled`) and the remaining
hash/state bookkeeping keys (`beyondwords_hash`, `speechkit_hash`,
`speechkit_disabled`, `speechkit_updated_at`, `publish_post_to_speechkit`,
`_speechkit_text`). The voice and language keys the v7 editor still uses —
`beyondwords_body_voice_id`, `beyondwords_language_code`,
`beyondwords_language_id` — are `current`, so they *are* exposed.

Hiding meta from the legacy "Custom Fields" panel is a separate mechanism, not
a side effect of registration: `Sync::init()` filters `is_protected_meta`, and
the callback `Sync::is_protected_meta()` flags every key in
`Utils::get_post_meta_keys( 'all' )` as protected. Unlike registration it is
not scoped per post type — the two only share the key list. The block editor's
panel can break when plugin meta renders there — see
[gutenberg#23078](https://github.com/WordPress/gutenberg/issues/23078).

## Legacy keys still exposed (`Sync::REST_LEGACY_META_KEYS`)

On an upgraded site, deprecated keys can hold a post's only BeyondWords data
until audio is regenerated under v7. The allow-list spans both eras: the
legacy SpeechKit keys (`speechkit_generate_audio`, `speechkit_project_id`,
`speechkit_podcast_id`, `speechkit_error_message`, `_speechkit_link`) and the
pre-v7 BeyondWords `beyondwords_podcast_id`. The block editor reads them in the
authenticated `edit` context as a fallback — the error notice, pending notice,
play/preview controls and the "Generate audio" toggle all read them (see the
components under [src/editor/components/](../src/editor/components/)).

`_speechkit_link` is the odd one out: no editor component renders it. Its real
consumer is server-side — `Meta::get_podcast_id()` in
[src/post/class-meta.php](../src/post/class-meta.php) parses the legacy `/a/`,
`/e/` or `/m/` player URL out of it via `get_post_meta()`, which needs no
`show_in_rest`.

## Hiding private meta from public REST (`Sync::REST_PRIVATE_META_KEYS`)

A handful of REST-exposed keys hold secrets or internal data: BeyondWords API
error strings, the audio preview token, and the legacy player URL.

`WP_REST_Meta_Fields` returns `show_in_rest` meta in the public `view` context
too, with **no capability check** — so an anonymous `GET /wp/v2/posts/:id`
would disclose them. Requesting the `edit` context is itself permission-gated
by the posts controller, so `Sync::hide_private_meta_from_rest()` keeps those
keys only for `edit` requests and strips them from every other response —
closing the anonymous disclosure while leaving the block editor unaffected.
`Sync::register_rest_meta_visibility()` adds the filter on `rest_api_init` —
once per compatible post type, as `rest_prepare_{$post_type}` — so it only
loads for REST requests.

See also: [legacy-meta-migration.md](./legacy-meta-migration.md) for how keys
moved between the `current` and `deprecated` sets.
