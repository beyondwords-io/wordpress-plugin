=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 6.1.0-beta.1
Requires PHP: 8.1
Tested up to: 6.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses.

== Description ==

BeyondWords is the AI voice platform that brings frictionless audio publishing to newsrooms, writers, and businesses. Automatically create audio versions of WordPress posts and pages and embed via a customizable player. Lifelike neural voices and customizable text-to-speech algorithms deliver realistic speech that keeps listeners coming back for more.

== GET STARTED WITH BEYONDWORDS ==

To get started with BeyondWords, please [book a demo](https://beyondwords.io/book-a-demo/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) with our team.

We’ll walk you through the platform, discuss your goals, and help set up your account so you can begin converting your WordPress content into audio.

Any questions? Visit our website [https://beyondwords.io](https://beyondwords.io/?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin) or email <support@beyondwords.io>.

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

== Changelog ==

= 6.1.0-beta.1 =

Release date: TBC

**Fixes**

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

* **PHP 8.1** is now our minimum supported version.

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
