# Async REST / non-blocking API calls

Goal: keep blocking BeyondWords API requests off the hot paths — page render and
the save request.

## How it works

### Read caching

The editor-dropdown reads go through `Client::cached_get()`, which stores the
decoded response in a transient:
[src/api/class-client.php](../src/api/class-client.php).

- `Client::get_languages()`
- `Client::get_voices()` (and `get_voice()`, which filters that list)
- `Client::get_project()`
- `Client::get_video_settings()`
- `Client::get_summarization_settings_templates()`
- `Client::get_video_settings_templates()`

These power both the block-editor REST proxies and the classic-editor render, so
both get fewer and more resilient API calls.

- The cache key is salted with the project ID + API key, so changing either
  invalidates implicitly — no flush needed, which matters on object-cache hosts
  (e.g. VIP) where `_transient_*` rows can't be enumerated.
- Successful 2xx array responses are cached for `Client::CACHE_TTL` (15
  minutes).
- Failures are negative-cached: an empty array is stored for the shorter
  `Client::CACHE_TTL_ON_ERROR` (2 minutes). Because `cached_get()` returns any
  value it finds (`false !== $cached`), that stored `[]` short-circuits the next
  fetch, so an unreachable API is probed at most once per interval rather than
  on every render.
- Requests use `Client::DEFAULT_REQUEST_TIMEOUT` (3 seconds), except voices,
  which uses `Client::VOICES_REQUEST_TIMEOUT` (8 seconds) — it is the one slow
  endpoint, and the default would abandon (and then negative-cache) many
  cold-cache fetches.

### Background create/update (VIP only)

The blocking `create_audio`/`update_audio` call on `wp_after_insert_post` made
saving slow. `Sync::on_add_or_update_post()` defers `generate_audio_for_post()`
to a WP-Cron single event (`Sync::GENERATE_AUDIO_CRON_HOOK`) so the save request
returns immediately: [src/post/class-sync.php](../src/post/class-sync.php).

- **Gated to WordPress VIP only** (`Sync::is_async_generation_enabled()`), detected
  by VIP-only cron symbols (`Automattic\WP\Cron_Control\Main`,
  `wpcom_vip_schedule_single_event`, `VIP_GO_APP_ENVIRONMENT`). Off-VIP, WP-Cron
  is traffic-triggered and unreliable, so we keep the synchronous call. VIP runs
  WP-Cron from a real system cron, so the deferred job fires promptly.
- Overridable via the `beyondwords_async_generate_audio` filter.
- The cron job re-runs `generate_audio_for_post()`, which re-checks eligibility
  and passes the API response to `process_response()` → writes
  `beyondwords_project_id`, `beyondwords_content_id` and
  `beyondwords_preview_token` exactly as the synchronous path does. Language and
  voice meta are deliberately not copied back from the response: those keys hold
  explicit editor choices. No webhook/poll needed.
- Error meta is written when the job runs (≈1 cron tick later) rather than
  inline, so the editor surfaces an API error on its next load.

### Background delete (VIP only)

The trash and permanent-delete handlers use the same mechanism.
`Sync::on_trash_post()` and `Sync::on_delete_post()` call
`delete_audio_for_post_or_defer()`, which on VIP schedules
`Sync::DELETE_AUDIO_CRON_HOOK` via `schedule_audio_deletion()` and off VIP
deletes inline.

- The cron event carries the BeyondWords project + content IDs, not a post ID:
  the meta is wiped (trash) or the post row is gone (permanent delete) by the
  time the job runs. `Sync::delete_audio_by_ids()` handles the event and calls
  `Client::delete_audio_by_ids()`.
- Both handlers first call `unschedule_audio_generation()` to drop any pending
  generate event for the post.
- The save-time "delete content" branch in `Sync::on_add_or_update_post()`
  (`beyondwords_delete_content` meta) still deletes synchronously.

### Bulk generation

`Sync::bulk_generate_audio_for_posts()` backs the posts-list "Generate audio"
bulk action ([src/posts-list/class-bulk-edit.php](../src/posts-list/class-bulk-edit.php)).
It sets `beyondwords_generate_audio` meta on every selected post, then:

- On VIP, queues one cron event per post and returns immediately.
- Off VIP, generates inline up to `Sync::BULK_GENERATE_SYNC_LIMIT` (10) — each
  post is a blocking API call, so the cap keeps the batch inside execution
  limits. Posts without a content ID are processed first, so re-running the
  action makes forward progress. The remainder is reported as a `deferred`
  count and surfaced by `Notices::deferred_notice()`
  ([src/posts-list/class-notices.php](../src/posts-list/class-notices.php));
  their generate flag is already set, so re-running the action completes them.

## Known limitations

### Classic-editor server-side reads

The block editor is fully async (it populates dropdowns from the
`beyondwords/v1` REST proxies via React). The classic editor still calls the API
**server-side during metabox render**:

- `SelectVoice::element()` → `get_languages()` + `get_voices()`
- `SettingsFields::render_content_section()` → `get_summarization_settings_templates()`
- `SettingsFields::render_format_section()` → `get_video_settings_templates()` + `get_video_settings()`

Read caching (above) means these only hit the network on a cold cache, so the
per-render blocking is largely mitigated.

Removing it entirely is partly done.
[src/editor/components/select-voice/classic-metabox.js](../src/editor/components/select-voice/classic-metabox.js)
already fetches from the REST proxies — `hydrate()` re-fetches the saved
language's voices on load, and `applyProjectDefaultLanguage()` fetches
`/projects/{id}` when Customize is switched on — so the model/voice dropdowns
could be rendered empty and filled client-side.
The language list has no client-side fetch, and
[src/editor/components/settings-fields/classic-metabox.js](../src/editor/components/settings-fields/classic-metabox.js)
does no fetching at all, so its template and video-size dropdowns would need one
written from scratch.

Not a priority: classic-on-VIP is rare and the rewrite touches the render + JS
of both components plus their Cypress specs. The REST proxies it needs already
exist (`/languages`, `/languages/{code}/voices`,
`/summarization-settings-templates`, `/video-settings-templates`,
`/projects/{id}`, `/projects/{id}/video-settings`).
