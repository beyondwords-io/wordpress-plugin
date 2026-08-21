# BeyondWords plugin — code review (Fable 5 Max)

_Security · robustness · WordPress VIP (blocking requests & caching)_

| | |
|---|---|
| **Reviewer model** | Claude Fable 5 (Max effort), adversarially verified |
| **Date** | 2026-07-07 |
| **Baseline** | `origin/main` @ `9563be8` (review ran vs `3e0fd44`; findings in files changed since were re-verified) |
| **Method** | 21 file-cluster finders → independent adversarial verifier per finding → consolidation |
| **Confirmed findings** | 82 (medium+ confidence) → 73 items after dedupe |

## How to use this document

Work top-down (P0 → P3). Each item has a checkbox and a fenced **paste-ready prompt** — copy it straight into Claude Code (Opus 4.8) in VS Code to fix that one item. Tags:

- **🔁 outstanding (prior P#)** — this was also flagged in the earlier Opus 4.8 review and is still present, so it was most likely deprioritised. Re-decide it explicitly.
- **🆕 new** — surfaced by this Fable 5 run and not in the prior review.
- Priority: **P0** security / data-loss · **P1** robustness fatals + VIP blocking/caching + elevated security · **P2** logic/robustness/UX + moderate VIP · **P3** low / cosmetic / dead-code / i18n / edge.

> Severities are the verifier's corrected ratings; a few security items Fable rated `low` are **elevated** here because security is your stated driver — those are marked _(elevated)_ and worth confirming first.

## Summary

| Priority | Items | Focus |
|---|---|---|
| **P0** | 1 | security / data-loss |
| **P1** | 16 | robustness fatals · VIP blocking/caching · security |
| **P2** | 21 | logic / robustness / UX · moderate VIP |
| **P3** | 35 | low · cosmetic · dead code · i18n · edge |
| **Resolved** | 2 | fixed on main since the prior review (see end) |

### 🔁 Outstanding from the prior Opus review (likely deprioritised)

- **P0.1** (prior P2) — Dropdown bulk actions mutate and remotely delete audio without per-post capability checks — `src/posts-list/class-bulk-edit.php`
- **P1.1** (prior P10) — v7 migration gate never closes for the shipped '7.0.0-dev-2.0' version, so ~40 uncached DB queries run on every request — `src/core/class-updater.php`
- **P1.2** (prior P3) — TypeError fatal on classic edit screen when voices API returns a JSON error object — `src/editor/components/select-voice/class-select-voice.php`
- **P2.1** (prior P9) — TypeError fatal in error_message_from_response() when the API error body's `errors` member is not an array of arrays — `src/api/class-client.php`
- **P2.2** (prior P14) — Fetch fired with undefined restUrl before settings store resolves; failure then persists spurious error meta to the DB — `src/editor/components/content-id/index.js`
- **P2.3** (prior P16) — Unchecking Customize does not invalidate in-flight voices fetches, so a late response re-populates the hidden Voice select and save() persists meta the user reverted — `src/editor/components/select-voice/classic-metabox.js`
- **P3.1** (prior P19) — array_fill_keys() TypeError (site-wide fatal) if legacy speechkit_select_post_types is a truthy non-array — `src/core/class-updater.php`
- **P3.2** (prior P20) — strlen() on wp-config override constants fatals under strict_types if the constant is defined as a non-string — `src/core/class-urls.php`
- **P3.3** (prior P15) — No loading/failure state for the settings-store key lists: Remove stays disabled and the Copy payload is empty with literal 'undefined' lines — `src/editor/components/inspect-panel/index.js`
- **P3.4** (prior P26) — Sidebar link onClick is missing event.preventDefault(), so clicking also performs the '#beyondwords-plugin-sidebar' hash navigation — `src/editor/components/open-sidebar/index.js`
- **P3.5** (prior P24) — gethostbyname() called with a full URL: blocking DNS lookup that can never return an IP — `src/site-health/class-site-health.php`

---

## P0 — Security / data-loss (fix first)

### P0.1 — Dropdown bulk actions mutate and remotely delete audio without per-post capability checks

- [ ] 🔁 **outstanding** — prior P2 · `high` · _severity elevated (security driver)_ · `src/posts-list/class-bulk-edit.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-bulk-edit.php (around line 274): Dropdown bulk actions mutate and remotely delete audio without per-post capability checks.

Problem: handle_bulk_generate_action() (lines 252-294) and handle_bulk_delete_action() (lines 303-330) act on $object_ids unfiltered. WordPress core's edit.php routes custom bulk actions through the handle_bulk_actions-edit-{post_type} filter after only check_admin_referer('bulk-posts') and the coarse current_user_can($ptype->cap->edit_posts) gate at the top of edit.php - core never checks per-post edit_post capability for custom actions (it only does so for built-ins like trash). The generate handler writes beyondwords_generate_audio='1' meta (line 274) and fires API generation (line 278) for every ID; the delete handler (line 321) sends a remote batch-delete to the BeyondWords API and wipes all ~38 beyondwords/speechkit meta keys per post. The AJAX sibling save_bulk_edit() in this same class explicitly filters $post_ids by current_user_can('edit_post', $post_id) (lines 144-149) with a comment explaining exactly this risk - the redirect-based handlers omit the equivalent guard, and the PHPUnit suite covers only the AJAX path's cap filtering.
Trigger: A logged-in Contributor or Author (both hold edit_posts, so they can load edit.php and read the bulk-posts nonce from their own list-table form) requests edit.php?action=beyondwords_delete_audio&post[]=<ID-of-another-authors-published-post>&_wpnonce=<their-own-bulk-posts-nonce>. Core intval()s the IDs and passes them straight to the filter; handle_bulk_delete_action() runs the remote delete and meta wipe. Same with action=beyondwords_generate_audio for meta writes + API generation. The list-table UI hides checkboxes for uneditable posts, but the crafted request bypasses that.
Impact: Privilege-boundary violation: low-privilege users can irreversibly delete BeyondWords audio (remote API side effect) and erase all plugin meta, or force audio generation/regeneration (billable API calls), on posts they are not allowed to edit - including other authors' published content.
Fix: At the top of both handlers, filter the IDs exactly like the AJAX path: $object_ids = array_values( array_filter( $object_ids, static fn( $id ): bool => current_user_can( 'edit_post', $id ) ) ); and bail (return $redirect with a zero count) when the result is empty.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

---

## P1 — Robustness fatals · VIP blocking/caching · security

### P1.1 — v7 migration gate never closes for the shipped '7.0.0-dev-2.0' version, so ~40 uncached DB queries run on every request

- [ ] 🔁 **outstanding** — prior P10 · `high` · `src/core/class-updater.php` · +1 duplicate finding(s) merged

```text
In the BeyondWords WordPress plugin, src/core/class-updater.php (around line 43): v7 migration gate never closes for the shipped '7.0.0-dev-2.0' version, so ~40 uncached DB queries run on every request.

Problem: Updater::run() gates the v7 migrations on version_compare( $version, '7.0.0', '<' ). speechkit.php defines BEYONDWORDS__PLUGIN_VERSION as '7.0.0-dev-2.0', and version_compare('7.0.0-dev-2.0', '7.0.0', '<') === true (verified by executing php -r). Line 53 stores that same dev string back into beyondwords_version, so the gate at line 43 is true on EVERY request forever. Each request therefore runs: (a) delete_deprecated_options() — 39 delete_option()/delete_site_option() calls, and WP core's delete_option() unconditionally issues `SELECT autoload FROM wp_options WHERE option_name = %s` even when the option does not exist (verified in wp-includes/option.php:1207); (b) migrate_disabled_to_embed_none() — a WP_Query with a postmeta JOIN (meta_key = 'beyondwords_disabled' AND meta_value = '1', an unindexed meta_value filter) at plugin-include time; (c) migrate_preselect_format() — one option read. The file header's claim 'cheap when nothing's changed (the version compare short-circuits)' is false for this build; the migrate_preselect_format docblock even acknowledges the -dev gate fires on every load, but only that one function was made cheap — the other two were not.
Trigger: Any request of any kind (front-end page view, REST, AJAX, cron, admin) on an install running the current 7.0.0-dev-2.0 build, after the first run has stored '7.0.0-dev-2.0' in beyondwords_version. Plugin::init() runs Updater::run() at plugin-include time on every request; the stored dev version stays < '7.0.0'.
Impact: ~40 uncached primary-DB SELECTs plus an uncached slow postmeta-JOIN query added to every single page load, including anonymous front-end traffic. Direct violation of WordPress VIP performance rules (uncached queries and a WordPress.DB.SlowDBQuery meta_query pattern on a hot path). On large publisher databases the meta_value scan of wp_postmeta per page view is expensive; on multisite it also fires 39 network-meta lookups per request.
Fix: Make the migration gate close for dev builds: only run migrations when the stored version differs from BEYONDWORDS__PLUGIN_VERSION (e.g. `if ( $version === BEYONDWORDS__PLUGIN_VERSION ) return;` at the top of run()), or record a one-shot 'beyondwords_migrated_7_0_0' flag option and check it before running the v7 block, instead of relying on version_compare against '7.0.0' which a '-dev' suffixed version never satisfies.

Note: the same root cause was independently reported by other finders as: v7 migration gate never closes on dev builds: ~40 uncached DB queries run on every request, front end included. Fixing the above resolves them together.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.2 — TypeError fatal on classic edit screen when voices API returns a JSON error object

- [ ] 🔁 **outstanding** — prior P3 · `high` · `src/editor/components/select-voice/class-select-voice.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/class-select-voice.php (around line 205): TypeError fatal on classic edit screen when voices API returns a JSON error object.

Problem: get_voices_for_language() validates only the TOP level of the API response ('return is_array( $voices ) ? $voices : [];' at line 205), but the elements are never checked. element() then calls render_model_select() -> language_models( $voices ) (line 271 -> 430), which passes each element to voice_model_key( array $voice ) (line 435 -> 407). Under declare(strict_types=1), a non-array element (e.g. a string) thrown at the 'array' type declaration is an immediate TypeError. Client::get_voices() -> cached_get() (src/api/class-client.php:530-550) returns json_decode(body, true) UNCONDITIONALLY -- the <300 status check only gates whether the payload is cached, not whether it is returned. So a non-2xx response whose body is the BeyondWords API's own documented error shape {"message": "..."} decodes to ['message' => '<string>'], passes the top-level is_array() check, and the string element fatals in voice_model_key(). (Client::error_message_from_response() at class-client.php:558-580 explicitly documents that the API returns bodies shaped {"message": ...} and {"errors": [...]}.) Note the {"errors": [...]} shape does not fatal (elements are arrays); the common {"message": "..."} shape does. A 2xx response with a non-list JSON object would additionally be CACHED for 15 minutes (class-client.php:546), extending the breakage.
Trigger: A post has beyondwords_body language customization saved (beyondwords_language_code post meta non-empty, so line 204 makes the API call), and the BeyondWords API (or an intermediary) answers the /organization/voices request with a JSON object body such as {"message": "Too many requests"} -- e.g. HTTP 429 rate limiting, 5xx maintenance, or 401 after key revocation. Editor opens the post in the classic editor: element() -> render_model_select() -> language_models() -> voice_model_key('Too many requests') -> TypeError: Argument #1 ($voice) must be of type array, string given.
Impact: Hard fatal mid-metabox render kills wp-admin/post.php output for every classic-editor load of any customized post for the duration of the API incident (429/5xx bodies are not cached, so every page load re-triggers it; a 401 fatals once then Plugin::init's has_valid_api_connection() gate hides the metabox). The edit screen is left half-rendered and unusable. A malformed 2xx object body would be cached and fatal for 15 minutes per language.
Fix: Harden the shape validation in get_voices_for_language() (and ideally get_languages()) to element level, e.g. return array_values( array_filter( $voices, 'is_array' ) ); after the is_array() check. Alternatively (or additionally) loosen voice_model_key() to accept mixed and return STANDARD_MODEL_KEY for non-arrays, matching the tolerant JS mirror voiceModelKey() in classic-metabox.js which guards with 'voice &&'.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.3 — Slot components imported from @wordpress/editor are undefined on WP 5.9-6.5, which the plugin declares as supported — entire block-editor UI fails to render

- [ ] 🆕 **new** · `high` · `src/editor/block/document-setting/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/block/document-setting/index.js (around line 5 (also src/editor/block/sidebar/index.js:5 and src/editor/block/prepublish/index.js:5)): Slot components imported from @wordpress/editor are undefined on WP 5.9-6.5, which the plugin declares as supported — entire block-editor UI fails to render.

Problem: document-setting/index.js imports `PluginDocumentSettingPanel`, sidebar/index.js imports `PluginSidebar` and `PluginSidebarMoreMenuItem`, and prepublish/index.js imports `PluginPrePublishPanel` — all from `@wordpress/editor`. webpack.config.js spreads the wp-scripts default config (dependency-extraction plugin intact), so these compile to the `wp.editor.*` globals at runtime. Those components were only moved into the `@wordpress/editor` package in WordPress 6.6 (previously they lived in `@wordpress/edit-post`); on WP 6.5 and below, `wp.editor.PluginDocumentSettingPanel` etc. are `undefined`. The plugin declares `Requires at least: 5.9` (speechkit.php line 25, readme.txt line 7), and src/editor/block/class-assets.php enqueues build/index.js on every compatible post-type editor screen with no WP version gate — so the broken path is reached on every supported install below 6.6.
Trigger: Open the block editor for any compatible post type on WordPress 5.9 through 6.5 (all within the plugin's declared support range). Each of the three registered plugins renders JSX whose element type is `undefined`, throwing 'Element type is invalid: expected a string ... but got: undefined'. @wordpress/plugins' per-plugin error boundary catches each throw and logs "The plugin 'beyondwords-document-sidebar' failed to render" (and likewise for the prepublish and plugin-sidebar registrations).
Impact: On WP 5.9-6.5 the entire BeyondWords block-editor integration silently disappears: no document-settings panel, no pre-publish Generate Audio control, no sidebar (and its Voice/Player settings). Users on those versions cannot control audio generation from the block editor at all; the only symptom is console errors.
Fix: Pick one: (a) if v7 intends to require modern WP, bump `Requires at least:` to 6.6 in speechkit.php and readme.txt (and optionally guard the enqueue in class-assets.php with a version_compare) so the declared range matches the code; or (b) keep 5.9 support by resolving each slot component with a fallback at module scope, e.g. `const PluginDocumentSettingPanel = wp.editor.PluginDocumentSettingPanel ?? wp.editPost.PluginDocumentSettingPanel;` (dependency extraction will need `wp-edit-post` added as a dependency, e.g. via an import from '@wordpress/edit-post').

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.4 — Classic-editor metabox render makes up to 3 blocking remote API calls with 30s timeouts that are never cached on failure

- [ ] 🆕 **new** · `high` · `src/editor/components/settings-fields/class-settings-fields.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/settings-fields/class-settings-fields.php (around line 358): Classic-editor metabox render makes up to 3 blocking remote API calls with 30s timeouts that are never cached on failure.

Problem: render_content_section() (line 358: Client::get_summarization_settings_templates()) and render_format_section() (line 392: Client::get_video_settings_templates(); line 395: Client::get_video_settings()) run during Metabox::render_meta_box_content() on every classic post.php/post-new.php page render. Following into src/api/class-client.php: cached_get() (lines 530-550) only stores a transient when the response is a 2xx WITH an array body — WP_Error (timeout/unreachable host), 5xx, and non-JSON responses are never negative-cached, and build_args() (line 492) sets 'timeout' => 30 with the VIP WordPressVIPMinimum.Performance.RemoteRequestTimeout sniff explicitly suppressed. A 401 self-heals (call_api deletes the beyondwords_valid_api_connection option that gates the metabox in Plugin::init), but a network failure or 5xx does NOT clear that flag, so the metabox keeps rendering and keeps re-issuing the requests on every load.
Trigger: beyondwords_valid_api_connection option is set (site previously validated), then the BeyondWords API becomes slow/unreachable (network blackhole, DNS failure, API outage/5xx). An editor opens any classic-editor edit screen for a compatible post type: render fires 3 sequential wp_remote_request() calls, each blocking up to 30 seconds, and because failures are never cached the identical 3-call stall repeats on every subsequent edit-screen load. Even in the healthy case, a cold/expired transient adds 3 sequential remote round-trips to the admin page render every 15 minutes.
Impact: Up to ~90 seconds of blocked PHP execution per classic edit-screen render, repeated on every load for the duration of an API incident — ties up PHP-FPM workers (VIP platform concern: uncached remote requests on a render path with a long timeout), effectively making wp-admin post editing unusable whenever the remote API is degraded.
Fix: In Client::cached_get(), negative-cache failures with a short TTL (e.g. set_transient($key, [] or a sentinel, 1-2 * MINUTE_IN_SECONDS) when the request errors or returns non-2xx) so an outage costs at most one probe per interval; and use a short timeout (<= 3s, per VIP guidance / vip_safe_wp_remote_get semantics) for these render-path GET requests instead of inheriting the 30s write-path timeout. Optionally also skip the template/size fetches in render_content_section()/render_format_section() when Settings\Utils::has_api_creds() is false.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.5 — Fatal Error: WP_Error instance methods called statically in get_http_response_body_from_post_meta()

- [ ] 🆕 **new** · `high` · `src/post/class-meta.php` · +2 duplicate finding(s) merged

```text
In the BeyondWords WordPress plugin, src/post/class-meta.php (around line 426): Fatal Error: WP_Error instance methods called statically in get_http_response_body_from_post_meta().

Problem: When post meta (e.g. legacy `speechkit_response`) unserializes to a WP_Error object, the code calls `$post_meta::get_error_code()` and `$post_meta::get_error_message()`. `WP_Error::get_error_code()` / `get_error_message()` are non-static instance methods; the `$obj::method()` syntax performs a static call, and on PHP 8 this throws an uncaught `Error: Non-static method WP_Error::get_error_code() cannot be called statically` (verified on PHP 8.4). The WP_Error branch exists precisely because 3.6.1-era plugin versions stored failed HTTP responses as WP_Error objects in `speechkit_response`, so this branch is reachable with real legacy data.
Trigger: A legacy post whose `speechkit_response` meta holds a serialized WP_Error (a failed generation from plugin ~3.x) and which has no `beyondwords_content_id`, no `beyondwords_podcast_id`/`speechkit_podcast_id`, and no matching `_speechkit_link`. Then any of: (a) a front-end singular page view — `Player::render_player()` (src/player/class-player.php:131-132) calls `Meta::get_project_id($post->ID)` (non-strict) and `Meta::get_content_id($post->ID, true)`, both of which reach `get_http_response_body_from_post_meta($post_id, 'speechkit_response')` via class-meta.php:214/329; (b) saving the post — `Sync::generate_audio_for_post()` calls `Meta::get_content_id()` (class-sync.php:206); (c) trashing/deleting the post — `on_trash_post()`/`on_delete_post()` call `Meta::has_content()` → `get_content_id()` → `get_podcast_id()`.
Impact: Uncaught PHP Error = white-screen fatal: front-end 500s on page views of affected legacy posts, failed saves, and the post becomes impossible to trash or delete while the meta row exists.
Fix: Use instance-call syntax: `sprintf( self::WP_ERROR_FORMAT, $post_meta->get_error_code(), $post_meta->get_error_message() )`.

Note: the same root cause was independently reported by other finders as: Fatal Error: WP_Error instance methods called statically on legacy speechkit_response meta (reached from every Client audio method); Fatal Error: non-static call syntax on WP_Error instance methods when legacy meta holds a WP_Error. Fixing the above resolves them together.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.6 — Bulk generate makes one blocking 30s-timeout remote API call per selected post - unbounded N+1 HTTP loop in a single admin request

- [ ] 🆕 **new** · `high` · `src/posts-list/class-bulk-edit.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-bulk-edit.php (around line 278): Bulk generate makes one blocking 30s-timeout remote API call per selected post - unbounded N+1 HTTP loop in a single admin request.

Problem: handle_bulk_generate_action() loops over every selected post calling \BeyondWords\Post\Sync::generate_audio_for_post() (lines 277-283). For each post that resolves to a synchronous wp_remote_request() POST/PUT against the BeyondWords API (Client::create_audio()/update_audio() via call_api()), with 'blocking' => true and 'timeout' => 30 set in Client::build_args() (src/api/class-client.php:485-494, where the VIP RemoteRequestTimeout sniff is explicitly phpcs:ignore'd). There is no chunking, no batch endpoint (unlike the delete path, which uses one batched /content/batch_delete request), and the handler never consults Sync::is_async_generation_enabled() - the cron-offload path built specifically for VIP - so even on VIP the loop runs inline in the edit.php redirect request.
Trigger: Admin selects posts on edit.php (Screen Options allows up to 999 per page, plus the select-all checkbox) and runs Bulk actions > 'Generate audio'. N sequential remote calls execute in one request; with a slow or unresponsive API each call can block up to 30s, so even ~3-20 posts can exceed the typical 60s PHP/VIP execution limit.
Impact: Request killed mid-loop: some posts get audio, the redirect never happens, the user sees a timeout/blank page and no notice, and re-running duplicates API work. On VIP this is a platform violation (unbounded blocking remote requests on a request path) and ties up an app container for minutes.
Fix: Dispatch generation asynchronously: set the beyondwords_generate_audio meta for each post (cheap) and schedule per-post (or chunked) background jobs via the existing Sync::is_async_generation_enabled()/cron path, or add a batched generate endpoint mirroring batch_delete_audio(). At minimum, hard-cap the number of posts processed synchronously and lower the per-call timeout for this path.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.7 — getVoices resolver caches per-language but writes to a single shared 'voices' slot, serving stale voices for previously fetched languages

- [ ] 🆕 **new** · `high` · `src/settings/store/index.js` · +1 duplicate finding(s) merged

```text
In the BeyondWords WordPress plugin, src/settings/store/index.js (around line 45-50 (selectors at 27-32 share the root cause)): getVoices resolver caches per-language but writes to a single shared 'voices' slot, serving stale voices for previously fetched languages.

Problem: @wordpress/data memoises resolver resolution per selector-args (EquivalentKeyMap keyed on [languageCode]), but the resolver `getVoices( languageCode )` stores every result into the one shared `voices` state key, and the auto-generated selector `( state ) => state.voices` (lines 27-32) ignores its argument entirely. Once `getVoices(['en_XX'])` has finished, re-selecting `getVoices('en_XX')` never re-runs the resolver, yet the shared slot may since have been overwritten by a different language's fetch. Nothing in src/ ever calls `invalidateResolution` (verified by grep), so the stale entry is permanent for the editor session. The consumer that makes this reachable is src/editor/components/settings-panel/voice-section.js lines 101-119: `s( 'beyondwords/settings' ).getVoices( languageCode )` plus `isResolving( 'getVoices', [ languageCode ] )` — isResolving is correctly per-args, so no spinner shows for the cache-hit case and the stale list renders immediately. The same shared-slot design also creates an uncancelled in-flight race: two quick language changes leave two fetches in flight and whichever response lands last wins the slot, regardless of the currently selected language. (`getProject`/`getVideoSizes` share the pattern but are currently safe only because projectId never changes within an edit session.)
Trigger: Block editor, BeyondWords sidebar Voice panel: enable Customize with Language A (voices for A fetched, resolution for ['A'] marked finished), change Language to B (shared `voices` slot overwritten with B's list), then change Language back to A. The resolution cache for ['A'] is already 'finished', so no refetch is dispatched and `isResolving('getVoices',['A'])` stays false — the selector returns the slot still holding language-B voices. Race variant: switch A→B before A's response arrives; if A's response lands after B's, B stays selected while the slot holds A's voices.
Impact: The Voice/Model dropdowns list voices belonging to a different language than the one selected, with no spinner or other cue. Because `setModel`/`setVoiceId` in voice-section.js persist whatever id is picked from that stale list, the post ends up with e.g. `beyondwords_language_code: 'en_XX'` plus a French voice id in `beyondwords_body_voice_id` — corrupted audio settings sent to the BeyondWords API on generate.
Fix: Key the cached data by the resolver argument so state matches the per-args resolution cache: store `voices` as a map (`voices: {}`), have the resolver dispatch e.g. `{ type: 'SET_VOICES', languageCode, value }` merged as `{ ...state.voices, [ languageCode ]: value || [] }`, and hand-write the selector `getVoices( state, languageCode ) => state.voices[ languageCode ] ?? []`. Apply the same arg-keying to `getProject( projectId )` and `getVideoSizes( projectId )` for consistency (they share the flaw, currently masked by projectId being constant per session).

Note: the same root cause was independently reported by other finders as: Voices list is a single shared store bucket: stale-response race can show and store voices from the wrong language. Fixing the above resolves them together.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.8 — VIP: 30-second timeout on blocking API requests that run during admin page render and post save (VIP timeout sniff explicitly suppressed)

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 492): VIP: 30-second timeout on blocking API requests that run during admin page render and post save (VIP timeout sniff explicitly suppressed).

Problem: build_args() sets `'timeout' => 30` for every request the client makes, with a `phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout` suppressing the VIP sniff. VIP requires remote requests on page-generating paths to use a short bounded timeout (max ~3s, 1s recommended; vip_safe_wp_remote_get for GETs). These 30s requests run synchronously on real render paths: the classic-editor edit screen renders SelectVoice::element() (get_languages + get_voices, src/editor/components/select-voice/class-select-voice.php:88-89) and SettingsFields::render_content_section()/render_format_section() (get_summarization_settings_templates, get_video_settings_templates, get_video_settings, src/editor/components/settings-fields/class-settings-fields.php:358,392,395) — up to 5 sequential blocking HTTP GETs on a cold cache; and off-VIP the create/update POST/PUT runs synchronously inside wp_after_insert_post (src/post/class-sync.php:463). Each call can hold a PHP worker for up to 30 seconds.
Trigger: BeyondWords API is slow or unresponsive while the 15-minute transients are cold: opening the classic post editor performs up to 5 back-to-back remote GETs, each allowed to block for 30s (~150s worst case, socket wait does not count toward max_execution_time on Linux). Saving a post off-VIP blocks the save request up to 30s per API call.
Impact: PHP workers pinned for up to 30s per request; on VIP this degrades the whole application under concurrency (worker exhaustion) whenever the third-party API is slow. Editors experience multi-minute hangs of wp-admin.
Fix: Drop the timeout to <=3s for the cached editor GETs (or accept a per-call timeout argument in build_args so cached_get passes 3 and only the content write path keeps a longer bound, e.g. 10s). Remove the phpcs:ignore once compliant.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.9 — Uninstall on multisite deletes no options at all — the BeyondWords API key survives uninstallation

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/core/class-uninstaller.php` · +1 duplicate finding(s) merged

```text
In the BeyondWords WordPress plugin, src/core/class-uninstaller.php (around line 53): Uninstall on multisite deletes no options at all — the BeyondWords API key survives uninstallation.

Problem: cleanup_plugin_options() has the same either/or flaw as php-core-3: `is_multisite() ? delete_site_option( $option ) : delete_option( $option )`. The plugin stores every option per-site via update_option()/register_setting() (verified: no site-option writes exist in src/), so on a multisite network the uninstall loop deletes rows from wp_sitemeta that were never created and leaves every real option — including the secret beyondwords_api_key and beyondwords_project_id — in wp_options / wp_N_options. Note also that uninstall.php runs once in the network context, so even a corrected delete_option() would clean only the main site; subsites are never iterated.
Trigger: Delete the plugin from the network admin of any multisite install (WP runs uninstall.php with is_multisite() true). Every delete_site_option() call returns false; no delete_option() ever runs.
Impact: Complete failure of uninstall cleanup on multisite: plugin credentials (API key) and all settings remain in the database after the user has removed the plugin. Retaining an API secret the user believes was deleted is a data-hygiene/security concern, and the function's return value (0) silently masks the failure.
Fix: Delete per-site options as the primary path: `delete_option( $option ); if ( is_multisite() ) { delete_site_option( $option ); }` — and, to fully honour multisite, iterate sites (get_sites() + switch_to_blog(), or a documented decision not to) so each site's options and postmeta are cleaned, not just the main site's.

Note: the same root cause was independently reported by other finders as: Multisite uninstall deletes zero options: delete_site_option() targets sitemeta but all options are stored per-site. Fixing the above resolves them together.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.10 — Unvalidated Content ID is interpolated raw into BeyondWords API URL paths (authenticated path/query injection with org API key)

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/editor/components/content-id/class-content-id.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/class-content-id.php (around line 119-125): Unvalidated Content ID is interpolated raw into BeyondWords API URL paths (authenticated path/query injection with org API key).

Problem: ContentId::save() persists $_POST['beyondwords_content_id'] to the beyondwords_content_id meta with only sanitize_text_field(), which permits '/', '.', '?', '#' and '&'. That meta is later read back verbatim (Meta::get_content_id(), src/post/class-meta.php:164-180) and interpolated unencoded into API URLs via sprintf('%s/projects/%d/content/%s', ...) in Client::get_content (src/api/class-client.php:128), Client::update_audio (:173) and Client::delete_audio (:195). Client::filter_http_request_args() (src/api/class-client.php:79-87) attaches the organization's X-Api-Key header to any outbound URL that merely starts with the API base URL, and path-suffix manipulation preserves that prefix, so the key is still attached. The block-editor write path has the same gap: Sync::register_meta() registers beyondwords_content_id with sanitize_callback sanitize_text_field (src/post/class-sync.php:319-331). By contrast, the plugin's own inspect REST route shows the intended charset: '[a-zA-Z0-9\-]+' (class-inspect-panel.php:331).
Trigger: A user with edit_post on any post (author, or contributor on their own draft) types a crafted value such as 'x/../../projects/999/content/abc?force=1' into the Content ID metabox field and saves. When audio generation or deletion runs (Sync::generate_audio_for_post sees a truthy content_id and calls Client::update_audio; the Remove flow calls Client::delete_audio), wp_remote_request() issues a PUT/DELETE/GET whose path — after cURL's default dot-segment normalization — is an attacker-chosen endpoint under the BeyondWords API host, with the org X-Api-Key header attached and attacker-chosen query parameters.
Impact: Author-level WordPress users can steer authenticated, org-API-key-scoped requests (GET/PUT/DELETE plus injected query strings) to arbitrary BeyondWords API endpoints — e.g. reading, updating or deleting content in other projects of the organization — a privilege boundary the plugin otherwise enforces (project id is %d-forced; the inspect route regex blocks these characters).
Fix: In ContentId::save(), validate the submitted value against the same charset the REST route enforces (e.g. preg_match('/^[a-zA-Z0-9-]*$/', $value), rejecting or blanking anything else) before update_post_meta(); apply the same validation as the sanitize_callback for beyondwords_content_id in Sync::register_meta(); and defensively rawurlencode() the content id at each sprintf URL construction site in src/api/class-client.php.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.11 — VIP performance: full Symfony DomCrawler DOM parse of post content on every singular pageview, before any enablement short-circuit

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/player/class-player.php`

```text
In the BeyondWords WordPress plugin, src/player/class-player.php (around line 186): VIP performance: full Symfony DomCrawler DOM parse of post content on every singular pageview, before any enablement short-circuit.

Problem: auto_prepend_player() (hooked to the_content at priority 1000000 for ALL post types) calls has_custom_player() before render_player()'s cheap Player::is_enabled() gate. When the content has no [beyondwords_player] shortcode — i.e. virtually every post on the site — has_custom_player() constructs `new \Symfony\Component\DomCrawler\Crawler( $content )` (a full libxml DOMDocument parse of the entire, unbounded post content) and runs two XPath queries. This happens on every uncached singular front-end request, including: posts with no BeyondWords audio at all, post types the plugin doesn't support, sites where the plugin is unconfigured (no API key/project ID), sites with Player UI = Disabled, and posts with Embed = None. All the cheap negative checks (is_enabled, project/content ID in Base::check) run only AFTER the expensive parse.
Trigger: Any front-end singular pageview (any post type) where the content does not contain the [beyondwords_player] shortcode — the common case. the_content @1000000 → auto_prepend_player() line 68 → has_custom_player() line 186 → full DOM parse + 2 XPath queries, regardless of whether a player could ever render.
Impact: Per-request CPU/memory cost proportional to post size on the hottest front-end path; on large posts / high-traffic VIP sites this is measurable avoidable render-time work on every uncached request, purely to detect a marker string that is absent ~always.
Fix: Short-circuit before parsing: (1) in auto_prepend_player(), check `get_post() instanceof WP_Post && self::is_enabled( $post )` (and ideally Base-level eligibility) before calling has_custom_player(); (2) in has_custom_player(), guard the Crawler with a cheap substring pre-check, e.g. `if ( ! str_contains( $content, 'data-beyondwords-player' ) && ! str_contains( $content, \BeyondWords\Core\Urls::get_js_sdk_url() ) ) { return false; }` so the DOM parse only runs when a marker is plausibly present.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.12 — Synchronous 30s-timeout remote DELETE per post on trash/delete hooks; N sequential blocking calls on bulk trash, no VIP async path

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 411): Synchronous 30s-timeout remote DELETE per post on trash/delete hooks; N sequential blocking calls on bulk trash, no VIP async path.

Problem: `on_trash_post()` (wp_trash_post) and `on_delete_post()` (before_delete_post) each make a blocking `\BeyondWords\Api\Client::delete_audio()` call, which is `wp_remote_request()` with `'timeout' => 30` (src/api/class-client.php:492, phpcs-ignored). Unlike generation — which is deliberately deferred to cron on VIP via `is_async_generation_enabled()` — deletion has no async/deferred path at all, so the admin request blocks on the network.
Trigger: Bulk-trashing N posts with BeyondWords content from the posts list (or WP-CLI/REST bulk delete of never-trashed posts): `wp_trash_post` fires per post, producing N sequential remote DELETEs in one request — worst case N x 30s against a slow/unreachable API, easily exceeding VIP request limits and timing out the admin request mid-loop.
Impact: VIP platform violation (blocking remote requests with a long timeout on a synchronous path); bulk trash of even a modest number of posts can hang or fatal the admin request, leaving posts partially processed.
Fix: Mirror the generation design: when `is_async_generation_enabled()`, schedule a single deferred cron event to delete the audio (store the content/project IDs in the event args, since meta is wiped), or use the existing `batch_delete_audio` endpoint for bulk operations; at minimum use a short timeout for the trash-path DELETE.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.13 — Uncached blocking external API call (30s timeout) on every settings page load, and transient failures hide the other settings tabs

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/settings/class-utils.php`

```text
In the BeyondWords WordPress plugin, src/settings/class-utils.php (around line 139-153): Uncached blocking external API call (30s timeout) on every settings page load, and transient failures hide the other settings tabs.

Problem: Utils::validate_api_connection() is invoked from Settings::maybe_validate_api_creds() on the 'load-settings_page_beyondwords' hook whenever the active tab is Authentication — which is the DEFAULT tab (first key of Tabs::get_visible_tabs()), i.e. the tab used by both the admin menu link and the plugin-row 'Settings' link (neither passes ?tab=). The method's docblock claims the stored 'beyondwords_valid_api_connection' flag lets 'subsequent admin page loads short-circuit without an API call', but no short-circuit exists: line 141 unconditionally delete_option()s the flag and then always issues wp_remote_request() via Client::call_api(), whose build_args() sets 'timeout' => 30 (the VIP RemoteRequestTimeout sniff is phpcs:ignore'd in the client). The result is never cached. Line 140 also deletes the transient 'beyondwords_validate_api_connection', which is never set anywhere in the codebase (grep confirms this is the only reference) — a vestige of removed throttling. Because the flag is deleted BEFORE the call, any transient failure (timeout, DNS error, 5xx, WP_Error) leaves it unset, and Tabs::get_visible_tabs() (class-tabs.php:95) then collapses the UI to the Authentication tab only.
Trigger: Any admin opens Settings → BeyondWords (or clicks the plugin-row Settings link). No tab param → active tab = 'authentication' → load hook fires → synchronous GET https://api.beyondwords.io/v1/projects/{id} with a 30-second timeout on every single page view, even when the connection was validated seconds earlier. If the BeyondWords API is slow, the admin page render blocks for up to 30s; if the call fails for any transient reason, beyondwords_valid_api_connection stays deleted.
Impact: VIP performance violation: an uncached remote request with a long (30s) timeout on an admin page-render path, repeated on every load — ties up a PHP worker per view and makes the settings page hang during API slowness. Availability side effect: during any API outage the Integration and Preferences tabs disappear (get_visible_tabs gates on the deleted option), locking the operator out of unrelated settings until a later validation succeeds.
Fix: Implement the short-circuit the docblock promises: keep the last-success flag and only re-validate when creds changed or on explicit user action (e.g. after an Authentication-tab save, or a 'Re-check connection' button), or throttle with a short transient (e.g. 5 minutes). Do not delete beyondwords_valid_api_connection until a definitive 401/403 is received (Client::call_api already deletes it on 401). Use a bounded short timeout (~3s) for this validation GET. Remove the dead delete_transient('beyondwords_validate_api_connection') line.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.14 — Uncached wp_remote_request() with no timeout on every Site Health Info render

