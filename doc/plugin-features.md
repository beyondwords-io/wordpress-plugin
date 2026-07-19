#   Features

The settings page has three tabs: Authentication, Integration and
Preferences ([src/settings/class-tabs.php](../src/settings/class-tabs.php)).
Only Authentication is shown until the saved credentials have validated
against the API — Integration and Preferences appear after that.

| Screen    | Feature                            | Description |
| --------- | ---------------------------------- | ----------- |
| Settings  | ‘API key’ field                    | Required field to make BeyondWords API requests. Authentication tab. |
| Settings  | ‘Project ID’ field                 | Required field to make BeyondWords API requests. Authentication tab. |
| Settings  | ‘Integration method’               | ‘REST API’ (the default) or ‘Magic Embed’, for sites where a theme or plugin prevents saving content via the REST API. Integration tab. |
| Settings  | ‘Excerpt’                          | Include the post excerpt at the start of generated audio and video. Preferences tab. |
| Settings  | ‘Player UI’                        | ‘Enabled’, ‘Headless’ or ‘Disabled’. Preferences tab. |
| Settings  | Preselect ‘Generate audio’         | ‘Generate audio’ starts checked for the selected post types, either for every post or only for posts with selected taxonomy terms. See [preselect-generate-audio.md](./preselect-generate-audio.md). Preferences tab. |
| Posts     | BeyondWords column                 | A table column to display which posts have players or errors. |
| Posts     | Bulk actions                       | ‘Generate audio’ or ‘Delete audio’ for multiple posts at once, from the bulk actions dropdown or the inline bulk edit. |
| Post Edit | Generate audio toggle              | Turn audio generation on or off for the current post. The caption reflects the state: ‘Generation enabled’ or ‘Generation disabled’. |
| Post Edit | Player preview                     | Display a preview of the audio player, or the processing status if the player is not available. |
| Post Edit | ‘Embed’                            | Pick which generated asset is shown on the post — ‘None’ shows no player. Replaces the ‘Display player’ checkbox removed in 7.0.0; see [legacy-meta-migration.md](./legacy-meta-migration.md). |
| Post Edit | ‘Source’ and ‘Script template’     | Generate from the ‘Post’, a ‘Script’, or ‘Post + script’. ‘Script template’ is shown only when the source includes a script, and defaults to ‘Project default’. |
| Post Edit | ‘Output’                           | Generate ‘Audio’, ‘Video’, or ‘Audio + video’. |
| Post Edit | ‘Video template’ and ‘Video size’  | Per-post video options, shown when the output includes video. Each defaults to ‘Project default’. |
| Post Edit | ‘Voice’                            | Toggle ‘Customize’ to override the project defaults with a ‘Language’ and a ‘Voice’. ‘Accent’, ‘Native’ and ‘Model’ narrow the voice list; they are filters only and are not saved — the post stores just the language code and voice id. |
| Post Edit | Per-block generation toggle        | Exclude an individual block from the generated audio, via the block toolbar button or the block inspector panel. |
| Post Edit | BeyondWords sidebar / metabox      | All plugin functionality and support in one place: a sidebar in the Block Editor, and an equivalent metabox in the Classic Editor. |
| Post Edit | Prepublish panel                   | Confirm audio generation immediately before publishing. |
| Post Edit | ‘Insert BeyondWords player’ button | Customize the audio player location while using the Classic Editor. |
| Post Edit | ’BeyondWords’ block                | Customize the audio player location while using the Block Editor. |
| Post Edit | Inspect panel                      | View and copy the custom data our plugin adds to each post. |
| -         | JavaScript SDK (Player)            | Automatically embed audio versions using a custom audio player. |
| -         | Settings notice                    | Display a notice if the BeyondWords Project ID or API Key is missing. |
| -         | Site Health                        | A BeyondWords section in Site Health with the current settings, plugin version, registered (and deprecated) filters, and a REST API connectivity check. |
| -         | AMP plugin compatibility           | Compatibility with the [official AMP plugin](https://en-gb.wordpress.org/plugins/amp/). |
| -         | WPGraphQL plugin compatibility     | Compatibility with the [GraphQL plugin](https://www.wpgraphql.com/). |
