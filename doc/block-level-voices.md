# Per-block language and voice

An editor can give any block its own language and voice, overriding the
post-level choice for that block alone. This documents where the values live,
when they are rendered, and how each kind of block behaves.

## The data attributes

BeyondWords reads [segment-scoped data attributes](https://docs.beyondwords.io/docs-and-guides/integrations/data-attributes#data-attributes)
from the HTML we submit as the content `body`:

```html
<p data-beyondwords-language="fr_FR" data-beyondwords-voice-id="784">
  Bonjour tout le monde.
</p>
```

A block with no override emits neither attribute and falls back to the
post-level voice:

```html
<p>Hello world.</p>
```

The request itself is unchanged — still `type: "auto_segment"`, still carrying
the post's `body_voice_id` as the fallback for everything without an override.

There is no model attribute. A voice name can map to several voice ids, one per
model (Patrick on `eleven_v3` and Patrick on `eleven_multilingual_v2` are
different ids), so `data-beyondwords-voice-id` identifies the voice *and* its
model on its own.

## Where the values live

Two block attributes, registered on every supported block in both JS
(`blocks.registerBlockType`) and PHP (`register_block_type_args`):

| Attribute | Example | Empty means |
| --- | --- | --- |
| `beyondwordsLanguageCode` | `en_GB` | inherit the post/project language |
| `beyondwordsVoiceId` | `9010` | inherit the post/project voice |

Both default to an empty string. Gutenberg omits attributes that equal their
default when it serializes a block, so a post whose blocks carry no override is
stored byte-for-byte as it was before this feature existed.

The values ride in the block's comment delimiter, never in its saved HTML:

```html
<!-- wp:paragraph {"beyondwordsLanguageCode":"fr_FR","beyondwordsVoiceId":"784"} -->
<p>Bonjour tout le monde.</p>
<!-- /wp:paragraph -->
```

Writing them into the saved HTML instead (via `blocks.getSaveContent.extraProps`)
would change published markup and invalidate every existing block the moment the
filter went away, so we deliberately don't.

## When the attributes are rendered

`BlockAttributes::add_segment_attributes()` is a `render_block` filter, but it is
**not** registered in `BlockAttributes::init()`. `Content::get_content_without_excluded_blocks()`
adds it immediately before the loop that renders the API body and removes it
straight after.

That keeps the front end byte-identical: `the_content` never sees the filter, so
a published page renders exactly the markup it always did, and deactivating the
plugin leaves nothing behind.

The renderer uses `WP_HTML_Tag_Processor`, setting both attributes on the first
tag of the rendered block and leaving the rest of the HTML untouched.

## Block coverage

Every block is supported by default, including third-party blocks such as
`myplugin/case-study`. `isBeyondwordsSupportedBlock()` holds the exclusions, and
the pre-existing list is unchanged.

| Kind of block | Behaviour |
| --- | --- |
| Leaf/static (`core/paragraph`, `core/heading`, `core/image`) | The override lands on the block's own tag. |
| Nested/container (`core/group`, `core/columns`, `core/quote`) | The override lands on the container's wrapper tag. BeyondWords cascades it to the descendants, which may override it again — the nearest ancestor wins. |
| Dynamic (a `render_callback`) | Supported: the attributes live in the comment delimiter, and the filter runs on whatever the callback returned. |
| Reusable (`core/block`) and template parts | Excluded, as before. Overrides set inside the synced content still apply. |
| Shortcode and classic (`core/shortcode`, `core/freeform`) | `core/freeform` is excluded, as before. A block that renders no HTML tag is left alone — there is nothing to carry the attributes. |
| Multi-root output | Only the first tag is given the attributes. |
| Excluded from audio (`beyondwordsAudio: false`) | Unchanged: top-level blocks are dropped from the body before rendering, so their overrides never matter. |

## In the editor

The block inspector's BeyondWords panel carries the same
Language → Accent → Native → Model → Voice pickers as the plugin sidebar — one
shared `VoicePicker` component — behind a per-block "Customize" toggle that is
off by default. Nothing is fetched from the API until it is switched on.

Two deliberate differences from the post-level pickers:

- **No project default is seeded.** Switching Customize on leaves the block
  inheriting until a language is actually picked; the post-level picker instead
  pre-selects the project's language, because a post must always resolve to a
  concrete voice.
- **Choosing a language seeds that language's default body voice**, so an
  override always names a voice that can speak the language it is paired with.
  This is what keeps the pair valid without a separate validation step.

Switching Customize off clears both attributes, returning the block to the
post-level fallback.