- [ ] 🆕 **new** · `medium` · _severity elevated (security driver)_ · `src/site-health/class-site-health.php`

```text
In the BeyondWords WordPress plugin, src/site-health/class-site-health.php (around line 189): Uncached wp_remote_request() with no timeout on every Site Health Info render.

Problem: add_rest_api_connection() issues a synchronous blocking wp_remote_request() to the BeyondWords API root inside the debug_information filter, with no 'timeout' argument (WordPress default 5 seconds) and no caching of the result (no transient/object cache). It also fires unconditionally, even on fresh installs with no API key or project ID configured. The Api\Client http_request_args filter only injects headers, not a timeout, so the 5s default applies. VIP requires remote requests on page-render paths to be cached and to use a bounded short timeout (vip_safe_wp_remote_get or timeout of 1-3s).
Trigger: An administrator opens Tools > Site Health > Info (or clicks 'Copy site info to clipboard'); WP_Debug_Data::debug_data() applies the debug_information filter, and the handler makes the remote GET on every render. If api.beyondwords.io is slow or unreachable, the admin page render blocks for up to the full 5-second HTTP timeout (plus the DNS lookup in php-misc-4).
Impact: Site Health Info page render blocks on an external HTTP request every view; on API slowness the admin page hangs for seconds. Violates VIP remote-request caching/timeout rules (WordPressVIPMinimum.Performance / vip_safe_wp_remote_get guidance).
Fix: Cache the reachability result in a short transient (1-5 minutes), pass an explicit low timeout (e.g. 'timeout' => 2, or use vip_safe_wp_remote_get when available), and skip the request entirely when \BeyondWords\Settings\Utils::has_api_creds() is false.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.15 — Stored XSS: user-controlled Content ID (and preview token) injected into an inline onload JS handler escaped only with esc_attr()

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/editor/classic/class-metabox.php`
- _Related to prior P1 (the prior XSS was in the JS player renderer, which is no longer flagged — a sibling XSS now surfaces in the classic metabox)_

