# Async REST / non-blocking API calls

Goal: keep blocking BeyondWords API requests off the hot paths — page render and
the save request.

## Done (this PR)

### Read caching

The editor-dropdown reads (`Client::get_languages()`, `get_voices()`,
`get_summarization_settings()`, `get_summarization_settings_templates()`,
`get_video_settings()`, `get_video_settings_templates()`) now cache successful
responses in transients (`Client::cached_get()`, 15-min TTL). These power both
the block-editor REST proxies and the classic-editor render, so both get fewer
and more resilient API calls.

- The cache key is salted with the project ID + API key, so changing either
  invalidates implicitly — no flush needed, which matters on object-cache hosts
  (e.g. VIP) where `_transient_*` rows can't be enumerated.
- Only 2xx array responses are cached; errors retry on the next call.

### Background create/update (VIP only)

The blocking `create_audio`/`update_audio` call on `save_post` made saving slow.
`Sync::on_add_or_update_post()` now defers `generate_audio_for_post()` to a
WP-Cron single event (`Sync::GENERATE_AUDIO_CRON_HOOK`) so the save request
returns immediately.

- **Gated to WordPress VIP only** (`Sync::is_async_generation_enabled()`), detected
  by VIP-only cron symbols (`Automattic\WP\Cron_Control\Main`,
  `wpcom_vip_schedule_single_event`, `VIP_GO_APP_ENVIRONMENT`). Off-VIP, WP-Cron
  is traffic-triggered and unreliable, so we keep the synchronous call. VIP runs
  WP-Cron from a real system cron, so the deferred job fires promptly.
- Overridable via the `beyondwords_async_generate_audio` filter.
- The cron job re-runs `generate_audio_for_post()`, which re-checks eligibility
  and captures the API response → writes `content_id` / `preview_token` / voice
  meta exactly as the synchronous path does. No webhook/poll needed.
- Error meta is written when the job runs (≈1 cron tick later) rather than
  inline, so the editor surfaces an API error on its next load.

## Deferred (follow-up)

### Classic-editor async reads

The block editor is already fully async (it populates dropdowns from the
`beyondwords/v1` REST proxies via React). The classic editor still calls the API
**server-side during metabox render**:

- `SelectVoice::element()` → `get_languages()` + `get_voices()`
- `SettingsFields::render_content_section()` → `get_summarization_settings_templates()`
- `SettingsFields::render_format_section()` → `get_video_settings_templates()` + `get_video_settings()`

Read caching (above) means these only hit the network on a cold cache, so the
per-render blocking is largely mitigated. Fully removing it means rendering empty
`<select>`s and populating them from the REST proxies in `classic-metabox.js`
(extending the pattern `select-voice/classic-metabox.js` already uses for voices
on language change, to also run on load and to cover templates/sizes).

Deferred because classic-on-VIP is not a priority and the rewrite touches the
render + JS of both components plus their Cypress specs. The REST proxies it
needs already exist (`/languages`, `/languages/{code}/voices`,
`/summarization-settings-templates`, `/video-settings-templates`,
`/projects/{id}/video-settings`).

### Background delete

The save-time "delete content" branch and the trash/delete handlers still call
the API synchronously. Lower priority (delete isn't on the common save path), but
could move to the same VIP cron mechanism.
