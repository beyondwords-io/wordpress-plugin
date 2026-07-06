=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 7.0.0-dev-3.0
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

= 6.3.0 =

Release date: 12th April 2026

**Enhancements**

* [#500](https://github.com/beyondwords-io/wordpress-plugin/pull/500) Editable content ID field.
    * Adds a new "Content ID" feature, allowing users to input and fetch audio metadata by Content ID directly from the editor and classic metabox.

**Codebase Enhancements**

* Bump BeyondWords Player block `apiVersion` to fix failing Plugin Check jobs in CI.
* Various dependency updates to clear Dependabot security warnings.

= 6.2.0 =

Release date: 11th March 2026

**Enhancements**

* [#498](https://github.com/beyondwords-io/wordpress-plugin/pull/498) "Generate Audio" component improvements.
    * Stability and performance updates for the Generate Audio component in the block editor.

**Codebase Enhancements**

* [#474](https://github.com/beyondwords-io/wordpress-plugin/pull/474) Replace Mock API responses with pre_http_request filter.
    * Mockoon has been replaced with WordPress HTTP filters, and completely removed.

= 6.1.0 =

Release date: 2nd March 2026

**Fixes**

* [#483](https://github.com/beyondwords-io/wordpress-plugin/pull/483) and [#494](https://github.com/beyondwords-io/wordpress-plugin/pull/494) Fix reported REST API 404 responses.
    * Skip the second `wp_after_insert_post` triggered by Gutenberg's meta box save.
    * If the audio update request to our REST API results in a 404 then clear the stale content ID in WordPress and create new audio.
    * `hasContent()` guard on `onTrashPost()` / `onDeletePost()` now skips posts without BeyondWords content IDs e.g. revisions.

**Enhancements**

* [#481](https://github.com/beyondwords-io/wordpress-plugin/pull/481) Add `$context` param to `beyondwords_player_html` filter.
    * Use the `$context` param to enable filtering of the player HTML based on whether it was auto-prepended to `the_content` or added manually using a shortcode.
    * This filter can be used to hide only the auto-prepended players, enabling the shortcode to be used effectively in PHP template files.
    * Check the [Examples](https://docs.beyondwords.io/docs-and-guides/integrations/wordpress/filters?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin#beyondwords_player_html) in our docs for further information.

= 6.0.4 =

Release date: 7th January 2026

**Fixes**

* [#477](https://github.com/beyondwords-io/wordpress-plugin/pull/477) Fix `saveErrorMessage()` bug.
    * In the error message saving method we were incorrectly checking the integration method for the plugin instead of the post, which may explain a reported problem where unexpected 404 error messages were being saved.

**Codebase Enhancements**

* Improve the Cypress test suite by adding more assertions and testing more post statuses.
* Check for `ABSPATH` at the top of all PHP files.

= 6.0.3 =

Release date: 16th December 2025

**Compatibility**

* [#473](https://github.com/beyondwords-io/wordpress-plugin/pull/473) Reintroduce support for **PHP 8.0**.

= 6.0.2 =

Release date: 3rd December 2025

**Enhancements and Features**

* [#468](https://github.com/beyondwords-io/wordpress-plugin/pull/468) Tested up to WordPress 6.9.

**Fixes**

* [#469](https://github.com/beyondwords-io/wordpress-plugin/pull/469) Add `beyondwords_integration_method` into Inspect Panel copied data.
* [#466](https://github.com/beyondwords-io/wordpress-plugin/pull/466) Removed PHP type hints from methods hooked directly into WordPress `add_action`/`add_filter`.

= 6.0.1 =

Release date: 26th November 2025

**Fixes**

* [#464](https://github.com/beyondwords-io/wordpress-plugin/pull/464) Use `CoreUtils::getPostMetaKeys` to get all keys for removal.
* [#461](https://github.com/beyondwords-io/wordpress-plugin/pull/461) Accept `null` params from WP Core for `isProtectedMeta`.
* [#460](https://github.com/beyondwords-io/wordpress-plugin/pull/460) Accept a `null` parameter in `getLangCodeFromJsonIfEmpty`.

= 6.0.0 =

Release date: 10th November 2025

**Enhancements and Features**

* [#449](https://github.com/beyondwords-io/wordpress-plugin/pull/449) Added support for Magic Embed integration within the plugin.
    * A new **"Magic Embed"** option has been added under **Content > Integration method** in the plugin settings.
    * This option is intended for users working with **page builders such as Elementor**, who may have experienced issues getting the **REST API** to function correctly during initial setup.
    * **Enabling Magic Embed** will automatically add the Magic Embed script to each of your posts.
    * For correct functionality:
        * Magic Embed must be **selected in the WordPress plugin**.
        * Magic Embed must also be **[enabled and configured](https://docs.beyondwords.io/docs-and-guides/integrations/magic-embed/overview?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin#setup) in your BeyondWords dashboard**.
    * Posts previously created using the **REST API** will continue to use that method.
    * Refer to our [Magic Embed documentation](https://docs.beyondwords.io/docs-and-guides/integrations/magic-embed/overview?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) for more information.

***If your plugin is already working as expected with the REST API, we recommend continuing to use that integration.***

**Fixes**

* [#457](https://github.com/beyondwords-io/wordpress-plugin/pull/457) Removed segment marker assignment.
    * Fixes a reported JS issue where the block editor "+" button was not being displayed.

**Code Coverage**

* [#455](https://github.com/beyondwords-io/wordpress-plugin/pull/455) Increased PHPUnit test coverage.

**Refactoring**

* [#454](https://github.com/beyondwords-io/wordpress-plugin/pull/454) PHP type declarations.
* [#447](https://github.com/beyondwords-io/wordpress-plugin/pull/447) Make PHP methods static.

**Compatibility**

* ~~**PHP 8.1** is now our minimum supported version.~~

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