```text
In the BeyondWords WordPress plugin, src/editor/classic/class-metabox.php (around line 231): Stored XSS: user-controlled Content ID (and preview token) injected into an inline onload JS handler escaped only with esc_attr().

Problem: player_embed() builds a BeyondWords.Player() call inside the inline `onload='...'` attribute of a <script> tag (lines 225-243). Dynamic values are placed into JavaScript string literals inside that handler and escaped only with esc_attr(): `contentId: "<?php echo esc_attr( $content_id ); ?>"` (line 231) and `previewToken: "<?php echo esc_attr( $preview_token ); ?>"` (line 235). esc_attr() is the wrong escaper for an inline event-handler JS context. It encodes a double quote to `&quot;`, but the browser HTML-decodes the attribute value BEFORE compiling the handler, so `&quot;` turns back into a real `"` that closes the JS string literal and lets the rest of the value run as code. $content_id comes straight from get_post_meta('beyondwords_content_id') (Meta::get_content_id, src/post/class-meta.php:165), which is written verbatim from the editable Content ID text field by ContentId::save() using only sanitize_text_field() (src/editor/components/content-id/class-content-id.php:119-124) — and sanitize_text_field() does NOT strip double quotes. I confirmed the round-trip: payload `"});alert(document.domain);({"` -> esc_attr -> `&quot;});alert(document.domain);({&quot;` -> after the browser decodes the onload attribute the JS engine sees `contentId: ""});alert(document.domain);({""`, executing alert(document.domain). (previewToken is the same sink but is API-supplied so less directly attacker-controlled; projectId on line 229 and sourceId on line 233 are always integers so are not independently exploitable.)
Trigger: A user with edit_post on a compatible post opens the classic editor, sets the Data > Content ID field to a crafted value such as `"});alert(document.domain);({"`, and saves. ContentId::save() sanitizes with sanitize_text_field (quotes preserved) and stores it in beyondwords_content_id. That non-empty value makes Meta::has_content() return true (REST integration is the default), so on the next classic-editor render of that post render_meta_box_content() calls player_embed(), which emits the payload into the onload handler; when the SDK <script> fires its load event the injected JavaScript executes in the viewer's admin session.
Impact: Stored/persistent XSS in wp-admin. A lower-privileged author can plant script that runs in the browser of any higher-privileged user (Editor/Administrator) who later opens that post's classic-editor screen — classic admin privilege escalation / account takeover (nonce theft, creating admin users, etc.). Violates WordPress VIP output-escaping requirements (data must be escaped for its exact output context).
Fix: Do not interpolate values into an inline event-handler JS string with esc_attr(). Build the player config as a PHP array and emit it with wp_json_encode(), which backslash-escapes inner quotes so they survive HTML-attribute decoding as `\"` (still inside the string). For example assemble `$config = [ 'projectId' => (int) $project_id, ... ]` (conditionally add contentId/sourceId), then `onload='new BeyondWords.Player(<?php echo esc_attr( wp_json_encode( $config ) ); ?>)'`. Better still, move the config to a data-* attribute (esc_attr is correct there) and initialise the player from a properly enqueued, non-inline script rather than an inline onload handler.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P1.16 — register_meta() exposes deprecated/secret legacy meta (incl. speechkit_access_key) publicly via the REST API

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 315): register_meta() exposes deprecated/secret legacy meta (incl. speechkit_access_key) publicly via the REST API.

Problem: `register_meta()` iterates `\BeyondWords\Core\Utils::get_post_meta_keys( 'all' )` — which merges the deprecated set including `speechkit_access_key`, `_speechkit_link`, `_speechkit_text`, `speechkit_error_message`, `speechkit_status` — and registers every key with `'show_in_rest' => true` (line 319). WordPress meta registered with show_in_rest is readable in the `view` context with NO capability check (WP_REST_Meta_Fields::get_value() performs no auth; the `auth_callback` only gates writes). String-valued rows are therefore returned verbatim to anonymous visitors in the `meta` object of `GET /wp/v2/posts/:id` for any published post. (Array/object-valued rows like `speechkit_info`/`speechkit_response` are masked to null by the string schema, but string secrets pass through.) Current-era keys `beyondwords_error_message` and `beyondwords_preview_token` are likewise publicly readable on every site.
Trigger: Unauthenticated `GET /wp/v2/posts/<id>` (or the posts collection) against a site upgraded from the legacy SpeechKit plugin: the response includes `meta.speechkit_access_key` (a per-post API credential from the v2.x era), `meta._speechkit_link`, `meta.speechkit_error_message`, etc. On any v7 site it also includes `meta.beyondwords_error_message` (internal API error strings) and `meta.beyondwords_preview_token`.
Impact: Public information disclosure: legacy per-post API access keys, internal BeyondWords API error messages, preview tokens and legacy player URLs are readable by anyone, on every compatible post type.
Fix: Register only the current keys the block editor actually needs with `show_in_rest => true`; register deprecated keys (needed only for the admin Inspect panel, which reads them server-side via has_meta()) with `show_in_rest => false`. At minimum exclude `speechkit_access_key` and error-message keys from REST exposure.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

---

## P2 — Logic / robustness / UX · moderate VIP

### P2.1 — TypeError fatal in error_message_from_response() when the API error body's `errors` member is not an array of arrays

- [ ] 🔁 **outstanding** — prior P9 · `medium` · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 566): TypeError fatal in error_message_from_response() when the API error body's `errors` member is not an array of arrays.

Problem: error_message_from_response() does `foreach ( $body['errors'] as $error ) { $messages[] = implode( ' ', array_values( $error ) ); }` with no shape validation of the externally-controlled JSON. If `errors` is a list of strings (e.g. {"errors":["Title can't be blank"]}) each $error is a string and `array_values( $error )` throws `TypeError: array_values(): Argument #1 ($array) must be of type array, string given` (confirmed on PHP 8.4). If `errors` is a scalar (e.g. {"errors":"Bad request"}) the foreach emits a PHP warning; if error rows contain nested arrays, implode emits Array-to-string warnings. The body comes from whatever answers the HTTP request — the BeyondWords API across versions, or an intermediary (WAF/CDN/gateway error JSON) — none of which the plugin controls.
Trigger: Client::call_api() gets any response with code > 299 for a REST-API-integration post (i.e. during Sync::generate_audio_for_post on post save, or trash/delete) whose JSON body contains an `errors` key holding strings rather than arrays. call_api() then calls error_message_from_response() at line 465.
Impact: Uncaught TypeError -> fatal 500 in the middle of the wp_after_insert_post hook: the editor's save request dies while trying to record the API error, and the error meta is never written.
Fix: Guard the shape: `if ( is_array( $body['errors'] ) ) { foreach ( (array) $body['errors'] as $error ) { $messages[] = is_array( $error ) ? implode( ' ', array_map( 'strval', array_values( $error ) ) ) : (string) $error; } }`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.2 — Fetch fired with undefined restUrl before settings store resolves; failure then persists spurious error meta to the DB

- [ ] 🔁 **outstanding** — prior P14 · `medium` · `src/editor/components/content-id/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/index.js (around line 77): Fetch fired with undefined restUrl before settings store resolves; failure then persists spurious error meta to the DB.

Problem: restUrl comes from select('beyondwords/settings').getSettings()?.restUrl (line 55-58). The store's DEFAULT_STATE.settings is {} (src/settings/store/index.js), and restUrl only exists after the async resolver fetches /beyondwords/v1/settings. Until then restUrl is undefined, yet handleFetch only guards contentId and settingsProjectId (line 69) — and settingsProjectId is satisfied by post meta (metaProjectId) alone, without settings being loaded. The Fetch button is enabled whenever contentId is non-empty (line 162), which it is immediately for any post with saved content. Clicking Fetch then runs fetch(`${undefined}beyondwords/v1/projects/...`), a relative URL 'undefinedbeyondwords/v1/...' resolved against /wp-admin/post.php → guaranteed 404. The !response.ok branch (lines 88-99) then POSTs errorMeta to the post via updatePostMeta and editPost, persisting beyondwords_error_message ('Failed to fetch content. Please check the Content ID.') — which the error-notice component displays — even though the content ID was never actually checked. The classic twin has exactly this guard (classic-metabox.js lines 246-251 return early when beyondwordsData.root is missing), proving the omission. If the settings resolver rejects (REST hiccup, security plugin), @wordpress/data marks resolution failed and never retries, so restUrl stays undefined for the whole session and every Fetch click destructively writes error meta.
Trigger: Open the block editor on a post that already has beyondwords_content_id + beyondwords_project_id meta and click Fetch before the /beyondwords/v1/settings response arrives (slow host / cold cache window of the request RTT), or at any time after that settings request has failed once.
Impact: A misleading 'Failed to fetch content. Please check the Content ID.' error is saved into post meta on the server (REST POST) and shown as an editor error notice, for a perfectly valid content ID; with a failed settings resolver the Fetch feature is silently broken for the whole session while still writing error meta on every click.
Fix: Bail out early when restUrl is falsy (mirroring classic-metabox.js's beyondwordsData.root guard) — e.g. `if ( ! restUrl ) return;` before setIsLoading — and/or disable the button until `hasFinishedResolution('getSettings')`. Better: drop the raw fetch and use apiFetch({ path: `/beyondwords/v1/projects/${...}/content/${...}` }) like every other request in this plugin, which needs neither restUrl nor a hand-rolled nonce.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.3 — Unchecking Customize does not invalidate in-flight voices fetches, so a late response re-populates the hidden Voice select and save() persists meta the user reverted

- [ ] 🔁 **outstanding** — prior P16 · `medium` · `src/editor/components/select-voice/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/classic-metabox.js (around line 204): Unchecking Customize does not invalidate in-flight voices fetches, so a late response re-populates the hidden Voice select and save() persists meta the user reverted.

Problem: toggleCustomize(false) (lines 199-205) hides the fields, clears the Language select, empties this.voices and renders empty dropdowns — but it never bumps this.voicesReq. Any voices fetch already in flight therefore still passes the reqId check in loadVoices (line 379), repopulates this.voices, and its caller's continuation runs: getVoices' .then (lines 325-329) calls renderModels(defaultVoiceId), and hydrate's .then (lines 297-302) calls renderModels(savedVoiceId). renderVoices then sets the now-hidden #beyondwords_voice_id select's value (line 491). Neither continuation re-checks the Customize checkbox, even though applyProjectDefaultLanguage (lines 237-245) contains exactly this guard for the project fetch — the comment there spells out the hazard ('otherwise we'd ... persist a language on a post the user left un-customised'). A display:none form control still submits, and save() in src/editor/components/select-voice/class-select-voice.php (lines 559-573) persists purely from the submitted select values — the Customize checkbox is never read server-side.
Trigger: Classic editor, Customize on. (a) User picks a Language (change handler line 159-166 starts a voices fetch), then unchecks Customize before the fetch resolves; or (b) a saved customized post loads, hydrate()'s fetch is in flight, and the user unchecks Customize (their first action on the page) before it resolves. The fetch window is a WP REST round-trip that proxies to the remote BeyondWords API (Client::get_voices — seconds on a cold transient cache). When the response lands, renderModels re-selects the default/saved voice in the hidden select. User clicks Update.
Impact: The post the user explicitly reverted to project defaults silently keeps (or gains) beyondwords_body_voice_id meta: save() runs update_post_meta with the hidden select's voice id while beyondwords_language_code submits '' and is deleted. Audio is then generated with the wrong voice, and on next edit element() derives $customize from the stored voice (class-select-voice.php line 94), so the post re-opens with Customize checked and an empty Language — a half-broken state the user never created. No visual feedback exists because the fields are hidden when the stale response mutates them.
Fix: In toggleCustomize's off-branch, invalidate in-flight loads by incrementing the token: add `this.voicesReq++;` next to `this.voices = [];` (line 204). Defence in depth: have the .then continuations in getVoices (line 326) and hydrate (line 298) re-check `byId('beyondwords_customize').checked` before calling renderModels, mirroring the existing guard in applyProjectDefaultLanguage.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.4 — VIP: no negative caching in cached_get() — API failures are re-fetched on every editor render

- [ ] 🆕 **new** · `medium` · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 546): VIP: no negative caching in cached_get() — API failures are re-fetched on every editor render.

Problem: cached_get() only stores a transient when the response is a 2xx with an array body (lines 541-547). Any failure — WP_Error, non-2xx status, empty/invalid JSON — is never cached, so the very next call repeats the remote request. Combined with php-api-2, every classic-editor page load during an API outage re-issues up to 5 blocking remote GETs (languages, voices, script templates, video templates, video settings), each allowed 30s, from every concurrent editor. VIP caching guidance explicitly requires caching failed remote lookups for a short period (e.g. 30-60s) to prevent exactly this repeated-blocking-request pattern and the resulting worker exhaustion / thundering herd.
Trigger: BeyondWords API returns errors or times out (outage, invalid credentials, rate limiting). Every subsequent admin editor render and every /beyondwords/v1/languages|voices REST call performs fresh remote requests because nothing was cached.
Impact: During any API incident, wp-admin editor loads hammer the remote API and block PHP workers repeatedly instead of failing fast from cache; on VIP this is a platform-level performance violation.
Fix: On failure, set a short-TTL sentinel transient (e.g. set_transient( $key . '_fail', 1, MINUTE_IN_SECONDS ) checked before calling, or cache `null` for 60s and distinguish it from a miss) so repeated failures short-circuit for a minute.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.5 — migrate_disabled_to_embed_none() runs before 'init', so custom post types are never migrated (and nothing at all migrates when network-activated)

- [ ] 🆕 **new** · `medium` · `src/core/class-updater.php`

```text
In the BeyondWords WordPress plugin, src/core/class-updater.php (around line 139): migrate_disabled_to_embed_none() runs before 'init', so custom post types are never migrated (and nothing at all migrates when network-activated).

Problem: The migration queries get_posts([ 'post_type' => 'any', ... ]) from Updater::run(), which executes at plugin-include time (speechkit.php calls Plugin::init() at top level). Verified against wp-settings.php in WP 5.9, 6.3, 6.6.2 and 6.7.1: create_initial_post_types() runs before the active-plugins loop but custom post types register on the 'init' hook, which has not fired yet. WP_Query resolves 'any' via get_post_types(['exclude_from_search' => false]) at query time (class-wp-query.php:2539), which yields only 'post', 'page', 'attachment' at this point — every CPT post carrying beyondwords_disabled = '1' is invisible to the migration. Worse, for a NETWORK-ACTIVATED plugin on multisite, wp-settings.php includes network plugins before create_initial_post_types() runs, so get_post_types() is empty and WP_Query emits 'AND 1=0' — the migration matches zero posts of any type. Additionally 'post_status' => 'any' excludes trash and auto-draft, so trashed disabled posts are also skipped. The function's contract ('Carry the flag forward ... then drop the legacy key') is silently unfulfilled for all of these posts.
Trigger: Upgrade from v6.x to v7 on a site that used the 'Display player' opt-out on any custom post type (BeyondWords explicitly supports CPTs via get_compatible_post_types()), or on any network-activated multisite install, or on posts sitting in trash during the upgrade. The migration runs pre-'init' once (or per-request with the -dev version, but always pre-'init'), never sees those posts, and the version gate eventually closes for good on a final release version.
Impact: The beyondwords_embed = 'none' value is never written and the legacy beyondwords_disabled meta is never deleted for CPT/trashed posts (or any posts, when network-activated). Today the front-end is saved only by the runtime fallback in SettingsFields::is_player_disabled_for_post(), which reads the legacy flag — meaning the migration's convergence goal fails permanently, stale deprecated meta rows persist, REST/GraphQL consumers reading the registered beyondwords_embed meta see '' for posts that are actually disabled, and the legacy fallback can never safely be removed.
Fix: Defer this migration until post types exist: hook the v7 data migration to run on 'init' (priority late) or 'admin_init' instead of executing it synchronously inside Plugin::init(), e.g. Updater::run() records that the migration is pending and add_action('init', ...) performs it. Alternatively query wp_postmeta directly by meta_key (the migration does not actually need post-type filtering) instead of get_posts('post_type' => 'any').

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.6 — delete_deprecated_options() on multisite calls only delete_site_option(), but the plugin never stores site options — deprecated options are never removed

- [ ] 🆕 **new** · `medium` · `src/core/class-updater.php` · +1 duplicate finding(s) merged

```text
In the BeyondWords WordPress plugin, src/core/class-updater.php (around line 114): delete_deprecated_options() on multisite calls only delete_site_option(), but the plugin never stores site options — deprecated options are never removed.

Problem: The v7 cleanup does `is_multisite() ? delete_site_option( $option ) : delete_option( $option )` (an either/or). grep across the whole plugin confirms there are zero update_site_option()/add_site_option() calls — every option (including all 39 deprecated ones, written by v6 and earlier via the Settings API and update_option()) lives in each site's per-site wp_N_options table. delete_site_option() targets wp_sitemeta, where none of these rows exist.
Trigger: Any multisite installation upgrading from 6.x to 7.0.0: is_multisite() is true, so the branch calls delete_site_option() for all 39 options, each returning false, and never calls delete_option() for the current site.
Impact: The migration's entire purpose fails on multisite: all deprecated per-site options (beyondwords_player_*, beyondwords_project_*, speechkit_* etc.) remain in the options table indefinitely — several of them autoloaded on every request, which is the exact bloat the cleanup was written to remove. Combined with php-core-1, each request additionally wastes 39 fruitless sitemeta lookups.
Fix: Call delete_option( $option ) unconditionally (it operates on the current site), and additionally call delete_site_option( $option ) on multisite for the truly-legacy installs the docblock mentions — i.e. both, not either/or: `delete_option( $option ); if ( is_multisite() ) { delete_site_option( $option ); }`.

Note: the same root cause was independently reported by other finders as: delete_deprecated_options() is a no-op on multisite: deprecated per-site options are never removed. Fixing the above resolves them together.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.7 — updateMetaboxUI never syncs the Voice select and sets the Language select without firing 'change', so the next post Update silently wipes the just-fetched voice/language meta

