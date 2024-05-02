=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 4.7.0
Requires PHP: 7.4
Tested up to: 6.5
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

= 4.7.0 =

Release date: 2nd May 2024

**Fixes**

* [#388](https://github.com/beyondwords-io/wordpress-plugin/pull/388) If the post body [has_blocks](https://developer.wordpress.org/reference/functions/has_blocks/) then remove the `wpautop` filter before sending it to our REST API. This filter was stripping closing `</p>` tags from empty paragraph blocks.

**Enhancements**

* [#386](https://github.com/beyondwords-io/wordpress-plugin/pull/386) Prepend custom plugin links instead of appending them
* [#384](https://github.com/beyondwords-io/wordpress-plugin/pull/384) Refactoring to improve code quality
* [#388](https://github.com/beyondwords-io/wordpress-plugin/pull/388) Unit tests for empty paragraphs
* Prevent empty `data-beyondwords-marker` attributes

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
