# Legacy meta migration

Deferred cleanup tracked here so it doesn't get lost across the multi-PR
Block Editor redesign initiative (ticket S-8551).

## Why deferred

The new schema lands additively first (new meta keys + new REST endpoints)
so the old UI keeps working while the new UI is built. Moving the legacy
keys into the `deprecated` bucket *now* would unregister them from REST,
which would break the existing `player-style`, `player-content`,
`select-voice`, `display-player`, and `inspect-panel` components before
their replacements ship.

## When to run

Same PR that removes the legacy React components. In the proposed PR
sequence that's the final cleanup PR — after:

1. Schema (additive) — _done in this initiative._
2. Settings store extensions.
3. Shared field components.
4. Plugin sidebar (toolbar surface) using new components.
5. Document setting panel (subset of new components).
6. Generate-toggle relabel (Create / Update).
7. Wire new meta into the API payload.

Cleanup PR: this migration **plus** deleting the old React/PHP
components in one go.

## Keys being deprecated

Move from `current` → `deprecated` in
[src/core/class-utils.php](../src/core/class-utils.php)
(`Utils::get_post_meta_keys()`):

- `beyondwords_player_style`
- `beyondwords_player_content`
- `beyondwords_title_voice_id`
- `beyondwords_summary_voice_id`

DB rows are **kept** on plugin deactivation and upgrade — only removed
on full uninstall — so a plugin downgrade still finds the values.

## Tasks for the cleanup PR

### Source

| File | Action |
|---|---|
| [src/core/class-utils.php](../src/core/class-utils.php) | Move the four keys from `current` → `deprecated`. |
| [src/post/class-sync.php](../src/post/class-sync.php) | `register_meta()` already iterates `get_post_meta_keys('current')`, so the keys drop off REST automatically. No code change needed — but verify in test. |
| [src/core/class-uninstaller.php](../src/core/class-uninstaller.php) | Already iterates `get_post_meta_keys('all')`, so deprecated keys are still purged on uninstall. No code change needed — but verify. |
| [src/post/class-meta.php](../src/post/class-meta.php) | Remove getters for the four keys if unused after component removal. |
| [src/post/class-content.php](../src/post/class-content.php) | Stop reading `title_voice_id` / `summary_voice_id` from the API payload. |
| [src/post/class-head.php](../src/post/class-head.php) | Stop reading `player_style` for the player config. |
| [src/player/class-config-builder.php](../src/player/class-config-builder.php) | Stop emitting `player_style`. |
| [src/editor/components/inspect-panel/index.js](../src/editor/components/inspect-panel/index.js) | Drop the four keys from the inspect debug output. |

### Components to delete in the same PR

- `src/editor/components/player-style/` (whole folder)
- `src/editor/components/player-content/` (whole folder)
- `src/editor/components/select-voice/` (whole folder — replaced by new `voice` field)
- `src/editor/components/play-audio/` (whole folder — replaced by `preview-panel`; lift the hook first)
- `src/editor/components/display-player/` (whole folder — replaced by inline toggle in `preview-panel`)

### Site Health

[src/site-health/class-site-health.php](../src/site-health/class-site-health.php)
does **not** currently surface any of the four keys, so no change needed.
Audit at cleanup time to confirm.

### Tests

PHPUnit:

- [tests/phpunit/core/test-utils.php](../tests/phpunit/core/test-utils.php) — move the four keys in the `get_post_meta_keys` / `get_post_meta_keys_all` expected lists; add to `get_post_meta_keys_deprecated`.
- [tests/phpunit/post/test-head.php](../tests/phpunit/post/test-head.php) — drop `player_style` assertions.
- [tests/phpunit/post/test-content.php](../tests/phpunit/post/test-content.php) — drop `title_voice_id` / `summary_voice_id` assertions.
- [tests/phpunit/post/test-meta.php](../tests/phpunit/post/test-meta.php) — drop getter coverage for removed methods.
- [tests/phpunit/post/test-sync.php](../tests/phpunit/post/test-sync.php) — verify deprecated keys are not registered for REST.
- [tests/phpunit/player/test-config-builder.php](../tests/phpunit/player/test-config-builder.php) — drop `player_style` assertions.
- [tests/phpunit/editor/components/player-style/test-player-style.php](../tests/phpunit/editor/components/player-style/test-player-style.php) — delete (whole file).
- [tests/phpunit/editor/components/player-content/test-player-content.php](../tests/phpunit/editor/components/player-content/test-player-content.php) — delete (whole file).
- [tests/phpunit/editor/components/inspect-panel/test-inspect-panel.php](../tests/phpunit/editor/components/inspect-panel/test-inspect-panel.php) — drop the four keys from expected debug output.

Cypress e2e:

- [tests/cypress/e2e/classic-editor/player-style.cy.js](../tests/cypress/e2e/classic-editor/player-style.cy.js) — delete.
- [tests/cypress/e2e/classic-editor/player-content.cy.js](../tests/cypress/e2e/classic-editor/player-content.cy.js) — delete.
- [tests/cypress/e2e/classic-editor/content-id.cy.js](../tests/cypress/e2e/classic-editor/content-id.cy.js) — audit; drop deprecated-key checks if any.
- [tests/cypress/e2e/block-editor/content-id.cy.js](../tests/cypress/e2e/block-editor/content-id.cy.js) — audit; drop deprecated-key checks if any.

## Downgrade safety check

Before merging the cleanup PR, manually verify on a staging site:

1. Install plugin at the new version.
2. Generate content (writes only new keys; old keys untouched).
3. Downgrade plugin to the previous version.
4. Confirm posts still render with the legacy keys preserved.