- [ ] 🆕 **new** · `medium` · `src/editor/components/content-id/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/classic-metabox.js (around line 112): updateMetaboxUI never syncs the Voice select and sets the Language select without firing 'change', so the next post Update silently wipes the just-fetched voice/language meta.

Problem: A successful fetch REST-saves beyondwords_body_voice_id and beyondwords_language_code (lines 284-287). updateMetaboxUI's stated job is to 'update visible metabox form controls to reflect the fetched data' so the still-open classic form doesn't clobber the REST-saved meta — it syncs the content-id input, the generate-audio checkbox and the language select, but (1) it never touches the #beyondwords_voice_id select rendered by select-voice, and (2) it assigns languageSelect.value programmatically (line 140) without dispatching a 'change' event, so select-voice's classic JS (which listens for 'change' on #beyondwords_language_code to repopulate the model/voice dropdowns) never reacts. SelectVoice::save (class-select-voice.php lines 538-574) runs on every post Update and maps $_POST['beyondwords_voice_id'] → beyondwords_body_voice_id, calling delete_post_meta when the value is empty, and does the same for beyondwords_language_code. So the stale on-screen Voice select ('' or the old voice, possibly from a different language) overwrites or deletes the body_voice_id the fetch just persisted; when the fetched language code has no matching option (hasOption false, line 139), the stale language select likewise overwrites/deletes beyondwords_language_code.
Trigger: Classic editor: click Fetch on a valid content ID (API response includes body_voice_id/language — meta is REST-saved and a success notice shows), then click the normal Update button without reloading the page. SelectVoice::save re-saves the untouched form selects over the fetched meta.
Impact: The voice (and possibly language) association imported by the fetch is silently reverted or deleted in the very next save — the normal flow after fetching — so subsequent audio regeneration uses the wrong or project-default voice while the UI gave no indication anything was lost.
Fix: After a successful save, also sync the voice control: set #beyondwords_voice_id to meta.beyondwords_body_voice_id (inserting the option if absent) and dispatch `languageSelect.dispatchEvent(new Event('change', { bubbles: true }))` so select-voice repopulates its dependent dropdowns; alternatively stop writing beyondwords_language_code/beyondwords_body_voice_id from the fetch in classic (aligning with the deliberate 'do not copy language/body_voice_id back' rule documented in src/post/class-sync.php process_response).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.8 — Save-lifecycle effects fire at save START (didPostSaveRequestSucceed is always true mid-save); a failed save silently keeps the destructive delete-content edit while the UI resets

- [ ] 🆕 **new** · `medium` · `src/editor/components/inspect-panel/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/index.js (around line 60-76): Save-lifecycle effects fire at save START (didPostSaveRequestSucceed is always true mid-save); a failed save silently keeps the destructive delete-content edit while the UI resets.

Problem: Both useEffects use the condition `isSavingPost && ! isAutosavingPost && didPostSaveRequestSucceed`. In core/editor, didPostSaveRequestSucceed() is `! getLastEntitySaveError(...)`, and core-data's saving reducer clears the last save error on SAVE_ENTITY_RECORD_START — so during every in-flight save the selector is true. The effects therefore run the moment a manual save STARTS, before the request outcome exists: the warning notice is removed (lines 60-65) and `removed` is reset to false (lines 67-76) unconditionally. The didPostSaveRequestSucceed guard is ineffective because it is sampled during the request, never after it. If the save request then FAILS, core-data keeps the pending edits dirty — including the `beyondwords_delete_content: '1'` meta edit made by setDeleteContent — but the panel now shows the 'Remove' label (not 'Restore'), the Copy button is re-enabled, and the 'data will be removed when the post is saved' warning notice is gone.
Trigger: Click 'Remove' in the Inspect panel (sets beyondwords_delete_content='1', shows warning, button becomes 'Restore'), click Update, and have the save request fail (offline/network blip, expired nonce, a 5xx, or another plugin returning WP_Error from a REST save filter). The effects fire at request start; the failure leaves the '1' edit queued while the UI claims no removal is pending. The user later clicks Update again once saving works.
Impact: Silent, unintended destruction of the post's BeyondWords data: the next successful save persists beyondwords_delete_content='1', which deletes all BeyondWords post meta AND triggers the DELETE request to the BeyondWords REST API (per the save flow documented in class-inspect-panel.php), removing the audio remotely — with the warning notice suppressed and no 'Restore' affordance shown. Data loss is irreversible.
Fix: Detect save COMPLETION instead of save start, e.g. track the previous saving state: `const wasSaving = useRef( false );` then in one effect: `if ( wasSaving.current && ! isSavingPost ) { if ( didPostSaveRequestSucceed ) { removeWarningNotice(); setRemoved( false ); } } wasSaving.current = isSavingPost && ! isAutosavingPost;`. On failure, leave `removed` and the warning notice intact (or re-create the notice) so the pending destructive edit stays visible.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.9 — Classic-editor Copy button is a silent no-op: ClipboardJS global is never loaded

- [ ] 🆕 **new** · `medium` · `src/editor/components/inspect-panel/js/inspect.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/js/inspect.js (around line 16-17): Classic-editor Copy button is a silent no-op: ClipboardJS global is never loaded.

Problem: inspect.js requires a global ClipboardJS (`if ( copyButton && typeof ClipboardJS !== 'undefined' ) { const clipboard = new ClipboardJS( '#beyondwords__inspect--copy' ); ... }`), but nothing loads it. The file is served raw from src/ (not webpack-bundled) and is enqueued by src/editor/components/inspect-panel/class-assets.php:43-49 with deps `[ 'wp-i18n' ]` only. Git history confirms the breakage: clipboard.js v2.0.10 used to be inlined at the top of this exact file; commit 6f08767 ('Use WordPress ClipboardJS', Jan 2025) deleted the inline bundle but never added WP core's registered `clipboard` script handle to the wp_enqueue_script dependency array (deps were `['jquery']`, later changed to `['wp-i18n']` in 633a308). A repo-wide grep finds no other enqueue of ClipboardJS, and WordPress core does not enqueue its `clipboard` handle on post.php/post-new.php. The `typeof` guard therefore always fails in the classic editor, no click handler is ever bound, and ClipboardJS's data-clipboard-text attribute (rendered by class-inspect-panel.php:113) is inert without the library. There is no Cypress spec for the classic inspect metabox (only tests/cypress/e2e/block-editor/inspect-panel.cy.js), so nothing catches it.
Trigger: Open any post in the Classic Editor on a BeyondWords-compatible post type, reveal the 'BeyondWords: Inspect' metabox via Screen Options, click 'Copy'.
Impact: The Copy button does nothing at all — nothing is copied to the clipboard, the ✓ confirmation span never shows, and no error is surfaced. The classic-editor half of the support-diagnostics feature has been completely dead since the inline bundle was removed. (Related hygiene: the script is also enqueued on every post.php/post-new.php screen regardless of post type compatibility or block-editor use, where it is pure dead weight.)
Fix: In src/editor/components/inspect-panel/class-assets.php change the dependency array to include WP core's registered handle, e.g. `[ 'clipboard', 'wp-i18n' ]` — wp-includes/js/clipboard.js exposes the `window.ClipboardJS` global this file checks for. Optionally also gate the enqueue on the compatible-post-type check like the block bundle does.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.10 — hydrate() re-enables the Model select even when the voices fetch failed, recreating the empty-state data loss the disable guard exists to prevent

- [ ] 🆕 **new** · `medium` · `src/editor/components/select-voice/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/classic-metabox.js (around line 303): hydrate() re-enables the Model select even when the voices fetch failed, recreating the empty-state data loss the disable guard exists to prevent.

Problem: hydrate() disables #beyondwords_model during the fetch specifically so 'a Model change during the fetch can't run against empty state (which would blank the Voice select)' (comment, lines 289-291). But the re-enable is in .finally (lines 303-307), so it also runs when loadVoices failed: the catch (lines 385-393) sets this.voices = [] and resolves false, renderModels is skipped (applied is false), and the server-rendered Model dropdown — still listing the saved language's models and looking fully functional — is re-enabled against an empty this.voices. Any subsequent Model interaction hits onModelChange (lines 505-518): this.voices.find(...) returns undefined, renderVoices(modelKey, '', true) computes bucketVoices = [] and replaces the Voice select with only the placeholder, value '' (lines 480-492), then hides it (line 495). The hydrate docblock (lines 261-268) names this exact chain as the failure the function exists to prevent: 'the first Model change finds no voices, empties the Voice select, and save() then drops the stored voice'.
Trigger: Open a saved customized post in the classic editor while the hydrate voices fetch fails — remote BeyondWords API error/timeout behind the beyondwords/v1 voices proxy, transient network failure, or invalid API key (the failure is silent apart from a console.log). The loader disappears and the Model dropdown shows the correct saved model. The user changes the Model (or flips it to the 'Select a model' placeholder), sees nothing obviously wrong because the emptied Voice select is hidden, and clicks Update.
Impact: The form submits beyondwords_voice_id='' and save() in class-select-voice.php (lines 567-573) runs delete_post_meta('beyondwords_body_voice_id') — the post's stored voice is silently deleted after a transient fetch failure, and subsequent audio generation falls back to a different voice. This is precisely the data-loss path the hydrate guard was added to close; it remains open on the error branch.
Fix: Only re-enable the Model select when the fetch applied: move `modelSelect.disabled = false;` into the `.then( ( applied ) => { if ( applied ) { ... } } )` success branch (keeping it disabled — or retrying — when applied is false), e.g. `.then((applied) => { if (applied) { this.renderModels(savedVoiceId); if (modelSelect) modelSelect.disabled = false; } })`. Alternatively, make onModelChange bail out (and leave the current Voice select untouched) when this.voices is empty.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.11 — recomputeEmbed() collapses the server-derived default embed to 'none', silently disabling the player after a Source/Output change

- [ ] 🆕 **new** · `medium` · `src/editor/components/settings-fields/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/settings-fields/classic-metabox.js (around line 164): recomputeEmbed() collapses the server-derived default embed to 'none', silently disabling the player after a Source/Output change.

Problem: When the current embed selection is no longer offered by the new Source × Output, recomputeEmbed() falls back to EMBED_NONE: `const selected = stillValid ? previous : EMBED_NONE;`. But the PHP renderer (SettingsFields::render_player_section → get_effective_embed, src/editor/components/settings-fields/class-settings-fields.php:305-321) renders the RESOLVED default (e.g. 'audio_post') as the selected option even when beyondwords_embed meta is unset — the common case. The JS cannot distinguish 'explicitly stored audio_post' from 'defaulted audio_post', so it treats the derived default as an explicit choice and downgrades it to 'none'. The block editor handles the same situation differently (src/editor/components/settings-panel/player-section.js:42,57-62): only a truthy STORED value that becomes invalid is written as 'none'; an unset value keeps following getDefaultEmbed(source, output), so the player stays visible ('None is the deliberate opt-out' per helpers.js getDefaultEmbed docs).
Trigger: Post with no beyondwords_embed meta (any post whose embed was never explicitly chosen — the default state). Classic editor renders Embed = 'Audio (post)' (derived). User changes Output from 'Audio' to 'Video' (or Source post→script): the change listener calls recomputeEmbed(), 'audio_post' is not in [none, video_post], so the select is rebuilt with 'None' selected. Since the embed select always submits and SettingsFields::save() persists any valid submitted value, saving writes beyondwords_embed='none'. Also one-way sticky: flipping Output to Video and back to Audio leaves 'none' selected because 'none' is always valid.
Impact: The player is permanently hidden on the post (is_player_disabled_for_post() treats 'none' as authoritative opt-out) even though the user never opted out — they only changed the Output/Source. The identical action sequence in the block editor leaves the player visible showing the new default asset, so classic and block editors produce opposite front-end results.
Fix: Expose whether an explicit embed is stored (e.g. wp_localize a hasExplicitEmbed flag in class-assets.php, or a data-explicit attribute on the select from render_player_section), then in recomputeEmbed() mirror the block editor: `const fallback = hasExplicitEmbed ? EMBED_NONE : ( options.find( ( o ) => o.value !== EMBED_NONE )?.value || EMBED_NONE ); const selected = stillValid ? previous : fallback;`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.12 — Duplicate player on AMP requests: has_custom_player() cannot detect the AMP renderer's output

- [ ] 🆕 **new** · `medium` · `src/player/class-player.php`

```text
In the BeyondWords WordPress plugin, src/player/class-player.php (around line 193): Duplicate player on AMP requests: has_custom_player() cannot detect the AMP renderer's output.

Problem: has_custom_player() detects an existing player via (a) the raw shortcode, (b) a `<script async defer src=…sdk…>` tag, or (c) a `<div data-beyondwords-player="true">`. But when an editor places the player manually, the shortcode is expanded by WP core's do_shortcode (the_content priority 11) long before auto_prepend_player runs at priority 1000000. On AMP requests the expanded output is Amp::render()'s `<amp-iframe data-beyondwords-player-context=…>` markup — no `<script>`, no data-beyondwords-player div, no shortcode. None of the three checks match, so has_custom_player() returns false and auto_prepend_player() prepends a second AMP player. (The JS path is only accidentally safe because its rendered `<script async defer src=sdk>` tag happens to match check (b).) Legacy `<div data-beyondwords-player>` placeholders hit the same path: replace_legacy_custom_player() converts them to the shortcode at priority 5, do_shortcode expands at 11, and the amp-iframe goes undetected at 1000000.
Trigger: Site served via an AMP plugin (Utils::is_amp() true); a post eligible for the player contains [beyondwords_player] (or a legacy player div) in its content; front-end AMP view: priority 11 expands the shortcode into <amp-iframe …>; priority 1000000 auto_prepend_player → has_custom_player() misses the amp-iframe → render_player('auto') prepends a second <amp-iframe>.
Impact: Two stacked audio players rendered on AMP pages for any post where the editor positioned the player manually — visible layout/UX defect and double iframe load of the remote AMP player.
Fix: Detect rendered player output generically, e.g. add an XPath for the marker attribute both renderers emit: `if ( $crawler->filterXPath( '//*[@data-beyondwords-player-context]' )->count() > 0 ) { return true; }` (covers both the <script> and <amp-iframe> forms), or explicitly check `//amp-iframe[contains(@src, "audio.beyondwords.io")]`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.13 — get_post_body() removes the wpautop the_content filter and never restores it (request-wide state leak, cross-post body corruption)

- [ ] 🆕 **new** · `medium` · `src/post/class-content.php`

```text
In the BeyondWords WordPress plugin, src/post/class-content.php (around line 84): get_post_body() removes the wpautop the_content filter and never restores it (request-wide state leak, cross-post body corruption).

Problem: For block-editor posts, `get_post_body()` calls `remove_filter( 'the_content', 'wpautop' )` before `apply_filters( 'the_content', $content )`, but never re-adds the filter. The removal persists for the remainder of the PHP request, affecting every later `the_content` application — including this plugin's own subsequent API bodies for other posts.
Trigger: Bulk "Generate audio" from the posts list (src/posts-list/class-bulk-edit.php:278 loops `Sync::generate_audio_for_post()` in one admin request): if a block-editor post is processed before a classic-editor post, the block post strips wpautop, so the classic post's `apply_filters('the_content', ...)` output loses all auto-generated <p> wrapping in the body sent to the BeyondWords API. Same contamination occurs on the VIP async path when Cron Control runs several queued `beyondwords_generate_audio` events in one request, and it also alters any third-party the_content rendering later in the same save/cron request.
Impact: Audio body for classic-editor posts is submitted without paragraph markup (degraded segmentation/pauses, body differs from front-end rendering), and global filter state is silently mutated for unrelated code in the same request.
Fix: Capture the original priority with `$priority = has_filter( 'the_content', 'wpautop' );`, remove it, apply the filters, then restore with `add_filter( 'the_content', 'wpautop', $priority )` (only when it was originally present) before returning.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.14 — get_lang_code_from_json_if_empty() shadows the stored beyondwords_language_code whenever legacy beyondwords_language_id exists

- [ ] 🆕 **new** · `medium` · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 479): get_lang_code_from_json_if_empty() shadows the stored beyondwords_language_code whenever legacy beyondwords_language_id exists.

Problem: The callback is hooked to the `get_post_metadata` short-circuit filter. WordPress core (`get_metadata_raw()`) invokes this filter with `$value = null` BEFORE the meta cache/DB is consulted, so the `! empty( $value )` guard never reflects whether the post actually has `beyondwords_language_code` stored — it only detects another filter's short-circuit value. Consequently, if the post has any legacy `beyondwords_language_id`, the callback ALWAYS returns the JSON-mapped legacy code, permanently overriding the real stored value. Nothing in the codebase ever deletes `beyondwords_language_id` (it is only read here and removed at uninstall), while the Select Voice UI explicitly writes `beyondwords_language_code` (src/editor/components/select-voice/class-select-voice.php:562).
Trigger: A post migrated from v4–v6 with `beyondwords_language_id = 10` (maps to `ar_SA` in assets/lang-codes.json). The editor picks a new language in Select Voice; `update_post_meta(..., 'beyondwords_language_code', 'en_GB')` succeeds (writes bypass the read filter). Every subsequent `get_post_meta( $id, 'beyondwords_language_code', true )` — Select Voice render (class-select-voice.php:153), Head::add_meta_tags() (class-head.php:107), REST meta — re-enters the filter with $value=null and returns `ar_SA`.
Impact: The user's explicit language choice is silently ignored forever on legacy posts: the voice dropdown loads voices for the wrong language, the editor UI reverts the selection on reload, and the `beyondwords-article-language` head meta tag emits the wrong language.
Fix: Inside the callback, check the real stored value before back-filling, e.g. temporarily `remove_filter( 'get_post_metadata', [ self::class, 'get_lang_code_from_json_if_empty' ], 10 )`, call `get_post_meta( $object_id, 'beyondwords_language_code', true )`, re-add the filter, and only back-fill when that stored value is empty (or read the meta cache directly via `get_metadata_raw`). Alternatively perform a one-time write migration and drop the read filter.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.15 — Nonce field name and action are swapped between bulk_edit_custom_box() and save_bulk_edit() - the inline bulk-edit feature can never work

- [ ] 🆕 **new** · `medium` · `src/posts-list/class-bulk-edit.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-bulk-edit.php (around line 112): Nonce field name and action are swapped between bulk_edit_custom_box() and save_bulk_edit() - the inline bulk-edit feature can never work.

