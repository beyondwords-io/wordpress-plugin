# Preselect "Generate audio"

How the **Preselect ‘Generate audio’** setting decides whether the Generate
Audio toggle starts checked, and how that choice is honoured at save time
without dirtying the post. Implemented in
[src/settings/class-preselect.php](../src/settings/class-preselect.php),
[src/editor/components/generate-audio/](../src/editor/components/generate-audio/)
and [src/post/class-sync.php](../src/post/class-sync.php).

## Stored format (`beyondwords_preselect` option)

One entry per compatible post type, each with a `mode`:

```php
[
    'post' => [ 'mode' => 'all' ],                 // preselect for every post
    'page' => [
        'mode'  => 'terms',                        // gate by taxonomy terms
        'terms' => [
            'category' => [ 12, 34 ],
            'genre'    => [ 56 ],
        ],
    ],
    // a missing post type means never preselect (`Preselect::MODE_OFF`)
]
```

`all` and `terms` are the only valid values of the `mode` key; any other `mode`
resolves to `off`. `Preselect::get_mode()` is also tolerant of the pre-7.0.0
shapes described below, so a legacy `'1'` still reads as `all` and a bare
taxonomy array as `terms` before the migration has run. Nothing writes an
explicit `mode: off` — `sanitize()` removes the post type's entry instead.

### Default value

`Preselect::DEFAULT_VALUE` is `[ 'post' => [ 'mode' => 'all' ] ]`, and
`Preselect::get()` passes it as the `get_option()` fallback. So the setting is
not pure opt-in: until the option has been saved at least once, posts
preselect out of the box (other post types do not).

`sanitize()` merges into the *raw* stored option rather than into `get()`, so
that default is not silently written into the first save. It also reads only
the post types and hierarchical taxonomies rendered in that request, so saving
the settings page never wipes config belonging to a post type or taxonomy that
is currently unregistered.

### Legacy (pre-7.0.0) shapes

`Updater::run()` migrates the old shapes on upgrade:

- `'1'` for a post type meant "preselect the whole post type" → `mode: all`.
- A bare `[ taxonomy => [ term_ids ] ]` array meant term-gating → `mode: terms`.

## Editor behaviour: derive, don't write

The block editor does **not** write `beyondwords_generate_audio` meta to show a
preselected toggle — it *derives* the checked state from the Preselect setting.
An untouched post therefore stays clean (no dirty state, no meta write) until
the user actually changes something.

The classic editor's checkbox writes an explicit `'1'`/`'0'` on save. The
server renders the correct initial state; its metabox JS
([classic-metabox.js](../src/editor/components/generate-audio/classic-metabox.js))
then does two things:

1. Keeps the caption beside the checkbox in step with the checkbox state,
   swapping between its `data-label-enabled` and `data-label-disabled` text.
2. In `terms` mode only, re-checks the checkbox as the watched taxonomy terms
   are ticked and unticked (`post_category[]` for categories, `tax_input[…][]`
   for other taxonomies). The `change` listener is delegated, so terms added
   through the "+ Add New Category" UI are covered too.

Once the user toggles the checkbox themselves, the term sync stops (the
caption still follows), so a deliberate choice is never clobbered.

## Save-time decision

Because a preselected-but-untouched post has no meta, the generate decision
must come from the setting at save time:

1. An explicit `beyondwords_generate_audio` value always wins
   (`'1'` = generate, `'0'` = don't).
2. When unset, `Sync::should_generate_audio_for_post()` falls back to
   `Preselect::should_preselect_for_post()` — but **only for editor/REST
   saves** (`REST_REQUEST`). Programmatic and imported posts
   (`wp_insert_post()`, WXR import, cron) keep the explicit-meta requirement so
   a bulk import never unexpectedly generates audio.

`Preselect::get()` applies an explicit default-value fallback rather than
relying on `register_setting` defaults (those only apply where the setting is
registered), keeping REST/cron decisions in step with what the editor displays.
