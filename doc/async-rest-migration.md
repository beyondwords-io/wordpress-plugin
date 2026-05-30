# Async REST migration (planned — separate PR)

Tracked here so it isn't lost. **Not implemented yet.** Goal: no blocking
requests to the BeyondWords REST API anywhere — block editor or classic — and
make content create/update requests run in the background on WordPress VIP.

## Why

Today the classic-editor metabox renders synchronously against the BeyondWords
API. On every `post.php` / `post-new.php` load, the Voice + Settings Fields
sections each call the API during render:

- `SelectVoice::element()` → `Client::get_languages()`, `Client::get_voices()`
- `SettingsFields::render_content_section()` → `Client::get_summarization_settings_templates()`
- `SettingsFields::render_format_section()` → `Client::get_video_settings_templates()`, `Client::get_video_settings()`

That's ~5 blocking HTTP round-trips per edit-screen load, none cached. If the API
is slow or down, the edit screen stalls or the dropdowns render empty. VIP
guidelines require caching remote requests and not blocking page generation on
them.

The block editor already fetches these via the `beyondwords/v1` REST proxies
(async from JS), so the data flow exists — but the proxies themselves call the
API synchronously inside the REST request, and the classic editor doesn't use
them at all.

## Scope

1. **Classic editor reads → async REST.** Move the Voice and Settings Fields
   dropdown population off server-side render and onto the same `beyondwords/v1`
   REST proxies the block editor uses, fetched from `classic-metabox.js`
   (`select-voice/classic-metabox.js` already does this for voices — extend the
   pattern to templates/sizes). The metabox renders immediately with empty
   selects that populate on load.

2. **Cache the proxy responses.** Wrap `Client::get_*` settings/template/voice
   responses in short-lived transients (these change rarely). Bust on relevant
   settings save. Belt-and-braces even once reads are async.

3. **Background create/update on VIP.** `Sync` / `Client::create_audio()` /
   `update_audio()` currently block the `save_post` request. Where VIP's async
   helpers exist, offload these to the background:
   - Gate VIP-only helpers behind `function_exists()` so non-VIP installs fall
     back to the current synchronous path.
   - Candidate approaches: a non-blocking `wp_remote_post( …, [ 'blocking' => false ] )`
     for fire-and-forget, or scheduling via VIP's cron/async offload so the
     response (content id, preview token, voice ids) is still captured and
     written back to post meta.
   - Capturing the response is the hard part for a non-blocking call — likely
     needs a follow-up "fetch status" poll (the Content ID fetch button already
     does a version of this) or a webhook. Design before building.

## Risks / open questions

- Non-blocking create/update means the response isn't available inline; the post
  meta write-back (`Sync` copies `content_id`, `preview_token`, voice ids) must
  move to a poll or webhook. This changes the UX (audio "pending" until the
  background job lands) and needs product sign-off.
- Transient cache invalidation: which settings changes must bust which caches.
- Multisite / object-cache behaviour for the transients.