Problem: bulk_edit_custom_box() line 66 calls wp_nonce_field( 'beyondwords_bulk_edit_nonce', 'beyondwords_bulk_edit' ), which renders a hidden input NAMED 'beyondwords_bulk_edit' whose value is a nonce for ACTION 'beyondwords_bulk_edit_nonce' (wp_nonce_field's first arg is the action, second is the field name). The AJAX handler save_bulk_edit() (lines 110-115) instead requires $_POST['beyondwords_bulk_edit_nonce'] and verifies it against action 'beyondwords_bulk_edit' - both the field name and the action string are inverted relative to the rendered form, so no request assembled from the form can ever pass wp_verify_nonce(); grep confirms no code in src/ ever creates a nonce with action 'beyondwords_bulk_edit' (only tests/phpunit/posts-list/test-bulk-edit-ajax.php hand-crafts wp_create_nonce('beyondwords_bulk_edit'), which is why the tests pass while production cannot). Compounding it, the handler reads the generate/delete choice from $_POST['beyondwords_bulk_edit'] (lines 124, 132) - the field that actually carries the nonce - while the rendered <select> is named 'beyondwords_generate_audio' (line 73), which the handler never reads. Finally, no JavaScript anywhere in the repo posts to wp_ajax_save_bulk_edit_beyondwords (repo-wide grep for the action name and 'post_ids' finds nothing outside these PHP files), so the endpoint is unreachable end-to-end.
Trigger: Editor selects posts, chooses Bulk actions > Edit, sets the rendered 'BeyondWords' select to 'Generate audio' or 'Delete audio', clicks Update. Core's bulk_edit request carries beyondwords_generate_audio=generate plus the misnamed nonce; nothing reads either field (GenerateAudio::save() bails for lack of its own beyondwords_generate_audio_nonce), and any client that did call the AJAX endpoint with the rendered fields would die in wp_nonce_ays('').
Impact: The entire Bulk Edit > BeyondWords control is a silent no-op: users believe they queued/deleted audio for many posts, but nothing happens and no error is shown. Any future JS written against the rendered form is guaranteed a 403.
Fix: Make the pieces agree: render wp_nonce_field( 'beyondwords_bulk_edit', 'beyondwords_bulk_edit_nonce' ) (action/name in the intended order), read the action from the field the form actually posts (rename the select to a dedicated name, e.g. beyondwords_bulk_edit_action, and read that), and add the missing JS that intercepts the bulk-edit Update click and posts post_ids + action + nonce to wp_ajax_save_bulk_edit_beyondwords - or drop the fieldset/AJAX handler entirely in favour of the working dropdown bulk actions.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.16 — is_edit_screen() evaluated at plugin-load time is always false - pre_get_posts sort hook never registers, sortable column silently does nothing

- [ ] 🆕 **new** · `medium` · `src/posts-list/class-column.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-column.php (around line 56): is_edit_screen() evaluated at plugin-load time is always false - pre_get_posts sort hook never registers, sortable column silently does nothing.

Problem: Column::init() runs from Plugin::init() (src/core/class-plugin.php:79), which speechkit.php:41 invokes at top level while plugins are being loaded inside wp-settings.php. At that moment wp-admin/includes/screen.php has not been loaded (wp-admin/admin.php includes it only after wp-settings.php returns, i.e. after plugins_loaded/init/wp_loaded), so Core\Utils::is_edit_screen() (src/core/class-utils.php:52-64) hits function_exists('get_current_screen') === false and returns false on every request; even once the function exists, get_current_screen() stays null until set_current_screen() runs after admin_init. Therefore add_action('pre_get_posts', [self::class, 'set_sort_query']) never executes, and set_sort_query()/get_sort_query_args() (lines 134-163) are unreachable dead code. Meanwhile make_column_sortable() (line 122) still marks the column sortable.
Trigger: Any admin clicks the 'BeyondWords' column header on edit.php. The page reloads with orderby=beyondwords, but with no pre_get_posts handler WP_Query::parse_orderby() discards the unknown key and falls back to the default date ordering - the list never sorts by audio status, with no error.
Impact: The advertised sortable BeyondWords column is non-functional on every install: clicking the header toggles the arrow but the ordering is unchanged (date order), misleading editors trying to group posts with/without audio.
Fix: Register the hook unconditionally inside init() (or inside the existing wp_loaded closure) and do the screen check inside the callback, e.g. in set_sort_query() bail unless is_admin() && $query->is_main_query() && 'beyondwords' === $query->get('orderby') - or defer the is_edit_screen() test to the current_screen action.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.17 — Unticking every Preselect checkbox silently reverts to the stored config — the setting cannot be fully disabled

- [ ] 🆕 **new** · `medium` · `src/settings/class-preselect.php`

```text
In the BeyondWords WordPress plugin, src/settings/class-preselect.php (around line 298-300): Unticking every Preselect checkbox silently reverts to the stored config — the setting cannot be fully disabled.

Problem: Preselect::sanitize() returns the previously stored option when $value is not an array. But wp-admin/options.php passes $value = null for any registered option absent from $_POST (foreach over the group: `$value = null; if ( isset( $_POST[$option] ) ) ...; update_option( $option, $value );`). The preselect field renders only checkboxes (enabled / all / term boxes) with no hidden fallback input, and unticked checkboxes are not submitted — so a submission where NO checkbox under beyondwords_preselect is checked sends no key at all, sanitize(null) returns $existing, and update_option() no-ops. The narrow escape hatch (nested 'all'/term checkboxes staying checked-but-hidden and thus still submitting — preselect.js only hides them, it never unchecks them) does not exist for post types without hierarchical show_ui taxonomies (e.g. 'page'), which render only the 'enabled' checkbox (the options div at line 438 is wrapped in `if ( ! empty( $taxonomies ) )`).
Trigger: Example on a stock site: stored config is `['page' => ['mode' => 'all']]` (only Pages enabled — pages have no hierarchical taxonomy, so only the enabled checkbox renders). The user unticks Pages and clicks Save. $_POST contains no beyondwords_preselect key → options.php calls update_option('beyondwords_preselect', null) → sanitize(null) returns the old array. Also reachable with Posts: untick 'All' (revealing an empty term tree), then untick the Posts checkbox — now no checkbox in the group is checked.
Impact: WordPress shows 'Settings saved.' but the preselect config is unchanged and the form re-renders with the boxes ticked again. The publisher cannot turn 'Preselect Generate audio' off in this state, so new posts of those types keep getting audio generation preselected (and, via Sync/GenerateAudio's should_preselect_for_post(), server-side generation keeps being triggered) against the operator's explicit choice.
Fix: Distinguish 'field submitted empty' from 'field absent'. Simplest: render a hidden marker input inside the field (e.g. `<input type="hidden" name="beyondwords_preselect[__submitted]" value="1">`) and in sanitize() treat an array containing the marker (or null when the marker group was posted) normally; alternatively treat null as [] (empty map) since sanitize only runs on Preferences-group saves where the field is always rendered — the merge-preserve behaviour for non-rendered post types is already handled inside the loop, not by the null branch.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.18 — Unchecked preg_replace() null return can wipe post content and cascade to a strict-types TypeError

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/player/class-player.php`

```text
In the BeyondWords WordPress plugin, src/player/class-player.php (around line 99): Unchecked preg_replace() null return can wipe post content and cascade to a strict-types TypeError.

Problem: replace_legacy_custom_player() returns preg_replace()'s result directly from a the_content filter. preg_replace() returns null on PCRE failure — realistically PREG_BACKTRACK_LIMIT_ERROR here, because the pattern's `(?=[^>]*data-beyondwords-player…)` lookahead plus `[^>]*(?:\/>|>\s*<\/div>)` backtracks once per character of each `<div ` attribute run; on a very large post (or one containing `<div ` followed by a long run without `>`, e.g. malformed page-builder output or huge inline data attributes) the cumulative backtracks exceed the default pcre.backtrack_limit of 1,000,000. When that happens the entire post content becomes null. Downstream, on themes/sites where wpautop-style filters (which coerce null back to a string) are removed, the null survives to auto_prepend_player() at priority 1000000, and `self::has_custom_player( $content )` (line 68) — typed `string $content` under strict_types=1 — throws an uncaught TypeError.
Trigger: Singular front-end view of a post whose content makes preg_replace() at line 99 exceed the PCRE backtrack limit (e.g. >1MB of `<div `-prefixed content with long `>`-free attribute runs, or many divs with long attribute runs summing past 1M backtracks). preg_replace returns null → the_content becomes null → post body renders blank; with wpautop removed, has_custom_player(null) throws TypeError (fatal).
Impact: Whole post content silently blanked on the front end when triggered; on wpautop-less sites a fatal TypeError (500) instead. Failure is content-dependent and confusing to debug.
Fix: Guard the return value: `$replaced = preg_replace( $pattern, '[beyondwords_player]', $content ); return null === $replaced ? $content : $replaced;` so a PCRE failure degrades to the original content instead of null.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.19 — on_trash_post() wipes local BeyondWords meta even when the remote DELETE failed, permanently orphaning the remote audio

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 412): on_trash_post() wipes local BeyondWords meta even when the remote DELETE failed, permanently orphaning the remote audio.

Problem: `Client::delete_audio()` returns `false` when the API does not answer 204 (timeout, 5xx, WP_Error), but `on_trash_post()` ignores the return value and unconditionally calls `Meta::remove_all_beyondwords_metadata( $post_id )`, deleting `beyondwords_content_id`/`beyondwords_project_id` locally.
Trigger: Trash a post with generated audio while the BeyondWords API is unreachable or returns an error: `delete_audio()` returns false, yet all local linkage meta is removed.
Impact: The audio remains live in the BeyondWords dashboard/playlists with no local reference left, so the delete can never be retried from WordPress (restoring the post also cannot re-link it); silent WP-dashboard desync.
Fix: Only remove local metadata when `delete_audio()` did not return false (or reschedule/queue a retry on failure): `if ( false !== \BeyondWords\Api\Client::delete_audio( $post_id ) ) { \BeyondWords\Post\Meta::remove_all_beyondwords_metadata( $post_id ); }`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.20 — Uncached file_get_contents + json_decode of lang-codes.json on every back-filled meta read, including front-end wp_head; TypeError if the file is unreadable

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 490): Uncached file_get_contents + json_decode of lang-codes.json on every back-filled meta read, including front-end wp_head; TypeError if the file is unreadable.

Problem: `get_lang_code_from_json_if_empty()` reads and JSON-decodes `assets/lang-codes.json` from disk on EVERY read of `beyondwords_language_code` that reaches the back-fill branch — there is no static/object-cache memoization. Because of the shadowing bug (php-post-2), any post retaining `beyondwords_language_id` hits this branch on every read. Additionally, under `strict_types=1`, if `file_get_contents()` fails (missing/unreadable file, e.g. broken deploy) it returns `false`, and `json_decode( false, true )` throws `TypeError: json_decode(): Argument #1 ($json) must be of type string, bool given` — an uncaught fatal inside a meta read.
Trigger: Front-end singular view of a Magic Embed post with legacy `beyondwords_language_id`: `Head::add_meta_tags()` (src/post/class-head.php:107) reads `beyondwords_language_code` on every page load → disk read + decode per view (and again for each additional read of that key in the same request). The TypeError path triggers whenever `BEYONDWORDS__PLUGIN_DIR . 'assets/lang-codes.json'` is not readable.
Impact: Repeated per-request filesystem I/O and JSON parsing on a page-render path (VIP discourages per-request file reads in hot paths); a missing asset file escalates to a site-breaking fatal on every affected meta read.
Fix: Memoize the decoded map in a static variable (`static $lang_codes = null; if ( null === $lang_codes ) { ... }`), guard the read (`$json = file_get_contents(...); if ( false === $json ) { return $value; }`), and pass only strings to json_decode.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P2.21 — Project ID accepts non-numeric input, silently degrading every API URL to /projects/0

- [ ] 🆕 **new** · `low` · _severity elevated (security driver)_ · `src/settings/class-fields.php`

```text
In the BeyondWords WordPress plugin, src/settings/class-fields.php (around line 179-190): Project ID accepts non-numeric input, silently degrading every API URL to /projects/0.

Problem: sanitize_project_id() only runs sanitize_text_field() and an is-empty check — any non-numeric string ('Project 12345', 'abc123', an ID pasted with a stray character) is accepted and stored without complaint. Every consumer then formats it numerically: Utils::validate_api_connection() builds the URL with sprintf('%s/projects/%d', ...) (class-utils.php:150) — verified sprintf('%d', 'ABC123') yields '0' under strict_types (no error) — and Api\Client::get_project()/get_video_settings() cast (int). Settings::rest_settings_response() (class-settings.php:335) hands the raw non-numeric string to editor scripts, whose companion REST routes are constrained to (?P<projectId>[0-9]+) and therefore 404.
Trigger: Admin pastes a project identifier containing any non-digit into the Project ID field on the Authentication tab and saves. The field saves 'successfully' (no settings error is queued because the value is non-empty), then validation runs against GET /projects/0.
Impact: All API traffic targets project 0: connection validation fails with a misleading 'unable to validate' notice whose debug body describes the wrong project, editor dropdown proxies return errors, and the block editor's own project/videoSettings REST calls 404 — with no hint that the stored ID itself is malformed.
Fix: Validate numerically in sanitize_project_id(): e.g. `$value = preg_replace('/[^0-9]/', '', $value);` or reject with the existing add_settings_error_message() path when `! ctype_digit( $value )`, mirroring the empty-value error.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

---

## P3 — Low · cosmetic · dead code · i18n · edge

### P3.1 — array_fill_keys() TypeError (site-wide fatal) if legacy speechkit_select_post_types is a truthy non-array

- [ ] 🔁 **outstanding** — prior P19 · `low` · `src/core/class-updater.php`

```text
In the BeyondWords WordPress plugin, src/core/class-updater.php (around line 217): array_fill_keys() TypeError (site-wide fatal) if legacy speechkit_select_post_types is a truthy non-array.

Problem: construct_preselect_setting() checks `array_key_exists(...) && ! empty( $old_settings['speechkit_select_post_types'] )` but never is_array() before passing the value to array_fill_keys(). The surrounding code is carefully defensive about untrusted legacy option data ($old_settings itself is is_array()-checked at line 207, and $taxonomy->object_type is is_array()-checked at line 226), but this one value is not. array_fill_keys() with a string/int first argument throws a TypeError in PHP 8 regardless of strict_types coercion rules (array is never coercible).
Trigger: Upgrading from pre-3.0 with a corrupted/hand-edited speechkit_settings option where speechkit_select_post_types is a truthy scalar (e.g. the string 'post' written via wp-cli `option patch` or a bad import). Updater::run() -> migrate_settings() -> construct_preselect_setting() throws at plugin-include time.
Impact: Uncaught TypeError during Plugin::init(), which executes at top level of speechkit.php — the entire site white-screens on every request until the plugin is disabled or the option fixed, because the migration re-runs each load while the version gate is open.
Fix: Add an is_array() guard mirroring the neighbouring checks: `if ( array_key_exists(...) && is_array( $old_settings['speechkit_select_post_types'] ) && ! empty( ... ) )`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.2 — strlen() on wp-config override constants fatals under strict_types if the constant is defined as a non-string

- [ ] 🔁 **outstanding** — prior P20 · `low` · `src/core/class-urls.php`

```text
In the BeyondWords WordPress plugin, src/core/class-urls.php (around line 38): strlen() on wp-config override constants fatals under strict_types if the constant is defined as a non-string.

Problem: Every accessor uses the pattern `defined( 'X' ) && strlen( \X )` (lines 38, 49, 60, 71, 82, 93). This file declares strict_types=1, so if an operator defines the override as a non-string — e.g. `define( 'BEYONDWORDS_JS_SDK_URL', false )` in wp-config.php to try to disable the SDK, or an integer/null — strlen() throws `TypeError: strlen(): Argument #1 ($string) must be of type string, bool given` instead of falling back to the default. The guard is specifically meant to tolerate misconfigured overrides (that is why strlen() is checked at all), yet a wrong-typed define produces a hard fatal.
Trigger: Site operator adds a non-string define for any of BEYONDWORDS_API_URL / BEYONDWORDS_BACKEND_URL / BEYONDWORDS_JS_SDK_URL / BEYONDWORDS_AMP_PLAYER_URL / BEYONDWORDS_AMP_IMG_URL / BEYONDWORDS_DASHBOARD_URL in wp-config.php. The next call to the accessor (get_js_sdk_url() runs during front-end player render; get_api_url() during any API call) throws.
Impact: Uncaught TypeError -> fatal error on the pages that call the accessor (front-end render for the JS SDK URL, admin/API paths for the API URL), turning a soft misconfiguration into a site-breaking crash.
Fix: Type-check instead of length-checking the raw constant: `if ( defined( 'X' ) && is_string( \X ) && '' !== \X ) { return \X; }` — or cast: `strlen( (string) \X )`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.3 — No loading/failure state for the settings-store key lists: Remove stays disabled and the Copy payload is empty with literal 'undefined' lines

- [ ] 🔁 **outstanding** — prior P15 · `low` · `src/editor/components/inspect-panel/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/index.js (around line 82-94): No loading/failure state for the settings-store key lists: Remove stays disabled and the Copy payload is empty with literal 'undefined' lines.

Problem: dataKeys, hasData and copyMeta are derived from `inspectMetaKeys`, `pluginVersion` and `wpVersion`, which come from the beyondwords/settings store. The store default is `settings: {}` and its getSettings resolver (src/settings/store/index.js:37-40) performs a single apiFetch of /beyondwords/v1/settings with no retry; if that request rejects, @wordpress/data marks the resolution failed and never re-runs it, so `inspectMetaKeys` remains undefined for the rest of the session. In that state dataKeys is `[]`, so hasBeyondwordsData() returns false and the Remove button is disabled even though the read-only fields directly above visibly contain data; the Copy button remains enabled, and getTextToCopy (helpers.js:51 `${ key }\r\n${ meta[ key ] }`) produces a payload containing no meta lines at all plus literal `plugin_version\r\nundefined` and `wp_version\r\nundefined`. The same (transient) state exists in the window before the settings request resolves.
Trigger: Open the block editor when /beyondwords/v1/settings fails (REST blocked by a security plugin, expired auth after laptop sleep, transient 5xx) — or click Copy in the short window before the request resolves. Then use the Inspect panel's Copy/Remove controls.
Impact: The support-diagnostics payload — the exact artifact support asks users to send — is silently empty/garbage, and Remove is unusable, with no feedback that settings failed to load. Degradation is permanent for the session because failed resolutions are not retried.
Fix: Gate the controls on resolution state (e.g. `hasFinishedResolution( 'getSettings' )` / `getResolutionError`) and render the buttons disabled with a hint until the key lists exist; in getTextToCopy, skip keys whose value is undefined (or substitute '') so 'undefined' never reaches the clipboard payload.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.4 — Sidebar link onClick is missing event.preventDefault(), so clicking also performs the '#beyondwords-plugin-sidebar' hash navigation

- [ ] 🔁 **outstanding** — prior P26 · `low` · `src/editor/components/open-sidebar/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/open-sidebar/index.js (around line 18): Sidebar link onClick is missing event.preventDefault(), so clicking also performs the '#beyondwords-plugin-sidebar' hash navigation.

