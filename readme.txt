=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 7.0.0-beta.1
Requires at least: 5.9
Requires PHP: 8.0
Tested up to: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses.

== Description ==

[BeyondWords](https://beyondwords.io/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) is the AI audio and video platform built for publishers. Connect the plugin to automatically generate audio and video versions of your WordPress posts, which can be instantly embedded into your pages or distributed across third-party platforms.

Choose from a variety of ElevenLabs and Azure voices to power your narration, or create your own hyper-realistic [voice clones](https://beyondwords.io/voice-cloning/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin).


== GET STARTED WITH BEYONDWORDS ==

To get started with BeyondWords for WordPress, [book a demo](https://beyondwords.io/book-a-demo/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) with our team.

We’ll walk you through the platform, discuss your goals, and help set up your account so you can begin converting your WordPress content into audio and/or video.

== Key features of our text-to-speech plugin for WordPress: ==

* Easily pick which pages and posts are converted into audio and/or video
* Automatic content extraction ensures only editorial content is converted
* Powerful and versatile player options to fit your web and mobile experience


== Key features of your BeyondWords dashboard: ==

* Choose from hundreds of premade AI voices or create lifelike voice clones
* Configure audio, video, and player settings to suit your publication’s needs
* Curate your own playlists and podcast feeds
* Monitor engagement through BeyondWords Analytics or third-party platforms
* Monetize your content with audio and video advertising or subscriptions

Join hundreds of WordPress publishers already using our text-to-speech plugin and platform to increase engagement, boost subscriptions, and generate revenue.

If you have any questions or feedback, please email <support@beyondwords.io>.


== CUSTOMER TESTIMONIAL ==

> "We've been using BeyondWords to convert our articles into audio for over a year. Overall, we are very impressed with the service. The quality of the audio is consistently the best we've found available, the plugin and dashboard provides all the functionality we need, processing and delivery of the audio is fast and the players fit nicely on our page. We've had great customer feedback and the team have been quick to make adjustments based on our suggestions."
>
> &mdash; Kenneth Creamer, Creamer Media


== HYPER-REALISTIC VOICES ==

BeyondWords offers a curated selection of AI voices from ElevenLabs and Azure, making it easy for you to find the right sound for your newsroom. You can also create Instant voice clones from just a few seconds of audio, or invest in a top-quality Professional clone for consistent, on-brand narration.

Our AI preprocessing system automatically handles numbers, abbreviations, scores, and other non-standard text, converting them into the optimal spoken form for audio or video. For those rare exceptions, you can define custom pronunciations directly in your dashboard.

The result is natural-sounding narration that engages your audience—at a fraction of the cost of traditional voice recording.


== EFFORTLESS DISTRIBUTION ==

With the BeyondWords for WordPress plugin, you can automatically embed audio and video versions into your posts using our customizable player. Enable playback-by-paragraph to let readers click anywhere to start listening, and update the color scheme to match your publication’s branding.

In your BeyondWords dashboard, you can create custom playlists that keep audiences listening for longer. Embed these on your website or turn them into podcast feeds, ready for submission to platforms like Spotify, Apple Podcasts, and YouTube.

You can also download your audio/video files and share them via URL.


== MONETIZATION AND ANALYTICS ==

Monetize your BeyondWords audio and video by uploading your own advertising assets, or connect to an ad network through VAST integration. Our plugin is also compatible with subscriptions, allowing you to hide the player from non-logged-in users when needed.

Keep on top of performance with BeyondWords Analytics or by forwarding events to your own analytics tools. You can track plays, engagement rate, unique listeners, listening time, retention, and other key audio and video metrics.

== Changelog ==

= 7.0.0 =

Release date: tbc

**Enhancements**

* [#520](https://github.com/beyondwords-io/wordpress-plugin/pull/520) New settings page and project-wide rewrite.
    * Simplifies the plugin settings — removing many tabs, fields and options — so that audio/video generation and player display are managed from your BeyondWords project settings.
    * Standardises the codebase to WordPress VIP coding standards and migrates CI tooling from Yarn to npm.
* [#527](https://github.com/beyondwords-io/wordpress-plugin/pull/527) BeyondWords editor redesign for the block and classic editors.
    * New Content (Source, Script template), Format (Output, Video template, Video size), Voice (Language, Model, Voice) and Player (Embed) settings, available in both editors.
    * The Player "Embed" setting replaces the "Display player" checkbox — choose "None" to hide the player on a post.
* [#536](https://github.com/beyondwords-io/wordpress-plugin/pull/536) Wire up the Player "Embed" setting.
    * The Embed choice (audio/video × post/script) now controls the rendered player, rather than only showing or hiding it.
* [#537](https://github.com/beyondwords-io/wordpress-plugin/pull/537) Opt-in "Customize" voice and language controls.
    * A per-post "Customize" toggle (off by default) reveals the Language and Voice pickers; the project's default language is pre-selected when enabled.
    * The Voice and Language settings are consolidated into the plugin sidebar.
* [#558](https://github.com/beyondwords-io/wordpress-plugin/pull/558) Reorder the voice picker to Language → Model → Voice.
    * "Model" is now a language-level filter that narrows the Voice list.
* [#557](https://github.com/beyondwords-io/wordpress-plugin/pull/557) Preselect "Generate audio" by taxonomy term.
    * Reinstates preselecting audio generation for posts assigned specific terms, now across all hierarchical taxonomies and without marking posts as having unsaved changes.
* [#555](https://github.com/beyondwords-io/wordpress-plugin/pull/555) Live player preview for the "Add Player" block.
    * The block renders a non-interactive preview of the actual player, or a prompt to generate audio when none exists.
* [#563](https://github.com/beyondwords-io/wordpress-plugin/pull/563) State-reflecting generation labels and "Legacy" model rename.
    * The "Generate audio" toggle now reads "Generation enabled" / "Generation disabled"; the "Standard" voice model bucket is relabelled "Legacy".
* [#532](https://github.com/beyondwords-io/wordpress-plugin/pull/532) Cache API reads and defer audio generation on WordPress VIP.
    * Editor dropdown data is cached in 15-minute transients; on VIP, audio create/update is deferred to WP-Cron so the save request returns immediately.

**Fixes**

* [#564](https://github.com/beyondwords-io/wordpress-plugin/pull/564) Send the full `video_settings` payload so videos generate.
    * Selecting "Video" or "Audio + video" output now sends the complete video settings (seeded from the project defaults), fixing posts that produced no video.
* [#540](https://github.com/beyondwords-io/wordpress-plugin/pull/540) Escape the player `onload` attribute to prevent stored XSS via the Content ID.
* [#539](https://github.com/beyondwords-io/wordpress-plugin/pull/539) Add capability checks to the bulk-edit AJAX handler.
* [#541](https://github.com/beyondwords-io/wordpress-plugin/pull/541) Prevent a player SDK error from clobbering saved content on Fetch.
* [#546](https://github.com/beyondwords-io/wordpress-plugin/pull/546) Prevent the classic editor Voice/Model dropdowns breaking on a voices REST error.
* [#545](https://github.com/beyondwords-io/wordpress-plugin/pull/545) Persist settings-validation errors across the save redirect.
* [#554](https://github.com/beyondwords-io/wordpress-plugin/pull/554) Return JSON from the bulk-edit AJAX handler and catch delete errors.
* [#553](https://github.com/beyondwords-io/wordpress-plugin/pull/553) Resolve the post ID in `Content::get_content_without_excluded_blocks`.
* [#552](https://github.com/beyondwords-io/wordpress-plugin/pull/552) Re-check the BeyondWords namespace in an effect to avoid a player init race.
* [#551](https://github.com/beyondwords-io/wordpress-plugin/pull/551) Track live meta for the Inspect panel Remove button.
* [#550](https://github.com/beyondwords-io/wordpress-plugin/pull/550) Coerce non-string API error messages to avoid a fatal `TypeError`.
* [#548](https://github.com/beyondwords-io/wordpress-plugin/pull/548) Guard against undefined post meta in the settings-panel sections.
* [#544](https://github.com/beyondwords-io/wordpress-plugin/pull/544) Prevent a `TypeError` in the deferred audio-generation cron when a post is deleted.
* [#543](https://github.com/beyondwords-io/wordpress-plugin/pull/543) Guard a null languages API result in the classic editor voice select.
* [#542](https://github.com/beyondwords-io/wordpress-plugin/pull/542) Surface a `WP_Error` from `get_content()` instead of a fatal `TypeError`.

**Deprecations**

* Removed the `beyondwords_player_style`, `beyondwords_player_content`, `beyondwords_title_voice_id`, `beyondwords_summary_voice_id` and `beyondwords_disabled` post meta keys.
    * Existing values are preserved in the database and only removed on full uninstall; `beyondwords_disabled` is migrated to the new "Embed" setting on upgrade.
* The `beyondwords-*` `<head>` meta tags are now only emitted for the client-side (Magic Embed) integration.

**Compatibility**

* Tested up to WordPress 7.0.
* [#515](https://github.com/beyondwords-io/wordpress-plugin/pull/515) PHP 8.5 support.
    * Run unit and e2e tests against PHP 8.0 and PHP 8.5 in GitHub Actions.
    * Bumped `phpVersion` in wp-env to 8.5.

**Codebase Enhancements**

* [#533](https://github.com/beyondwords-io/wordpress-plugin/pull/533) Fix failing Cypress tests for v7.
* [#538](https://github.com/beyondwords-io/wordpress-plugin/pull/538) Remove the unused `updatePostMeta` util from the Inspect panel.
* [#562](https://github.com/beyondwords-io/wordpress-plugin/pull/562) Re-enable Plugin Check on `plugin-check-action` v1.1.7.
* [#560](https://github.com/beyondwords-io/wordpress-plugin/pull/560) Cancel superseded CI runs via a concurrency group.
* [#516](https://github.com/beyondwords-io/wordpress-plugin/pull/516) Remove `environment` from the GitHub workflows.
* [#517](https://github.com/beyondwords-io/wordpress-plugin/pull/517), [#535](https://github.com/beyondwords-io/wordpress-plugin/pull/535) Update GitHub Actions dependencies.
* [#514](https://github.com/beyondwords-io/wordpress-plugin/pull/514), [#529](https://github.com/beyondwords-io/wordpress-plugin/pull/529), [#534](https://github.com/beyondwords-io/wordpress-plugin/pull/534) Dependency upgrades.
* Various dependency updates to clear Dependabot security warnings.

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
