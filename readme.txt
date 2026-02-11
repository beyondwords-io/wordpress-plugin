=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 6.0.4
Requires PHP: 8.1
Tested up to: 6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses.

== Description ==

[BeyondWords](https://beyondwords.io/) is the AI audio and video platform built for publishers. Connect the plugin to automatically generate audio and video versions of your WordPress posts, which can be instantly embedded into your pages or distributed across third-party platforms.

Choose from a variety of ElevenLabs and Azure voices to power your narration, or create your own hyper-realistic [voice clones](https://beyondwords.io/voice-cloning/).


== GET STARTED WITH BEYONDWORDS ==

To get started with BeyondWords for WordPress, [book a demo](https://beyondwords.io/book-a-demo/) with our team. 

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

= 6.0.4 =

Release date: 7th Jan 2026

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
        * Magic Embed must also be **[enabled and configured](https://docs.beyondwords.io/docs-and-guides/integrations/magic-embed/overview#setup) in your BeyondWords dashboard**.
    * Posts previously created using the **REST API** will continue to use that method.
    * Refer to our [Magic Embed documentation](https://docs.beyondwords.io/docs-and-guides/integrations/magic-embed/overview) for more information.

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