Problem: The anchor at lines 16-24 has href="#beyondwords-plugin-sidebar" and an onClick that only calls openSidebar(). Because the click's default action is never prevented, the browser also navigates to the fragment: the editor URL becomes post.php?post=N&action=edit#beyondwords-plugin-sidebar and a new history entry is pushed. No element with that id exists, so the fragment serves no purpose.
Trigger: In the block editor, open the BeyondWords document-settings panel and click the 'BeyondWords sidebar' link rendered by OpenSidebar. The sidebar opens, but window.location gains the #beyondwords-plugin-sidebar fragment and a history entry.
Impact: URL/history pollution: the user's next Back press appears to do nothing (it only strips the hash), and reloading/bookmarking carries a dead fragment. If any element ever acquires that id, clicking would additionally scroll-jump the admin page.
Fix: Accept the event and cancel the default action: onClick={ ( event ) => { event.preventDefault(); openSidebar(); } } — or replace the anchor with <Button variant="link" onClick={ openSidebar }>.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.5 — gethostbyname() called with a full URL: blocking DNS lookup that can never return an IP

- [ ] 🔁 **outstanding** — prior P24 · `low` · `src/site-health/class-site-health.php`

```text
In the BeyondWords WordPress plugin, src/site-health/class-site-health.php (around line 212): gethostbyname() called with a full URL: blocking DNS lookup that can never return an IP.

Problem: In the error branch of add_rest_api_connection(), gethostbyname( $api_url ) is passed the full API URL. Urls::get_api_url() returns 'https://api.beyondwords.io/v1' (confirmed in src/core/class-urls.php:27-43) - a URL with scheme and path, not a hostname. gethostbyname() cannot resolve it and, per PHP semantics, returns its argument unchanged on failure, so the '%1$s' placeholder (documented in the translators comment as 'The IP address the REST API resolves to') always prints the raw URL and never an IP. In addition, gethostbyname() is a synchronous blocking DNS call with no WP-side timeout, executed precisely in the scenario where the network/DNS is already failing (the wp_remote_request returned WP_Error), and resolvers configured with search domains may attempt multiple lookups on the garbage name, adding further seconds to the already-slow admin render. Blocking DNS functions are on the VIP restricted list.
Trigger: Open Tools > Site Health > Info while the BeyondWords API is unreachable (DNS failure, firewall, outage): wp_remote_request() returns WP_Error, the code falls into the error branch and calls gethostbyname('https://api.beyondwords.io/v1').
Impact: A second synchronous blocking network lookup stacked on top of the failed 5s HTTP request in the admin page render, and a diagnostic message that never contains the promised IP address - support gets misleading output exactly when they need it.
Fix: Remove the DNS lookup entirely and print only $response->get_error_message(); if the IP is genuinely wanted, resolve the host component instead: gethostbyname( (string) wp_parse_url( $api_url, PHP_URL_HOST ) ), and cache/skip it on VIP.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.6 — cached_get() returns decoded non-2xx error bodies as if they were entity data

- [ ] 🆕 **new** · `low` · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 549): cached_get() returns decoded non-2xx error bodies as if they were entity data.

Problem: When the response is a non-2xx, cached_get() skips the transient write but still `return $decoded;` — the decoded error body. So get_languages()/get_voices()/get_project()/get_video_settings()/template getters return e.g. `['message' => 'Unauthorized']` for a 401, an array that callers cannot distinguish from a valid list. Concretely: the REST proxies in src/settings/class-settings.php:403-451 (rest_video_settings_response, rest_project_response, rest_*_templates_response) pass this straight into `new \WP_REST_Response( $response )`, so the block editor receives the upstream error payload with an HTTP 200 status; SelectVoice::element() and SettingsFields renderers accept it as an array and quietly render empty dropdowns (their per-row isset()/empty() guards happen to skip string rows). Auth/connection failures are therefore silently swallowed as "no options" instead of surfacing as errors.
Trigger: Any non-2xx JSON response from the BeyondWords API on the cached GET endpoints — e.g. an invalid API key (401) while an editor opens a post or the block editor calls the settings/voices REST proxies.
Impact: Editor UI shows empty language/voice/template dropdowns with no error, and the plugin's REST proxies return 200 responses whose body is an upstream error object, misleading the editor JS.
Fix: Return null (already in the declared return union) when `is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300`, and only return $decoded for 2xx; let the REST proxies map null to a WP_Error/non-200 status.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.7 — Default error message embeds raw HTML that every consumer escapes or strips

- [ ] 🆕 **new** · `low` · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 622): Default error message embeds raw HTML that every consumer escapes or strips.

Problem: save_error_message() builds the fallback message with a literal anchor tag: `'API request error. Please contact %s.'` filled with `<a href="mailto:support@beyondwords.io">support@beyondwords.io</a>`, and stores it in `beyondwords_error_message` meta. Both places that render this meta neutralise the markup: the classic-editor metabox prints it through esc_html() (src/editor/classic/class-metabox.php:263), so editors see the raw `<a href="mailto:...">` source text verbatim; the posts-list column runs it through wp_kses with an allowlist of only `span.class` (src/posts-list/class-column.php:24-28,102), which strips the tag. The link never renders as a link anywhere.
Trigger: Any API failure where the response yields an empty message (e.g. WP_Error transport failure, or non-JSON error body) for a REST-integration post: save_error_message() stores the HTML-laden default, then the user opens the classic editor metabox for that post.
Impact: Broken-looking error text containing raw HTML markup in the editor metabox; cosmetic but user-facing on every connection failure.
Fix: Store a plain-text message (`'API request error. Please contact support@beyondwords.io.'`) and let renderers add markup, or render the metabox error with wp_kses allowing `a[href]` instead of esc_html.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.8 — Client::get_voice() is dead code (no callers anywhere in the plugin)

- [ ] 🆕 **new** · `low` · `src/api/class-client.php`

```text
In the BeyondWords WordPress plugin, src/api/class-client.php (around line 333): Client::get_voice() is dead code (no callers anywhere in the plugin).

Problem: A full-repo search (src/, plugin root, excluding vendor/node_modules/tests) finds no call sites for Client::get_voice(); the voices REST route and editor components use get_voices() directly. The method also carries a latent defect if ever wired up: because get_voices() can return a non-2xx error body (see php-api-5), `array_column( $voices, null, 'id' )` on such a body yields a list of scalar strings (verified on PHP 8.4: array_column keeps scalar rows when the index key is absent), and `[ $voice_id ] ?? false` with $voice_id = 0 would return a string, violating the declared `object|array|false` return type (TypeError under strict_types). As no caller exists today, that path is unreachable — the actionable issue is the dead method itself.
Trigger: None currently — the method is unreachable from plugin code; only a third-party caller could invoke it.
Impact: Maintenance burden and a latent strict-types TypeError if the method is ever used with an error-shaped voices response.
Fix: Remove the method, or if it is kept as public API, guard it: verify $voices is a list of arrays before array_column and return false otherwise.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.9 — cleanup_custom_fields() loads every meta_id into memory and issues one giant IN() DELETE, contradicting its own batching comment

- [ ] 🆕 **new** · `low` · `src/core/class-uninstaller.php`

```text
In the BeyondWords WordPress plugin, src/core/class-uninstaller.php (around line 80): cleanup_custom_fields() loads every meta_id into memory and issues one giant IN() DELETE, contradicting its own batching comment.

Problem: The docblock claims deletion is done 'one meta_id at a time to keep individual queries fast', but the implementation calls $wpdb->get_col() to fetch ALL meta_ids for each of the 39 keys into a PHP array, then runs a single `DELETE ... WHERE meta_id IN ( <every id> )`. There is no LIMIT/chunking anywhere. On a large publisher site (e.g. a couple of million posts with beyondwords_generate_audio / beyondwords_content_id rows), get_col() materialises millions of string zvals (hundreds of MB) and the imploded IN() list grows to tens of MB, risking PHP memory exhaustion or exceeding MySQL max_allowed_packet — and the huge single DELETE takes the very long postmeta lock the comment says it was written to avoid.
Trigger: Uninstall the plugin on a site with a very large number of BeyondWords meta rows (millions of rows for one key). get_col() returns the full id list; implode() builds one statement containing all of them.
Impact: Fatal 'Allowed memory size exhausted' or a failed over-sized query mid-uninstall, aborting cleanup partway (options already deleted, postmeta left behind) with no error surfaced to the user. Also a long postmeta table lock on exactly the large sites the code claims to protect.
Fix: Actually batch: loop `SELECT meta_id ... WHERE meta_key = %s LIMIT 1000` and delete those ids per iteration until no rows remain (or use `DELETE ... WHERE meta_key = %s LIMIT 1000` in a loop). Update the docblock to match the real strategy.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.10 — construct_preselect_setting(): get_taxonomy('category') returns false when network-activated, silently widening category-gated preselect to ALL posts

- [ ] 🆕 **new** · `low` · `src/core/class-updater.php`

```text
In the BeyondWords WordPress plugin, src/core/class-updater.php (around line 224): construct_preselect_setting(): get_taxonomy('category') returns false when network-activated, silently widening category-gated preselect to ALL posts.

Problem: The v2-to-v3 migration converts speechkit_selected_categories into a per-post-type term gate, guarded by `if ( $taxonomy && is_array( $taxonomy->object_type ) )`. When the plugin is network-activated on multisite, wp-settings.php includes network plugins BEFORE create_initial_taxonomies() runs (verified in WP 6.6.2/6.7.1 wp-settings.php ordering: network-plugin loop -> muplugins_loaded -> vars.php -> create_initial_taxonomies()), so get_taxonomy('category') is false at Updater::run() time and the category block is silently skipped. The post types were already seeded with '1' (MODE_ALL) at line 217, so the migrated preselect keeps '1' instead of the category restriction.
Trigger: A network-activated multisite install upgrading from a pre-3.0 speechkit version that used speechkit_selected_categories: migrate_settings() runs at network-plugin include time, before 'category' is registered.
Impact: The user's category restriction is dropped and 'Generate audio' becomes preselected for EVERY post of the affected post types instead of only selected categories — which can lead to unintended audio generation (a billable action) after the upgrade. Rare population (ancient upgrades + network activation), hence low severity, but the guard turns a fatal into silent data loss.
Fix: Same root fix as php-core-2: run migrations on the 'init' hook when taxonomies/post types exist. Minimally, hardcode the fallback `['post']` for the object_type when get_taxonomy('category') is unavailable, since 'category' is guaranteed to attach to 'post'.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.11 — get_backend_url() / BEYONDWORDS_BACKEND_URL is dead code with an empty-string production value

- [ ] 🆕 **new** · `low` · `src/core/class-urls.php`

```text
In the BeyondWords WordPress plugin, src/core/class-urls.php (around line 48): get_backend_url() / BEYONDWORDS_BACKEND_URL is dead code with an empty-string production value.

Problem: The constant BEYONDWORDS_BACKEND_URL is '' (line 28) and get_backend_url() (lines 48-54) has zero callers anywhere in src/ (verified by grep across all accessors — every other Urls getter has consumers; this one has none). Any future caller that assumed a usable base URL would silently build relative/malformed URLs from the empty default.
Trigger: Not reachable today — no call sites exist. The risk is latent: a new consumer treating the return value as a valid absolute URL base.
Impact: Maintenance hazard only: dead accessor plus a footgun default (empty string typed as a URL). No runtime effect in current code.
Fix: Delete the constant and accessor (v7 already removed the legacy backend integration), or document why it is retained and give it a non-empty production value if it is about to gain a consumer.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.12 — Missing wp_set_script_translations() for the block-editor bundle — all editor JS strings untranslatable

- [ ] 🆕 **new** · `low` · `src/editor/block/class-assets.php`

```text
In the BeyondWords WordPress plugin, src/editor/block/class-assets.php (around line 47): Missing wp_set_script_translations() for the block-editor bundle — all editor JS strings untranslatable.

