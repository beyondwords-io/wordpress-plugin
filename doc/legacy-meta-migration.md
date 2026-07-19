# Legacy meta migration

Record of the legacy post-meta cleanup carried out for the v7 Block/Classic
Editor redesign (ticket S-8551). **This work has shipped** — the notes below
document what changed and the downgrade-safety guarantees, not outstanding work.

## What changed

The new schema (new meta keys + new REST endpoints) landed additively first so
the old UI kept working while the new UI was built. Once the new components
replaced the old ones, the legacy keys moved `current` → `deprecated` in
[src/core/class-utils.php](../src/core/class-utils.php)
(`Utils::get_post_meta_keys()`).

That move does not unregister the keys. `Sync::register_meta()` in
[src/post/class-sync.php](../src/post/class-sync.php) registers every key from
`get_post_meta_keys( 'all' )`, so writes to the deprecated keys are still
sanitised and `Sync::is_protected_meta()` still keeps them out of the legacy
Custom Fields panel. What the move changes is `show_in_rest`, which is set from
the `current` list plus `Sync::REST_LEGACY_META_KEYS` — a short allow-list of
deprecated keys (`beyondwords_podcast_id`, `speechkit_generate_audio`,
`speechkit_project_id`, `speechkit_podcast_id`, `speechkit_error_message`,
`_speechkit_link`) that the editor still reads as a fallback on upgraded sites.
Deprecated keys outside that allow-list get `show_in_rest => false`.

### Keys deprecated

- `beyondwords_player_style`
- `beyondwords_player_content`
- `beyondwords_title_voice_id`
- `beyondwords_summary_voice_id`
- `beyondwords_disabled` — the per-post "Display player" opt-out. Replaced by the
  Player "Embed" setting (`beyondwords_embed = "none"`).

DB rows are **kept** on plugin deactivation and upgrade — only removed on full
uninstall — so a plugin downgrade still finds the values. The one exception is
the `beyondwords_disabled = '1'` rows, which the v7.0.0 migration deletes as it
converts them (see below).

### `beyondwords_disabled` → `beyondwords_embed`

The "Display player" checkbox was removed in favour of the Embed dropdown, where
`Embed = None` is the deliberate opt-out (equivalent to the old unchecked box).
To carry existing opt-outs forward, the v7.0.0 migration in
[src/core/class-updater.php](../src/core/class-updater.php)
(`migrate_disabled_to_embed_none()`) walks every post with
`beyondwords_disabled = '1'` in batches of 100. A post only gets
`beyondwords_embed = 'none'` if its `beyondwords_embed` is still empty — an
Embed value already set is authoritative and is left alone. Either way the
legacy flag is deleted from that post. Rows holding any other value (`''`,
`'0'`) are not matched by the query and stay in place, where both pre-v7 code
and `Meta::get_disabled()` read them as "not disabled".

The query passes `'post_type' => 'any'`, which excludes post types registered
with `exclude_from_search`. Those types can still be BeyondWords-compatible —
`Settings\Utils::get_compatible_post_types()` builds its list from an
unfiltered `get_post_types()` call — so posts of such a type keep their
`beyondwords_disabled` row through the upgrade.

`Player::is_enabled()` still honours `beyondwords_disabled` as a fallback for
any post the migration hasn't reached (e.g. after a downgrade/re-upgrade). It
delegates to `SettingsFields::is_player_disabled_for_post()`, which prefers a
non-empty `beyondwords_embed` and only falls back to `Meta::get_disabled()`.

### Components removed

- `src/editor/components/player-style/`
- `src/editor/components/player-content/`
- `src/editor/components/display-player/` (replaced by the Embed setting)

`display-player` rendered the "Display player" checkbox as a row inside
`preview-panel/`; that row was dropped and the visibility control now lives in
the Embed select in
[settings-panel/player-section.js](../src/editor/components/settings-panel/player-section.js).
`src/editor/components/play-audio/` was **not** removed — it is still the
component that renders the player, imported by
[preview-panel/index.js](../src/editor/components/preview-panel/index.js) and by
[block/document-setting/index.js](../src/editor/block/document-setting/index.js).

`src/editor/components/select-voice/` was also kept. It was refactored in place
into the model-first Language → Model → Voice selects — see
[class-select-voice.php](../src/editor/components/select-voice/class-select-voice.php)
and [classic-metabox.js](../src/editor/components/select-voice/classic-metabox.js)
— and remains the classic editor's voice UI; it was not replaced by a new
component.

## Downgrade safety

DB rows for the deprecated keys are preserved until full uninstall, so a plugin
downgrade still renders posts using the legacy values — with the exception of
the `beyondwords_disabled = '1'` rows, which `migrate_disabled_to_embed_none()`
deletes on upgrade to v7.0.0. A downgrade after that migration will not see the
old opt-out flag on those posts; the equivalent state lives in
`beyondwords_embed = 'none'`, which pre-v7 code ignores.

New posts written under v7 use the new keys only; the remaining legacy keys are
left untouched.
