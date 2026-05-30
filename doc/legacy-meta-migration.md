# Legacy meta migration

Record of the legacy post-meta cleanup carried out for the v7 Block/Classic
Editor redesign (ticket S-8551). **This work has shipped** — the notes below
document what changed and the downgrade-safety guarantees, not outstanding work.

## What changed

The new schema (new meta keys + new REST endpoints) landed additively first so
the old UI kept working while the new UI was built. Once the new components
replaced the old ones, the legacy keys moved `current` → `deprecated` in
[src/core/class-utils.php](../src/core/class-utils.php)
(`Utils::get_post_meta_keys()`), which unregisters them from REST.

### Keys deprecated

- `beyondwords_player_style`
- `beyondwords_player_content`
- `beyondwords_title_voice_id`
- `beyondwords_summary_voice_id`
- `beyondwords_disabled` — the per-post "Display player" opt-out. Replaced by the
  Player "Embed" setting (`beyondwords_embed = "none"`).

DB rows are **kept** on plugin deactivation and upgrade — only removed on full
uninstall — so a plugin downgrade still finds the values.

### `beyondwords_disabled` → `beyondwords_embed`

The "Display player" checkbox was removed in favour of the Embed dropdown, where
`Embed = None` is the deliberate opt-out (equivalent to the old unchecked box).
To carry existing opt-outs forward, the v7.0.0 migration in
[src/core/class-updater.php](../src/core/class-updater.php)
(`migrate_disabled_to_embed_none()`) converts every post with
`beyondwords_disabled = '1'` to `beyondwords_embed = 'none'` and drops the legacy
flag. `Player::is_enabled()` still honours `beyondwords_disabled` as a fallback
for any post the migration hasn't reached (e.g. after a downgrade/re-upgrade).

### Components removed

- `src/editor/components/player-style/`
- `src/editor/components/player-content/`
- `src/editor/components/display-player/` (replaced by the Embed setting)

`play-audio/` and `display-player`'s preview role were folded into
`preview-panel/`; the player `<select>`-driven voice/model UI replaced the old
`select-voice` dropdown.

## Downgrade safety

DB rows for the deprecated keys are preserved until full uninstall, so a plugin
downgrade still renders posts using the legacy values. New posts written under
v7 use the new keys only; the legacy keys are left untouched.