Problem: The 'beyondwords-block-js' handle is enqueued without a wp_set_script_translations( 'beyondwords-block-js', 'speechkit' ) call, and `grep -rn wp_set_script_translations src/ speechkit.php` confirms the function is never called anywhere in the plugin. The bundle sources import @wordpress/i18n in 20+ modules (e.g. src/editor/components/generate-audio/index.js, inspect-panel/index.js, settings-panel/*.js — verified via grep) and pass the 'speechkit' text domain. Without wp_set_script_translations(), WordPress never prints the locale data for the handle, so every wp.i18n.__() call in the block-editor UI permanently returns the English source string, even when PHP-side strings on the same screen are translated via the plugin's language packs.
Trigger: Set the site locale to anything other than en_US with speechkit translations installed, open the block editor for a compatible post type: the BeyondWords sidebar/panels render English strings while the PHP-rendered parts of the admin are translated.
Impact: The entire BeyondWords block-editor UI (sidebar, panels, notices) is stuck in English on every non-English site — a user-facing functional defect across the whole localized install base, and an inconsistency with the translated PHP-side UI.
Fix: After the wp_enqueue_script() call add: `wp_set_script_translations( 'beyondwords-block-js', 'speechkit', BEYONDWORDS__PLUGIN_DIR . 'languages' );` (the third argument only if the plugin ships local .json translation files; omit it for wp.org language packs).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.13 — check.js is dead code that is never imported, with a latent TypeError in its withSelect mapping

- [ ] 🆕 **new** · `low` · `src/editor/components/block-attributes/check.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/block-attributes/check.js (around line 21): check.js is dead code that is never imported, with a latent TypeError in its withSelect mapping.

Problem: The entire module is unreachable: src/editor/components/block-attributes/index.js requires only ./addAttributes and ./addControls, a repo-wide grep finds no other import of ./check or BlockAttributesCheck, and `git log -S "block-attributes/check"` confirms the file has never been imported at any point in the repository history. The intended gate — only rendering children when the current post type supports custom-fields — is therefore never applied to anything. The module also carries a latent hazard for anyone who wires it up: line 21 destructures `const { getCurrentPostType } = select( 'core/editor' );` without checking the store exists. `withSelect`'s registry `select()` returns undefined for an unregistered store, so mounting BlockAttributesCheck in any block-editor context that does not register the core/editor store (widgets editor, site editor, standalone block-editor instances) throws `TypeError: Cannot destructure property 'getCurrentPostType' of 'select(...)' as it is undefined`, crashing the React tree it is rendered in. Today the bundle is only enqueued on compatible post-type edit screens (src/editor/block/class-assets.php line 41), so no current code path reaches this — it is dead weight and a trap, not a live crash.
Trigger: Currently none — the file is never imported, so no runtime path executes it. The latent throw at line 21 fires only if a future change imports check.js and renders BlockAttributesCheck in an editor where the 'core/editor' store is not registered (e.g. widgets or site editor).
Impact: No user-facing impact today. Maintenance hazard: the file implies a custom-fields gate on the block-attribute UI that does not actually exist, misleading future work; if naively wired up outside the post editor it would crash the component tree with an unhandled TypeError.
Fix: Delete src/editor/components/block-attributes/check.js (block attributes serialize into post_content, so a custom-fields/post-meta support gate is not needed for this component). If gating is actually desired, import it from addControls.js and make the mapping store-safe first, e.g. `const postType = select( 'core/editor' )?.getCurrentPostType?.();` before calling `select( coreStore ).getPostType( postType )`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.14 — Missing wp_set_script_translations() for the classic Content ID metabox script

- [ ] 🆕 **new** · `low` · `src/editor/components/content-id/class-assets.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/class-assets.php (around line 50): Missing wp_set_script_translations() for the classic Content ID metabox script.

Problem: 'beyondwords-metabox--content-id' is registered with a 'wp-i18n' dependency (line 53) precisely because classic-metabox.js calls wp.i18n.__( 'Content fetched and saved successfully.', 'speechkit' ), __( 'Failed to save fetched content.', 'speechkit' ) and __( 'Failed to fetch content. Please check the Content ID.', 'speechkit' ), but wp_set_script_translations() is never called for the handle (or anywhere in the plugin). The translation data for the 'speechkit' domain is therefore never loaded into wp.i18n, so these user-facing notices always render in English. Note the last string is also persisted into the beyondwords_error_message post meta by the JS error path, baking the untranslated string into stored data that the editor later re-displays.
Trigger: On a non-English site with speechkit translations, edit a compatible post in the classic editor and click the Content ID "Fetch" button: the success/error notices (and the beyondwords_error_message meta written on failure) are English regardless of locale.
Impact: Untranslated user-facing admin notices on localized sites, plus an English error string persisted to post meta; declaring the wp-i18n dependency without binding translations makes the i18n plumbing dead code.
Fix: After wp_register_script() (or after wp_enqueue_script() on line 67) add: `wp_set_script_translations( 'beyondwords-metabox--content-id', 'speechkit' );`.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.15 — Save-vs-fetch error discrimination relies on exact Error message string, so a network-level save rejection shows the wrong notice and rewrites meta

- [ ] 🆕 **new** · `low` · `src/editor/components/content-id/classic-metabox.js`
- _Related to prior P5 (classic content-id save/fetch error handling)_

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/classic-metabox.js (around line 313): Save-vs-fetch error discrimination relies on exact Error message string, so a network-level save rejection shows the wrong notice and rewrites meta.

Problem: The catch distinguishes save failures only via fetchError.message === 'Failed to save', the string thrown when savePostMeta gets a non-ok HTTP response (line 101). If savePostMeta's fetch rejects at the network layer instead (connection drop, DNS failure — TypeError 'Failed to fetch'), the message doesn't match, so a successful content fetch followed by a failed save is routed into the fetch-failed branch: it shows 'Failed to fetch content. Please check the Content ID.' (wrong diagnosis) and then issues a second savePostMeta writing errorMeta (lines 325-345), which — if connectivity has returned — persists the error message even though the content ID was valid and the full fetched meta was in hand.
Trigger: Classic editor: content fetch succeeds, then the connection blips during the wp/v2 save so fetch() rejects rather than returning an HTTP error; a moment later connectivity returns and the errorMeta save succeeds.
Impact: User gets a misleading 'check the Content ID' notice for a save-side network failure, and beyondwords_error_message is persisted while the successfully fetched meta is discarded.
Fix: Discriminate by phase rather than message text: e.g. set a flag once the content fetch has succeeded (or throw a custom Error subclass / attach error.isSaveError = true in savePostMeta) and branch on that, treating any error after a successful content fetch as a save failure.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.16 — Meta save assumes a wp/v2 route exists for every compatible post type; classic CPTs with show_in_rest=false make Fetch fail 100% of the time

- [ ] 🆕 **new** · `low` · `src/editor/components/content-id/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/classic-metabox.js (around line 89): Meta save assumes a wp/v2 route exists for every compatible post type; classic CPTs with show_in_rest=false make Fetch fail 100% of the time.

Problem: savePostMeta always POSTs to wp/v2/{restBase}/{postId}. The metabox and this script are enabled for every 'compatible' post type, and Settings\Utils::get_compatible_post_types() only requires title/editor/custom-fields support — it does not require show_in_rest. For a CPT registered with show_in_rest => false (the archetypal classic-editor CPT, which is exactly the audience of this classic script), class-content-id.php falls back to rest_base = the post type slug (line 60) and getRestBase happily returns it, but no wp/v2 route exists at all: every save gets 404 rest_no_route → 'Failed to save' → the 'Failed to save fetched content.' notice. The content fetch itself succeeds, so the user repeatedly sees a working lookup that can never be applied, with no hint why.
Trigger: Register a compatible CPT with 'show_in_rest' => false (or omitted, the default), edit a post of that type in the classic editor, enter a valid content ID and click Fetch: the beyondwords/v1 lookup succeeds, the wp/v2 save 404s every time.
Impact: The Fetch feature is deterministically broken for classic-only custom post types, failing with a generic save error after every attempt; nothing is persisted.
Fix: In ContentId::element(), only render the Fetch button (or render it disabled with an explanatory title) when $post_type_object && $post_type_object->show_in_rest; alternatively save via a plugin-owned beyondwords/v1 endpoint (or admin-ajax) that does not depend on the post type being exposed in the wp/v2 REST API.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.17 — Catch-all conflates 'meta save failed' with 'content fetch failed', persisting a misleading error after a successful fetch

- [ ] 🆕 **new** · `low` · `src/editor/components/content-id/index.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/content-id/index.js (around line 125): Catch-all conflates 'meta save failed' with 'content fetch failed', persisting a misleading error after a successful fetch.

Problem: In the success path, `await updatePostMeta( postId, meta )` (line 120) runs inside the same try as the BeyondWords fetch. If the content fetch succeeds but the wp/v2 meta save rejects (REST error, meta rejected, transient failure), control falls into the single catch (line 125), which writes errorMeta with the message 'Failed to fetch content. Please check the Content ID.' — both to the DB (retry via the same failing updatePostMeta, usually failing again) and to editor state via editPost (line 139). The classic implementation explicitly distinguishes this case (classic-metabox.js lines 313-322 show 'Failed to save fetched content.'); the React version lost that distinction. It also replaces the successfully-fetched canonical meta with errorMeta in editor state, discarding the fetched preview_token/project_id values that were available in memory.
Trigger: Click Fetch with a valid content ID; the beyondwords/v1 proxy returns 200 but the subsequent POST /wp/v2/{rest_base}/{id} rejects (e.g. transient 5xx, or a post type whose meta write is rejected).
Impact: The user is told to check a Content ID that was verifiably correct, an incorrect diagnosis is persisted into beyondwords_error_message when the retry save succeeds, and the fetched values held in memory are thrown away instead of at least being reflected via editPost.
Fix: Wrap updatePostMeta in its own try/catch (or flag which phase threw) and, on save failure after a successful fetch, keep the fetched meta in editPost and surface a distinct 'Failed to save fetched content.' message, matching classic-metabox.js behaviour.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.18 — Error-notice CSS enqueued on all block-editor screens — missing compatible-post-type gate its sibling has

- [ ] 🆕 **new** · `low` · `src/editor/components/error-notice/class-assets.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/error-notice/class-assets.php (around line 41): Error-notice CSS enqueued on all block-editor screens — missing compatible-post-type gate its sibling has.

Problem: enqueue_block_assets() only checks Core\Utils::is_gutenberg_page() before enqueuing error-notice.css, unlike the directly analogous Sidebar\Assets::enqueue_block_assets() (src/editor/components/sidebar/class-assets.php:39) which additionally gates on `in_array( get_post_type(), Settings\Utils::get_compatible_post_types(), true )`. is_gutenberg_page() is true not only in the post editor but on every screen whose WP_Screen has is_block_editor set — the Site Editor (site-editor.php), the block Widgets editor (widgets.php), and post editors for incompatible post types. The selectors in error-notice.css (.beyondwords-sidebar__post-status-description--failed/--error/--payment-required) are only rendered by components inside build/index.js, which IS post-type gated (src/editor/block/class-assets.php:41), so on all those extra screens the stylesheet can never match anything. Because the hook is enqueue_block_assets, the rules are additionally duplicated into the editor content iframe via _wp_get_iframed_editor_assets(). (They are also verbatim duplicates of rules already present in sidebar.css lines 11-15.)
Trigger: Open the Site Editor, the block Widgets screen, or the block editor for any post type not in get_compatible_post_types() (with a valid API connection saved): a request for error-notice.css is made and a <style>/<link> is emitted even though the BeyondWords editor bundle that renders the targeted class names is not loaded there.
Impact: A needless stylesheet request/inline style on every non-target block-editor screen and inside the editor iframe — minor admin-side asset bloat, and an inconsistency with the sibling Sidebar\Assets gating that invites divergence during the v7 refactor.
Fix: Mirror Sidebar\Assets: after the is_gutenberg_page() check add `if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) { return; }` — or drop the file and this class entirely, since sidebar.css already contains identical rules.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.19 — All rest_api_response() error paths surface as HTTP 500 — WP_Error data lacks ['status'], and HTTP codes are misused as WP_Error codes

- [ ] 🆕 **new** · `low` · `src/editor/components/inspect-panel/class-inspect-panel.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/class-inspect-panel.php (around line 356-403): All rest_api_response() error paths surface as HTTP 500 — WP_Error data lacks ['status'], and HTTP codes are misused as WP_Error codes.

Problem: Every error branch returns rest_ensure_response( new \WP_Error( <int http code>, <message>, <data without 'status'> ) ). rest_ensure_response() passes WP_Error through unchanged, and WP_REST_Server derives the HTTP status exclusively from $error_data['status'] (rest_convert_error_to_response()), defaulting to 500 when absent. So the intended 400 for 'Invalid Project ID' (line 356-363) and 'Invalid Content ID' (365-373), the 500 connection error (379-386), and the relayed upstream code (392-403, e.g. a BeyondWords 404) ALL reach the client as HTTP 500. Additionally the first WP_Error argument is meant to be a machine-readable slug, not an integer HTTP status.
Trigger: Editor's Fetch/Inspect JS requests GET /wp-json/beyondwords/v1/projects/123/content/<unknown-id>; the BeyondWords API answers 404; WordPress responds with HTTP status 500 (body code 404). Similarly, any request the code classifies as a 400 bad-request is served as a 500.
Impact: The editor JS and any monitoring cannot distinguish user/validation errors from genuine server failures by status code; upstream 4xx conditions are logged and alerted as WordPress 500s.
Fix: Use slug codes and put the HTTP status in the data array, e.g. new \WP_Error( 'beyondwords_invalid_project', __( 'Invalid Project ID', 'speechkit' ), [ 'status' => 400, 'projectId' => $project_id ] ); for the relay branch pass [ 'status' => (int) $code, 'body' => $body ].

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.20 — rest_api_response() does not handle Client::get_content() returning false, yielding an error-less WP_Error('') and an 'Undefined array key 0' warning inside WP core

- [ ] 🆕 **new** · `low` · `src/editor/components/inspect-panel/class-inspect-panel.php`
- _Related to prior P4 (get_content() WP_Error handling)_

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/class-inspect-panel.php (around line 375-392): rest_api_response() does not handle Client::get_content() returning false, yielding an error-less WP_Error('') and an 'Undefined array key 0' warning inside WP core.

Problem: Client::get_content() is declared array|\WP_Error|false and returns false when the supplied project id is falsy and the beyondwords_project_id option is also empty (src/api/class-client.php:120-126). rest_api_response() only checks is_wp_error(); for $response === false, wp_remote_retrieve_response_code()/wp_remote_retrieve_body() both return '' (isset on a scalar subscript is false). Under PHP 8 semantics '' < 200 is true (200 is cast to '200' for the string comparison), so the error branch runs and constructs new \WP_Error( '', sprintf(..., '') ). WP_Error::__construct() discards everything when the code is empty, producing a WP_Error instance with zero registered errors; rest_ensure_response() returns it unchanged and WP core's rest_convert_error_to_response() then evaluates $errors[0] on an empty array — an 'Undefined array key 0' PHP warning — and emits a bare null body with status 500.
Trigger: A logged-in user with edit_posts requests GET /wp-json/beyondwords/v1/projects/0/content/abc on a site whose beyondwords_project_id option is empty/unset: '0' matches the route regex [0-9]+ and passes is_numeric(), but is falsy inside get_content(), which therefore returns false.
Impact: PHP warning emitted from WP core on every such request (fatal on installs that promote warnings to exceptions), and the client receives a malformed 'null' 500 response instead of a meaningful error object.
Fix: After calling get_content(), handle the documented false return explicitly, e.g. if ( false === $response || is_wp_error( $response ) ) { return rest_ensure_response( new \WP_Error( 'beyondwords_unreachable', __( 'Could not connect to BeyondWords API', 'speechkit' ), [ 'status' => 500 ] ) ); } (and/or reject a zero project id up-front alongside the is_numeric() check).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.21 — Duplicate HTML id 'beyondwords__inspect--remove' on the Remove button and its confirmation span; the remove-confirm tick is unreachable dead markup

- [ ] 🆕 **new** · `low` · `src/editor/components/inspect-panel/class-inspect-panel.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/class-inspect-panel.php (around line 131): Duplicate HTML id 'beyondwords__inspect--remove' on the Remove button and its confirmation span; the remove-confirm tick is unreachable dead markup.

Problem: render_meta_box_content() gives both the <button> (line 125) and the dashicons <span> nested inside it (line 131) the same id 'beyondwords__inspect--remove'. The parallel Copy control uses distinct ids ('beyondwords__inspect--copy' / 'beyondwords__inspect--copy-confirm'), so the span was evidently meant to be 'beyondwords__inspect--remove-confirm'. inspect.js only toggles the copy confirm span; nothing references a remove confirm, and with the duplicate id document.getElementById would resolve to the button anyway — the tick can never be shown.
Trigger: Render the Inspect metabox on any classic-editor post screen: the DOM contains two elements sharing id 'beyondwords__inspect--remove' (invalid HTML), and confirming the Remove dialog never displays the dashicons-yes acknowledgement.
Impact: Invalid HTML/accessibility (duplicate id), and the intended visual confirmation for the Remove action is dead code that silently never appears.
Fix: Rename the inner span's id to 'beyondwords__inspect--remove-confirm' (mirroring the copy control) and, if the confirmation UX is desired, un-hide it in inspect.js after the user confirms removal; otherwise delete the span.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.22 — Confirm-dialog string can never be translated: no wp_set_script_translations() for the script handle

- [ ] 🆕 **new** · `low` · `src/editor/components/inspect-panel/js/inspect.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/inspect-panel/js/inspect.js (around line 36-40): Confirm-dialog string can never be translated: no wp_set_script_translations() for the script handle.

Problem: The click handler calls `wp.i18n.__( 'Remove all BeyondWords data when the post is saved?', 'speechkit' )`, but a repo-wide grep shows the plugin never calls wp_set_script_translations() for any handle — including 'beyondwords-inspect' (enqueued in src/editor/components/inspect-panel/class-assets.php). Without registered translation data for the 'speechkit' domain, wp.i18n.__() always returns the English source string, so the string is unreachable by locale packs. The same wiring gap applies to the block bundle 'beyondwords-block-js', e.g. the 'Copied data to clipboard.' notice at src/editor/components/inspect-panel/index.js:115 and every other __() string in the editor JS.
Trigger: On any non-English WordPress site, open a post in the classic editor and click the Inspect metabox 'Remove' button: the browser confirm() shows English text while the surrounding PHP-rendered metabox strings are translated.
Impact: User-facing i18n defect only (no runtime error): the destructive-action confirmation — the one string a non-English user most needs to understand — is always English, as are all block-editor JS strings.
Fix: After the wp_enqueue_script call in class-assets.php add `wp_set_script_translations( 'beyondwords-inspect', 'speechkit' );` (and likewise `wp_set_script_translations( 'beyondwords-block-js', 'speechkit' );` in src/editor/block/class-assets.php).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.23 — selected() missing $display=false -- attribute echoed twice, stray text inside <select>

- [ ] 🆕 **new** · `low` · `src/editor/components/select-voice/class-select-voice.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/class-select-voice.php (around line 242): selected() missing $display=false -- attribute echoed twice, stray text inside <select>.

Problem: In render_language_select(), the option loop calls selected( strval( $language['code'] ), strval( $selected_lang_code ) ) with only two arguments. WordPress's selected( $selected, $current = true, $display = true ) ECHOES the result when $display is true, and also returns it. Because printf's arguments are evaluated before printf outputs anything, the matching language's iteration echoes a stray " selected='selected'" directly into the <select> markup stream BEFORE the <option> tag, and the same string is then inserted into the option via the %s placeholder. Every other selected() call in this file (lines 231, 288, 295, 360, 367) correctly passes false as the third argument -- line 242 is the one inconsistent call.
Trigger: Open any post in the classic editor that has a saved beyondwords_language_code (i.e. $selected_lang_code matches one of the languages returned by the API). The matching iteration of the foreach at line 234 double-outputs the attribute.
Impact: Invalid HTML: a bare text node " selected='selected'" is emitted inside the <select> element before the selected <option>. Browsers do not render stray text inside <select>, and the option still carries the attribute via the printf placeholder, so the correct language remains selected -- but the page emits duplicate/misplaced markup, fails HTML validation, and can confuse DOM-diffing tooling/tests.
Fix: Pass false as the third argument, matching the sibling calls: selected( strval( $language['code'] ), strval( $selected_lang_code ), false ).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.24 — Loading spinner uses hardcoded root-relative /wp-admin/ URL -- 404 on subdirectory installs

- [ ] 🆕 **new** · `low` · `src/editor/components/select-voice/class-select-voice.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/class-select-voice.php (around line 515): Loading spinner uses hardcoded root-relative /wp-admin/ URL -- 404 on subdirectory installs.

Problem: render_loading_spinner() hardcodes src="/wp-admin/images/spinner.gif". On any WordPress install where core does not live at the domain root -- WP installed in a subdirectory (example.com/blog/wp-admin/...), 'WordPress in its own directory' setups (/wp/), or subdirectory multisites whose main install is off-root -- this root-relative path 404s and the spinner image is broken. classic-metabox.js's toggleLoader() un-hides this exact element (querySelector('.beyondwords-settings__loader')) during every voices/project fetch, so on affected installs users see a broken-image icon instead of a spinner while requests are in flight.
Trigger: WordPress core installed anywhere other than the web root (siteurl = https://example.com/blog). Open a customized post in the classic editor and change the Language dropdown: toggleLoader(true) reveals <img src="/wp-admin/images/spinner.gif"> which resolves to https://example.com/wp-admin/images/spinner.gif -> 404 -> broken image glyph.
Impact: Broken loading indicator (broken-image icon) during voice/language fetches on all subdirectory installs. Cosmetic but user-visible, and an unnecessary 404 request on every affected edit screen interaction.
Fix: Build the URL with admin_url(): src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" (and add an empty alt="" while touching the tag).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.25 — REST endpoints serve upstream API failures (null/false/error objects) as HTTP 200 success payloads

- [ ] 🆕 **new** · `low` · `src/editor/components/select-voice/class-select-voice.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/class-select-voice.php (around line 624): REST endpoints serve upstream API failures (null/false/error objects) as HTTP 200 success payloads.

Problem: languages_rest_api_response() (lines 621-625) and voices_rest_api_response() (lines 637-643) pass Client::get_languages()/get_voices() straight into new \WP_REST_Response( ... ) with no status or shape handling. Client methods are typed array|null|false where null/false signal failure (transport WP_Error yields body '' -> json_decode -> null), and on non-2xx upstream responses cached_get() returns the DECODED ERROR BODY (e.g. ['message' => 'Unauthorized'] -- see class-client.php:539-549, the <300 check only gates caching). So the plugin's own REST endpoints reply 200 OK with a body of null, or worse, 200 OK with {"message":"Unauthorized"} presented as if it were the languages/voices list. Consumers cannot distinguish failure from data. The class itself demonstrates the intended coercion for the classic path -- the private get_languages() wrapper (lines 185-188) exists precisely because the raw return is unsafe -- but the REST handlers skip it. The bundled classic-metabox.js happens to defend (Array.isArray at line 382), but the block editor consumes these endpoints too.
Trigger: BeyondWords API key revoked or API returning errors; an editor's browser (block editor sidebar or classic JS) calls GET /wp-json/beyondwords/v1/languages or /wp-json/beyondwords/v1/languages/en-US/voices. Response: HTTP 200 with body null or {"message":"..."} instead of an error status.
Impact: Editor UIs receive a 200 'success' containing garbage: dropdowns silently render empty (or JS consumers that .map()/.filter() a non-array throw client-side), with no way to surface the real upstream error to the user. Debugging is harder because the REST layer masks the failure as success.
Fix: In both handlers, validate the Client result: if it is not an array (or is an assoc error object rather than a list), return new \WP_Error( 'beyondwords_api_error', ..., [ 'status' => 502 ] ) or a WP_REST_Response with a non-2xx status; otherwise return the list. At minimum reuse the coercion pattern from the private get_languages()/get_voices_for_language() helpers so the endpoints always emit a JSON array.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.26 — hydrate() captures the saved voice id before the fetch and re-applies it after, silently reverting a Voice change the user makes during the fetch window

- [ ] 🆕 **new** · `low` · `src/editor/components/select-voice/classic-metabox.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/select-voice/classic-metabox.js (around line 286): hydrate() captures the saved voice id before the fetch and re-applies it after, silently reverting a Voice change the user makes during the fetch window.

Problem: hydrate() snapshots savedVoiceId from the Voice select before starting the fetch (lines 285-286) and, when the fetch resolves, calls renderModels(savedVoiceId) (lines 297-301), which rebuilds the Voice select's options via replaceChildren and sets its value back to the snapshot (lines 480-492). The Model select is explicitly disabled during the fetch to block interaction-during-fetch (lines 292-295), and a concurrent Language change is handled by the reqId supersede in loadVoices — but the Voice select is left enabled and visible (for a saved customized post the PHP renders it populated and shown, class-select-voice.php lines 343-373). A voice chosen by the user during the fetch window is therefore discarded when the stale snapshot is re-applied.
Trigger: Open a saved customized post in the classic editor; while hydrate()'s voices fetch is still in flight (a REST round-trip proxying to the remote API — noticeable on a cold transient cache), pick a different Voice from the already-populated dropdown. When the fetch resolves, the dropdown snaps back to the previously saved voice.
Impact: The user's new voice selection is silently reverted; if they don't notice the dropdown changing back and click Update, the old voice is persisted instead of the one they chose. Low severity: the window is one fetch and the reverted value is visible in the UI if the user looks.
Fix: Read the current selection at render time instead of using the pre-fetch snapshot: inside the .then, use `const currentId = voiceSelect ? voiceSelect.value : savedVoiceId;` and pass that to renderModels — or skip the re-render entirely when `voiceSelect.value !== savedVoiceId` (the user has taken over).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.27 — is_player_disabled_for_post() skips the embed-validity fallback, diverging from get_effective_embed() (editor shows "None", front end still renders a player)

- [ ] 🆕 **new** · `low` · `src/editor/components/settings-fields/class-settings-fields.php`

```text
In the BeyondWords WordPress plugin, src/editor/components/settings-fields/class-settings-fields.php (around line 338): is_player_disabled_for_post() skips the embed-validity fallback, diverging from get_effective_embed() (editor shows "None", front end still renders a player).

Problem: get_effective_embed() documents that it 'centralises the source×output→asset resolution so the dropdown's shown default and the rendered player can never diverge', and it resolves a persisted embed that no longer fits the current Source × Output to EMBED_NONE (lines 316-318). But is_player_disabled_for_post() — the function Player::is_enabled() (src/player/class-player.php:163) actually uses to decide whether to render — treats ANY non-empty stored embed as authoritative without the validity check: a stale 'video_post' value simply compares !== 'none' and returns false (enabled). Result: the editor dropdown (render_player_section → get_effective_embed) shows Embed: None, while the front end still renders a player; ConfigBuilder::merge_post_settings() then also resolves the embed to None (class-config-builder.php:73) and emits no video/summary params, so a default audio player appears on a post whose effective embed is None. The two resolvers the docblock promises can never diverge do diverge.
Trigger: A post has beyondwords_embed = 'video_post' stored; beyondwords_output is later changed from 'video' to 'audio' without rewriting the embed meta — e.g. via the block editor's direct REST meta write, WP-CLI, or any API client (the classic-metabox save() path is the only one that co-writes both). Then: editor panel shows Embed 'None' (get_effective_embed → invalid → EMBED_NONE), but front-end view → Player::is_enabled → is_player_disabled_for_post → 'video_post' !== 'none' → false → player renders with SDK defaults.
Impact: Editor UI and front end contradict each other: the publisher sees Embed: None yet the audio player still shows on the live post, and there is no way to see why without inspecting raw meta. Violates the module's own documented invariant.
Fix: Delegate to the canonical resolver so both paths share the validity fallback: `public static function is_player_disabled_for_post( int $post_id ): bool { return self::EMBED_NONE === self::get_effective_embed( $post_id ); }` (get_effective_embed already handles the empty-meta + legacy beyondwords_disabled fallback identically).

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.28 — Rejected getProject/getVoices resolutions never settle on WP 5.9-6.1: permanent spinner hides the whole Voice section

- [ ] 🆕 **new** · `low` · `src/editor/components/settings-panel/voice-section.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/settings-panel/voice-section.js (around line 69): Rejected getProject/getVoices resolutions never settle on WP 5.9-6.1: permanent spinner hides the whole Voice section.

Problem: `projectResolved` relies on `hasFinishedResolution( 'getProject', [ projectId ] )` returning true when the resolver FAILS (the comment says 'resolved or failed'), and `loadingProject` (line 91) hides every field except the toggle until then. The store resolvers (src/settings/store/index.js:36-83) `await apiFetch(...)` with no try/catch, so a rejected fetch propagates out of the resolver. wp.data only tracks failed resolutions (status 'error', counted by hasFinishedResolution) since the WP 6.1/6.2 cycle — but this plugin declares `Requires at least: 5.9` (speechkit.php:24), and the bundle externalises @wordpress/data to the site's wp.data global. On WP 5.9/6.0 a rejected resolver leaves the resolution permanently in the 'resolving' state and surfaces an unhandled promise rejection.
Trigger: On a WP 5.9-6.1 site, toggle Customize on for a post with no stored language/voice (needsDefault true) while the `/beyondwords/v1/projects/{id}` REST request fails at the REST layer — network drop, security plugin/WAF returning 403/5xx, or a PHP fatal. (Note: upstream BeyondWords API failures do NOT trigger this — the plugin's REST proxies return HTTP 200 with body `false`, which apiFetch resolves; only WP-REST-level failures reject.) `hasFinishedResolution` then never becomes true, so `loadingProject` stays true forever. The same mechanism leaves `isResolvingVoices` (lines 111-119) stuck true after a failed voices fetch.
Impact: The Voice section renders only the Customize toggle plus an infinite `<Spinner />` (lines 230-231) — Language/Model/Voice never appear and the customize flow is dead until a full page reload, contradicting the documented fallback ('On failure we leave the Language empty and fall back to the manual pick-a-language flow'). Plus an unhandled promise rejection in the console.
Fix: Wrap each resolver body in try/catch and dispatch the empty default on failure (e.g. `try { ... } catch { return set( 'project', {} ); }` in src/settings/store/index.js). That settles the resolution as 'finished' on every supported WP version, removes the unhandled rejection, and makes the manual-pick fallback actually reachable.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.29 — `customize` is one-shot derived state: editor Undo resurrects a custom voice while the toggle shows off

- [ ] 🆕 **new** · `low` · `src/editor/components/settings-panel/voice-section.js`

```text
In the BeyondWords WordPress plugin, src/editor/components/settings-panel/voice-section.js (around line 36): `customize` is one-shot derived state: editor Undo resurrects a custom voice while the toggle shows off.

Problem: `customize` is initialised once from meta (`useState( () => !! ( languageCode || voiceId ) )`) and afterwards only changed by the toggle handler. It is never re-synced when the underlying meta changes from outside the component. Meta edits made via `setMeta`/`editEntityRecord` participate in the block editor's undo stack, so Undo/Redo can change `beyondwords_language_code`/`beyondwords_body_voice_id` without going through `toggleCustomize`.
Trigger: Post has a saved custom language + voice (toggle initialises on). User switches Customize off — `toggleCustomize` (lines 146-157) writes empty language/voice meta and sets `customize` to false, hiding the pickers. User presses Ctrl+Z: the meta edit is undone and the entity again carries the explicit language and voice, but `customize` remains false.
Impact: The panel shows 'Customize: off' with no Language/Voice fields while the post's meta once again contains the explicit custom voice; saving persists the custom voice the UI claims was reverted to project defaults. (Mirror case: after toggling on with the project-default preselect applied, Undo clears the language but the toggle stays on — benign but inconsistent.)
Fix: Derive the on-state from meta instead of duplicating it — e.g. `const customize = forcedOn || !! ( languageCode || voiceId )` where `forcedOn` is local state only used to reveal empty pickers after toggling on; or add an effect that re-syncs `customize` to true whenever `languageCode || voiceId` becomes truthy while it is false.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run lint:js` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.30 — Headless Player UI setting is ignored on AMP requests — full visible player rendered

- [ ] 🆕 **new** · `low` · `src/player/renderer/class-amp.php`

```text
In the BeyondWords WordPress plugin, src/player/renderer/class-amp.php (around line 45): Headless Player UI setting is ignored on AMP requests — full visible player rendered.

Problem: Player::is_enabled() (class-player.php:167-173) treats PLAYER_UI_HEADLESS as enabled, with the stated rationale (comment at class-player.php:152-153) that 'we still emit the SDK script so the publisher's own UI can drive it'. That rationale is implemented only in the JS path: ConfigBuilder::merge_post_settings() sets `showUserInterface = false` for headless (class-config-builder.php:64-66). Amp::render() has no Player UI gate at all and always emits the standard visible <amp-iframe> player. So on AMP requests, a site configured as Headless (publisher builds their own UI; the standard player must not be shown) gets the full stock player UI rendered — the opposite of the setting. (AMP pages cannot run the publisher's custom SDK-driven UI either, so if anything the AMP renderer should emit nothing in headless mode.)
Trigger: Site option beyondwords_player_ui = 'headless'; AMP plugin active; front-end AMP view of an eligible post: Player::is_enabled() passes (headless counts as enabled) → renderer loop picks Amp (listed first) → Amp::render() unconditionally outputs the visible <amp-iframe> player.
Impact: Publishers who deliberately hid the stock player (headless mode, custom UI) show the standard BeyondWords player on all their AMP pages — a visible product/branding defect contradicting the configured setting.
Fix: Gate the AMP renderer on the Player UI setting, mirroring the JS renderer's semantics, e.g. at the top of Amp::render() (or Amp::check()): `if ( \BeyondWords\Settings\Fields::PLAYER_UI_ENABLED !== get_option( \BeyondWords\Settings\Fields::OPTION_PLAYER_UI, \BeyondWords\Settings\Fields::PLAYER_UI_ENABLED ) ) { return ''; }` so headless (and disabled) never emit the visible AMP player.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.31 — register_meta() 'prepare_callback' is passed at the top level, where WordPress silently discards it

- [ ] 🆕 **new** · `low` · `src/post/class-sync.php`

```text
In the BeyondWords WordPress plugin, src/post/class-sync.php (around line 324): register_meta() 'prepare_callback' is passed at the top level, where WordPress silently discards it.

Problem: `register_meta()` whitelists args via `array_intersect_key()` against its defaults (object_subtype, type, description, default, single, sanitize_callback, auth_callback, show_in_rest, revisions_enabled). `prepare_callback` is not among them, so the `'prepare_callback' => 'sanitize_text_field'` entry is dropped and never used. A REST prepare_callback is only honoured when nested inside the `show_in_rest` array (`'show_in_rest' => [ 'prepare_callback' => ... ]`).
Trigger: Any REST read of registered BeyondWords meta: the intended sanitize_text_field pass on output never runs; WordPress falls back to its default schema-based prepare_value().
Impact: Dead configuration — the intended output sanitization silently does not apply (raw stored strings are returned in REST responses, subject only to schema type validation).
Fix: Either remove the ineffective key, or move it to `'show_in_rest' => [ 'prepare_callback' => 'sanitize_text_field' ]` if output preparation is genuinely wanted.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.32 — Bulk generate counts API error responses as successes - 'Audio was requested for N posts' shown when the API rejected them

- [ ] 🆕 **new** · `low` · `src/posts-list/class-bulk-edit.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-bulk-edit.php (around line 279): Bulk generate counts API error responses as successes - 'Audio was requested for N posts' shown when the API rejected them.

Problem: handle_bulk_generate_action() increments $generated whenever Sync::generate_audio_for_post() returns truthy (lines 278-282). For the REST integration that return value is json_decode( wp_remote_retrieve_body( $response ), true ) regardless of HTTP status: Client::call_api() returns the response array even for 4xx/5xx (it only records the error in post meta), and Client::create_audio()/update_audio() decode the body unconditionally (src/api/class-client.php:152, 177). BeyondWords error responses carry JSON bodies in the errors[]/message shapes handled by error_message_from_response(), which decode to a non-empty array - truthy - so a rejected post is counted as generated. Only transport-level WP_Error (empty body, json_decode null) or an empty/non-JSON body lands in the ++$failed branch. The unit test (handle_bulk_generate_action_with_no_api_credentials) only covers the create_audio()-returns-false path, not HTTP error responses.
Trigger: Bulk 'Generate audio' on posts the API rejects, e.g. a 422 validation response or 5xx with JSON body {"message":"..."}. json_decode yields a non-empty array, $generated is incremented, and the redirect carries beyondwords_bulk_generated=N with beyondwords_bulk_failed=0.
Impact: The admin notice reports success ('Audio was requested for N posts.') when some or all posts actually failed; the failed-posts notice - whose whole purpose is to say 'check for errors in the BeyondWords column below' - never appears, so failures go unnoticed unless the user inspects the column manually.
Fix: Base the success test on the response outcome rather than body truthiness: after generate_audio_for_post(), treat the post as failed when \BeyondWords\Post\Meta::get_error_message( $post_id ) is non-empty (call_api() writes it for is_wp_error() or status > 299), or change create_audio()/update_audio() to return false/WP_Error for non-2xx statuses.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.33 — Expired or cross-user result nonce hard-kills the whole admin page via wp_nonce_ays() in admin_notices

- [ ] 🆕 **new** · `low` · `src/posts-list/class-notices.php`

```text
In the BeyondWords WordPress plugin, src/posts-list/class-notices.php (around line 152): Expired or cross-user result nonce hard-kills the whole admin page via wp_nonce_ays() in admin_notices.

Problem: verify_result_nonce() runs from four admin_notices callbacks on every admin page load. When the URL contains beyondwords_bulk_edit_result_nonce but wp_verify_nonce() fails, it calls wp_nonce_ays('') which wp_die()s the entire request. Nonce failure is not just tampering: WP nonces expire after 12-24 hours and are per-user/per-session, and the bulk-action redirect leaves beyondwords_bulk_edit_result_nonce (plus the count params) sitting in the address bar - list-table pagination and sorting links propagate existing query args, so the stale param follows the user around and gets bookmarked. These are display-only notices guarding an integer count and a plain-text (sanitize_text_field + esc_html) message, so the proportionate failure mode is to skip rendering, exactly as the function already does when the param is absent.
Trigger: Run any BeyondWords bulk action (redirect lands on edit.php?...&beyondwords_bulk_edit_result_nonce=xyz). Leave the tab open past the nonce lifetime (or log out/in, which rotates the session token, or send the URL to a colleague), then reload. wp_verify_nonce() returns false and every admin page with that param dies with 'The link you followed has expired.' - the posts list is unreachable until the user hand-edits the URL.
Impact: Editors get locked out of the posts screen by a legitimately aged URL; the wp_die page offers no recovery link. Four separate admin_notices callbacks route through the same fatal path.
Fix: Replace wp_nonce_ays('') with return false so an invalid/expired nonce simply suppresses the notices: if ( ! wp_verify_nonce( ... ) ) { return false; } The count/error params are already sanitized and escaped, so silently skipping display is safe.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.34 — Preselect config for filter-added but unregistered post types is wiped on every save, contradicting the documented merge-preserve guarantee

- [ ] 🆕 **new** · `low` · `src/settings/class-preselect.php`

```text
In the BeyondWords WordPress plugin, src/settings/class-preselect.php (around line 305-311): Preselect config for filter-added but unregistered post types is wiped on every save, contradicting the documented merge-preserve guarantee.

Problem: Utils::post_type_supports_required_features() (class-utils.php:99-101) deliberately returns true for post types that do not exist, so slugs added via the 'beyondwords_settings_post_types' filter stay in get_compatible_post_types() even while unregistered ('we treat the filter as authoritative'). But Preselect::render() skips them (get_post_type_object() returns null → continue, line 389-393), so no checkbox is ever rendered for them — and Preselect::sanitize() iterates the full compatible list assuming every entry was rendered: an entry absent from $_POST yields $submitted = [] → empty($submitted['enabled']) → unset($clean[$post_type]). The stored configuration for that post type is deleted on every Preferences save, which is exactly the scenario the sanitize() docblock promises to protect ('toggling a CPT or taxonomy plugin off and saving settings never wipes the configuration').
Trigger: A theme/site plugin registers add_filter('beyondwords_settings_post_types', fn($t) => array_merge($t, ['book'])) (the documented way to opt a type in). The 'book' CPT plugin is temporarily deactivated. An admin opens Settings → Preferences and clicks Save without touching anything → sanitize() runs, 'book' is in the compatible list but not in the submission → its stored ['mode' => 'terms', 'terms' => [...]] entry is unset and the option is persisted without it.
Impact: Silent loss of the term-gating/preselect configuration for the filter-added post type; when the CPT plugin is reactivated the publisher's preselect setup is gone and audio preselection for that type silently turns off (or must be reconfigured term by term).
Fix: Make sanitize() skip post types that were not actually renderable this request — e.g. `if ( ! get_post_type_object( $post_type ) ) { continue; }` at the top of the loop (mirroring render()), so unregistered filter-added types fall under the merge-preserve path like any other non-rendered type.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

### P3.35 — Review-notice dismiss endpoint returns a spurious HTTP 500 when update_option() no-ops

- [ ] 🆕 **new** · `low` · `src/settings/class-settings.php`

```text
In the BeyondWords WordPress plugin, src/settings/class-settings.php (around line 384-391): Review-notice dismiss endpoint returns a spurious HTTP 500 when update_option() no-ops.

Problem: rest_dismiss_review_notice() interprets update_option()'s boolean return as success/failure and maps false to a 500 response. update_option() also returns false when the new value equals the stored value — not only on failure. The value written is gmdate(\DateTime::ATOM), which has one-second granularity, so two dismiss requests within the same second write identical strings and the second update_option() returns false while the option is in fact set.
Trigger: User double-clicks the notice dismiss control (or the editor script fires the POST twice, e.g. duplicated event handler / React strict-mode dev double-invoke) → both requests hit /beyondwords/v1/settings/notices/review/dismiss within one second → second call returns {"success":false} with status 500.
Impact: Client-side code sees a 500 from an operation that actually succeeded — error toasts/log noise for a healthy operation, and misleading 5xx entries in monitoring on VIP.
Fix: Treat 'already dismissed' as success: e.g. `update_option(...); $dismissed = (bool) get_option('beyondwords_notice_review_dismissed'); return new \WP_REST_Response(['success' => $dismissed], $dismissed ? 200 : 500);` or simply always return 200 after calling update_option.

Follow WordPress VIP coding standards (WordPress-VIP-Go PHPCS for PHP / @wordpress/eslint-plugin for JS): sanitise input (wp_unslash + sanitize_*), escape output (esc_*/wp_kses), verify nonce AND capabilities where relevant, and cache remote responses / avoid blocking calls (use a short timeout, prefer vip_safe_wp_remote_get) where relevant; do NOT add phpcs:ignore.
Then verify: run `npm run phpcs` and any affected PHPUnit/Jest test; do NOT run the full Cypress suite.
```

---

## ✅ Resolved on current main since the prior review

- **Prior P1** — Stored XSS in JS player renderer (class-javascript.php) — not resurfaced; appears escaped now. NB a related XSS now flagged in the classic metabox (P1 below).
- **Prior P4** — get_content() WP_Error TypeError — not resurfaced as a fatal; a milder variant (500 on error paths) remains (P3).
- **Prior P5** — content-id player-throw overwrites meta — not resurfaced (a narrower error-discrimination nit remains, P3).
- **Prior P6** — Settings-validation errors lost across redirect — not resurfaced.
- **Prior P7** — Async audio-generation cron TypeError on deleted post — not resurfaced.
- **Prior P8** — select-voice REST error object -> forEach TypeError — not resurfaced (different select-voice issues remain).
- **Prior P11** — Preview panel renders empty PanelBody — not resurfaced.
- **Prior P12** — settings-panel sections TypeError on undefined meta — not resurfaced.
- **Prior P13** — bulk-edit AJAX returns array / delete throws uncaught — not resurfaced.
- **Prior P17** — play-audio namespace hook load race — not resurfaced.
- **Prior P18** — cached_get() bare JSON scalar TypeError — not resurfaced (a different cached_get issue remains, P3).
- **Prior P21** — get_content_without_excluded_blocks() int post id — appears fixed (get_post_body now resolves via get_post()).
- **Prior P22** — Uninstall orphaned _transient_timeout_ rows — VERIFIED FIXED on current main (commit 71dfa66).
- **Prior P23** — Dead add-player Assets $hook branch — VERIFIED FIXED (class-assets.php removed from the tree).
- **Prior P25** — help-panel &amp; UTM breakage — not resurfaced.
- **Prior P27** — Unguarded include of build/index.asset.php — not resurfaced.

_Also verified fixed this run:_
- Transient cleanup deletes value rows but orphans _transient_timeout_beyondwords_* rows permanently — Fixed on current main (commit 71dfa66) — cleanup now sweeps both _transient_ and _transient_timeout_ rows.

## ⚠️ Superseded / re-verify (code changed after the review)

- **sync() clobbers a previously-saved explicit Generate audio choice on any watched-taxonomy checkbox change** (`src/editor/components/generate-audio/classic-metabox.js`) — generate-audio/classic-metabox.js was rewritten after the review (commit 6524965, "state-reflecting generation labels"); the sync() structure the finding described no longer matches. Re-verify against current code before actioning.

