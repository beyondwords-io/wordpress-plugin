=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 5.4.0
Requires PHP: 8.0
Tested up to: 6.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses.

== Description ==

BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses. Automatically create audio versions of WordPress posts and pages and embed via a customizable player. Lifelike neural voices and customizable text-to-speech algorithms deliver realistic speech that keeps listeners coming back for more.

== GET STARTED IN MINUTES ==

1. [Create a free Pilot account](https://dash.beyondwords.io/auth/signup?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) (no credit card required)
2. Copy the project ID and API key from your dashboard
3. Download and [set up the WordPress plugin](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/install?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)

== Key features of our text-to-speech plugin for WordPress: ==

* Easily pick which pages and posts are converted into audio
* Audio is automatically created and embedded via our customizable player
* Powerful and versatile player options to fit your web and mobile experience.

== Key features of your BeyondWords dashboard: ==

* Lifelike AI voices enhanced by customizable NLP algorithms
* Create or edit audio in the Text-to-Speech Editor
* Curate custom playlists and podcast feeds
* Embed audio manually or share via URL
* Monitor engagement through analytics
* Manage audio through the audio CMS
* Monetize your audio with audio ads

You are just minutes away from engaging audiences with AI audio. Join the hundreds of WordPress publishers already using our text-to-speech plugin and platform to make the most of their news articles, reports, guides, and more.

If you have any questions, feedback, or issues, please email <support@beyondwords.io>.

== CUSTOMER TESTIMONIAL ==

> "We've been using BeyondWords to convert our articles into audio for over a year. Overall, we are very impressed with the service. The quality of the audio is consistently the best we've found available, the plugin and dashboard provides all the functionality we need, processing and delivery of the audio is fast and the players fit nicely on our page. We've had great customer feedback and the team have been quick to make adjustments based on our suggestions."
>
> &mdash; Kenneth Creamer, Creamer Media

== ADVANCED TEXT-TO-SPEECH ==

Looking for the best text-to-speech plugin on WordPress?

Using methods like natural language processing (NLP), BeyondWords' unique text processing algorithms optimally convert your content into speech synthesis markup language (SSML). This enables AI voices to effectively pronounce elements that other platforms can struggle with, such as names, numbers, and dates, as well as filtering out elements that shouldn't be read aloud. You can even add aliases to ensure everything is read exactly how you want it.

BeyondWords gives you access to neural voices from Google Cloud, Amazon Web Services, and Microsoft Azure (500+ voices across 140+ language locales).

You can also get access to premium neural voices &mdash; voice clones of professional voice actors that are exclusive to BeyondWords. Users have the option to develop a completely bespoke custom voice using our voice cloning service.

The result is naturalistic spoken-word audio content that engages your target audience &mdash; at a fraction of the cost of human recordings.

== EFFORTLESS DISTRIBUTION ==

BeyondWords makes it easy to reach new audiences and grow your listenership. As well as auto-embedding audio players to your WordPress site, you can embed your audio manually or share via URL. You can even download your audios as mp3 files.

This includes content created automatically with our WordPress text-to-speech plugin, as well as content created manually with the Text-to-Speech Editor &mdash; perfect for audio newsletters.

Users can even create custom playlists that keep listeners listening for longer. These can be embedded, shared via URL, or even distributed via podcast feed. That means you can reach audiences through platforms like Apple Podcasts and Spotify.

== ANALYTICS AND MONETIZATION ==

You get access to project analytics, which means you can track listener engagement at the project level through your BeyondWords dashboard. Users can also get access to audio analytics, as well as Google Analytics and Google Tag Manager integrations.

You can even leverage your listenership through audio advertising. Use our self-serve audio advertising feature to create your own campaigns or use VAST (video ad serving template) to connect a programmatic advertising platform, such as Google Ad Manager.

== CREATE YOUR FREE ACCOUNT TODAY ==

[Create your Pilot account](https://dash.beyondwords.io/auth/signup?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) to see how the BeyondWords text-to-speech plugin improves reach and engagement on your WordPress site. There's a [pricing plan](https://beyondwords.io/pricing/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) to suit every publisher, from independent writers to global media companies.

Any questions? [Visit our website](https://beyondwords.io/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) or email <hello@beyondwords.io>.

== Changelog ==

= 5.4.0 =

Release date: 22nd April 2025

**Fixes:**

* [#436](https://github.com/beyondwords-io/wordpress-plugin/pull/436) Article language support in WordPress.
    * Each `POST` and `PUT` request to the BeyondWords REST API now includes the article language.
    * This update ensures compatibility with recent REST API changes and improves support for multilingual websites.
* [#432](https://github.com/beyondwords-io/wordpress-plugin/pull/432) Delete audio and post meta when posts are trashed or deleted.
    * When a post is moved into the trash in WordPress it is now deleted from the BeyondWords dashboard, and all associated BeyondWords post meta data (e.g. the content ID) is also deleted.
    * This resolves REST API error messages caused by `DELETE` requests for content that no longer exists.
    * After this update you will need to regenerate audio again if you restore posts from the trash.

**Enhancements and Features:**

* Tested up to WordPress 6.8.
* [#405](https://github.com/beyondwords-io/wordpress-plugin/pull/405) "Post a review" notice in WordPress admin.
    * A new notice prompts you to review our plugin on the WordPress Plugin Directory.
    * The notice will appear 14 days after activating the plugin, or 14 days after updating to this version (or later) .
    * The message is permanently dismissed once you close it or follow the link to leave a review.
* [#434](https://github.com/beyondwords-io/wordpress-plugin/pull/434) Update Cypress to version 14.

= 5.3.1 =

Release date: 11th February 2025

**Fixes:**

* Replace `esc_js` with `esc_attr` for the player `onload` attribute.
    * Fixes a reported issue where UTF-8 characters in the *Call to Action* setting were not being output correctly.

= 5.3.0 =

Release date: 3rd February 2025

**Enhancements and Features:**

* [426](https://github.com/beyondwords-io/wordpress-plugin/pull/426) Add support for article summarisation.
    * A new "Player Content" field has been added to the post edit screens for both the Block Editor and Classic Editor.
        * Selecting "Summary" in the BeyondWords side panel loads the summarised audio into the player.
        * The default value, "Article," continues to load the full article content.
    * To use this feature, ensure that summarisation is included in your plan and enabled in your BeyondWords dashboard.
* [429](https://github.com/beyondwords-io/wordpress-plugin/pull/429) Add Summarization tab in plugin settings.
    * Adds a link to manage summarization settings for your project, including the summarization voice.

**Fixes**

* Prioritise post-specific player settings.
    * It was previously possible for the plugin settings in the "Player" tab to overwrite any post-specific settings such as the player style.
    * The priority has now been corrected so any post-specific settings will overwrite the plugin settings.
* Fix Clipboard.js error.
    * Replace Clipboard.js script with the WordPress core version to address a console error in the block editor.
* [425](https://github.com/beyondwords-io/wordpress-plugin/pull/425) Update asset URL to Azure Storage.
    * The AMP logo image has been moved from AWS S3 to Azure Storage.

**Codebase Enhancements**

* Refactor React code for the player.
    * Updated the block editor player embed script to align with React players we use in other BeyondWords projects.
    * This included removing the `react-script-tag` dependency.
* Added the `__nextHasNoMarginBottom` property to various components.
    * `__nextHasNoMarginBottom` has been set in various places to address a console warning from WordPress core.
* Updated dependencies.
    * Updated several dependencies including `@mockoon/cli`, `@wordpress/env`, `@wordpress/eslint-plugin`, `@wordpress/scripts`, and `uuid`.
    * Removed unused dependencies.

= 5.2.2 =

Release date: 10th December 2024

**Fixes**

* [423](https://github.com/beyondwords-io/wordpress-plugin/pull/423) Optimise WordPress HTTP calls to BeyondWords REST API.
    * Increase the WordPress default `timeout` param from `5` to `30`. This is to address a reported issue where REST API calls are sometimes timing out in WordPress/PHP before the REST API is able to respond.
    * Also removed the `sslverify` param for API calls. This is no longer recommended.

= 5.2.1 =

Release date: 28th November 2024

**Fixes**

* [#421](https://github.com/beyondwords-io/wordpress-plugin/pull/421) Update WordPress GET voice queries to use scopes.
    * Fixes the issue where multilingual voices were not being shown in voices dropdown menus.
* [#417](https://github.com/beyondwords-io/wordpress-plugin/pull/417) Improve handling of failed REST API requests when syncing settings.
    * Add HTTP status code into failed API credentials validation message to improve debugging for empty response body.
    * Fix the undefined index errors that are logged when REST API responses do not contain the expected structure.
    * Replace transients with object cache to reduce db writes.
    * Add Query Monitor plugin to wp-env local dev config to help with debugging.

= 5.2.0 =

Release date: 22nd November 2024

**Enhancements**

* [#414](https://github.com/beyondwords-io/wordpress-plugin/pull/414) Tested up to WordPress 6.7.
* [#409](https://github.com/beyondwords-io/wordpress-plugin/pull/409) Support the [recommended inline script tag method](https://github.com/beyondwords-io/player/blob/main/doc/getting-started.md) to embed players.
    * ***This opt-in feature is experimental and may change, or be removed, in the near future***.
    * Opt-in to the inline `<script>` tag method of auto-embedding audio players by defining the constant `BEYONDWORDS_PLAYER_INLINE_SCRIPT_TAG` as `true` in your `wp-config.php`.
    * This was added because the recent removal of the deprecated `beyondwords_content_id` filter caused problems for a publisher who had been using it to display an audio player from another post on their homepage.
    * After opting-in, audio players that are auto-prepended to the post body should now use the `beyondwords_content_id` and `beyondwords_project_id` from the associated post being queried within The Loop.
    * A known-issue is the current implementation is currently incompatible with both the *BeyondWords shortcode* and the *BeyondWords player block*. Compatibility will be ensured before this experimental opt-in feature is shipped to all users. In the meantime players added using either the shortcode or player block are unlikely to appear when the `BEYONDWORDS_PLAYER_INLINE_SCRIPT_TAG` is `true`.

**Fixes**

* [#413](https://github.com/beyondwords-io/wordpress-plugin/pull/413) Refactor API calls to prevent "invalid creds" messages
    * Since upgrading to version 5 a few publishers have reported problems with their valid API credentials being flagged as invalid, so the checks we had in place to validate the API Key and Project ID have been updated.
    * API calls to create/update BeyondWords content can now be made with potentially invalid credentials. If this happens (e.g. if your API Key has been revoked) the 401 Unauthorized error code and error message will be stored for your requests and visible in WordPress admin.

= 5.1.0 =

Release date: 30th October 2024

**Fixes**

* [#404](https://github.com/beyondwords-io/wordpress-plugin/pull/404) Bring auto-publish setting into WordPress to fix auto-publishing.
    * In some cases WordPress was publishing audio regardless of the auto-publish setting in the dashboard.
    * After this update any content created with the WordPress plugin will need to be published in the BeyondWords dashboard.
* [#407](https://github.com/beyondwords-io/wordpress-plugin/pull/407) Regenerate audio for all post statuses
    * If a post has a content ID for audio then we now *always* make PUT requests to the BeyondWords REST API when the post is updated.
    * This fixes an issue where the `published` property of audio was not set to `false` when WordPress posts were moved back to `draft` status.
* [#408](https://github.com/beyondwords-io/wordpress-plugin/pull/408) Generate Audio checkbox in Classic Editor doesn't reflect the "Preselect" setting
    * A change in the `v5.0` update meant the "Preselect generate audio" JS script was no longer being enqueued. This should now be fixed.

= 5.0.0 =

Release date: 15th October 2024

**Enhancements**

* [#385](https://github.com/beyondwords-io/wordpress-plugin/pull/385) Extend plugin settings using a tabbed interface.
    * The plugin settings screen has been expanded to include settings from the BeyondWords dashboard, allowing you to set various BeyondWords settings without leaving WordPress.
    * Changes made in WordPress will be immediately copied over to the BeyondWords dashboard when "Save settings" is pressed.
    * Changes made in the BeyondWords dashboard will be copied over to WordPress when you visit the relevant plugin settings tab.

**Breaking changes**

* Legacy audio player support has been removed.
    * The legacy BeyondWords player is no longer natively supported in the WordPress plugin.
    * The standard [BeyondWords Player](https://docs.beyondwords.io/docs-and-guides/player/overview) is now the only built-in option for the audio player.
* Remove built-in Elementor compatibility.
    * Basic support for audio generation and auto-player embeds should still work for posts that are created with Elementor, although you will be unable to see a BeyondWords player in the Elementor post edit screens. To view our player in WordPress admin you can temporarily switch to the Block or Classic editors.
    * Refer to our [WordPress filters](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters) docs and the [Elementor hooks](https://developers.elementor.com/docs/hooks/) docs if you wish to add Elementor support to your site.
* Stop saving the legacy `beyondwords_podcast_id` param.
    * This change means that posts generated with versions `v5.0.0` and later will not play audio if the plugin is downgraded to `v3.x` or below.
    * If you need to downgrade to `v3.x` after using `v5.x` please contact us for support.
* Remove deprecated filters.
    * A number of deprecated filters have now been removed from the source code.
    * Refer to our [WordPress Filters](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters) documentation to view the current filters we provide.

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
