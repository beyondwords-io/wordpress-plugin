# Video settings payload

Why `Content::get_video_settings_params()` sends a full `video_settings` object
instead of just a template ID.

## Backend requirements

Choosing "Video" (or "Audio + video") output opts a post into video generation,
overriding the project's default `video_settings.enabled`. However, the
BeyondWords backend **silently skips video generation** unless the payload
carries all of:

- `enabled: true`
- a non-empty `variants` array
- a non-empty `sizes` array whose entries include `width`/`height`

A partial payload carrying only `template.id` and `sizes[].name`/`enabled`
leaves `variants` empty server-side, so no video is produced.

## Approach: mirror the dashboard

The BeyondWords dashboard sends the full object whenever a user customises
video, so the plugin does the same:

1. Seed the payload from the project's default video settings
   (`Client::get_video_settings()`, fetched once and cached). The defaults are
   fetched for the project the post publishes to — which may be a per-post
   override of the global project — so variants and size dimensions match the
   project the content POST actually targets.
2. Layer the post's own choices on top:
   - when the post sets a Video size, that size becomes the only enabled
     size; when the post leaves Video size at "Project default", every size
     keeps the project's own `enabled` flag;
   - the chosen video template overrides the project default (omitted when the
     post has none, deferring to the project default).
3. Anything the post doesn't customise (`variants`, and the size dimensions) is
   echoed straight from the project defaults. The plugin exposes no per-post
   variant control, so the project default is the correct value to send.
