=== BeyondWords - Text-to-Speech ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text to speech, text to audio, tts, speech synthesis, podcast, audio
Stable tag: 4.5.1
Requires PHP: 7.4
Tested up to: 6.4
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

= 4.5.1 =

Release date: 14th March 2024

**Enhancements**

* Make the BeyondWords column in WP admin sortable.

**Fixes**

* We now only render plugin components on the Post edit screens if the REST API credentials have been validated.
* Improved handling for REST API calls that fail while editing posts. This fixes the following issues:
    * The block editor crashed if the API Key and/or Project ID had not been entered in the plugin settings.
    * The block editor crashed if REST API calls failed for other reasons e.g. network problems.
* Removed a console log intended for debugging.

= 4.5.0 =

Release date: 1st March 2024

**Enhancements**

* Audio content for posts created with a future publish date is now available to preview in the WordPress admin players. To achieve this we handle a `preview_token` returned from the BeyondWords REST API.
* In Site Health and throughout the code, replace "Allowed post types" and "Supported post types" with "Compatible post types" and "Incompatible post types" to improve clarity.

**Code Refactoring**

* A general tidy-up of all code and docs for the public open-source release of our plugin repo at https://github.com/beyondwords-io/wordpress-plugin.
* Remove `BEYONDWORDS_DEBUG` constant throughout the code - this was only ever used internally for the legacy player, so it has been removed for the public repo release.
* Prevent side-effects in PHP constructors.

= 4.4.0 =

Release date: 11th January 2024

**Enhancements**

* Tested up to WordPress 6.4.
* Tested up to PHP 8.3.
* Delete audio when *Remove* feature is used. When you *Remove* audio using the Inspect panel we now make a DELETE request to the BeyondWords API to keep WordPress and the BeyondWords dashboard in sync.
* Add BeyondWords version and WordPress version into copied Inspect Panel data.

**Fixes**

* Use optional chaining before accessing properties. This fixes an undefined error when the sidebar is loaded. The issue was reported by Cypress testing in our CI pipeline.

= 4.3.0 =

Release date: 7th December 2023

**Deprecations**

We have renamed and removed some of our WordPress filters to make them simpler to understand and to implement.

The deprecated filters listed below are scheduled to be removed from the code in plugin v5.0, so please migrate to any replacements as soon as possible.

* Filters we have simply renamed (the functionality remains exactly the same):
    * `beyondwords_body_params`: Renamed to `beyondwords_content_params`
    * `beyondwords_player_styles`: Renamed to `beyondwords_settings_player_styles`
    * `beyondwords_post_types`: Renamed to `beyondwords_settings_post_types`
    * `beyondwords_post_statuses`: Renamed to `beyondwords_settings_post_statuses`
* Filters we have removed because there are suitable alternatives:
    * `beyondwords_amp_player_html`: Instead, use the `beyondwords_player_html` filter which is now applied to the player HTML for AMP and non-AMP content.
    * `beyondwords_content`: Instead, set the `body` key in the `beyondwords_content_params` filter
    * `beyondwords_post_metadata`: Instead, set the `metadata` key in the `beyondwords_content_params` filter
    * `beyondwords_post_audio_enabled_blocks`: Instead, refer to our [Filter content](https://docs.beyondwords.io/docs-and-guides/content/filter-content) docs for an alternative.
    * `beyondwords_post_player_enabled`: Instead, return an empty string in the `beyondwords_player_html` filter to hide the player. Alternatively, you can set `published` to `false` in the BeyondWords dashboard.
* Filters we have deprecated because the functionality is no longer required:
    * `beyondwords_js_player_params`: This only applies to the Legacy player which will be removed in plugin v5.0
    * `beyondwords_content_id`: We believe this is not being used, please contact [support@beyondwords.io](mailto:support@beyondwords.io) if you are using this filter.
    * `beyondwords_project_id`: We believe this is not being used, please contact [support@beyondwords.io](mailto:support@beyondwords.io) if you are using this filter.

We have also renamed `PostContentUtils::getBodyJson` to `PostContentUtils::getContentParams`, to match the `beyondwords_content_params` filter name.

= 4.2.4 =

Release date: 15th November 2023

**Fixes**

* Ensure the `$content` param of `Beyondwords\Wordpress\Core\Player\Player::hasCustomPlayer` is a string before using `strpos`.

= 4.2.3 =

Release date: 2nd November 2023

**Fixes**

* Pass full-size (originally uploaded) featured image to the BeyondWords API instead of a cropped thumbnail. The 240x240 thumbnail we replaced was not suitable for some styles of the latest player.

= 4.2.2 =

Release date: 27th October 2023

**Fixes**

* Ensure segment markers are added to the HTML body before we pass it to our REST API. This fixes an issue where "Playback from paragraph" was not working in some cases.
* Update the block control restrictions. This is to fix an issue where the block controls – including the "Audio processing enabled/diabled" toggle – sometimes did not appear when a block was clicked.

= 4.2.1 =

Release date: 12th October 2023

**Enhancements**

* Prevent player analytics events from being recorded in WordPress admin by setting the `{ analyticsConsent: 'none' }` param for admin players.

= 4.2.0 =

Release date: 10th October 2023

**Enhancements**

* Add `[beyondwords_player]` shortcode. See our "WordPress shortcode" docs at [https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress].

**Fixes**

* Ensure an array is always returned for `PlayerStyle::getCachedPlayerStyles()` to prevent PHP warnings.
* For AMP content, the player is no longer auto-prepended if a custom player is found in the content.
* Clean up 'beyondwords_player_style' option on plugin uninstall.

= 4.1.2 =

Release date: 21st September 2023

**Enhancements**

* Added "Large" option into "Player style" plugin setting.

= 4.1.1 =

Release date: 15th September 2023

**Enhancements**

* Tested up to WordPress 6.3.

= 4.1.0 =

Release date: 15th September 2023

**Enhancements**

* "Player style" support.
    * A "Player style" setting has been added to the plugin settings screen where you can assign the default player style for your audio players.
    * The default style can be overridden for each post on the post edit screen.
    * A `beyondwords_player_styles` filter is available so you can modify the options of the "Player style" select boxes in WordPress admin.
    * We are currently beta testing a video. To discuss enabling this for your project please contact [support@beyondwords.io](mailto:support@beyondwords.io).
    * For more information on the supported styles see `playerStyle` at [https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md].
* "Delete audio" option added into "Bulk actions".

**Fixes**

* Fixed the "Remove" button for the Inspect panel in Classic Editor.
* BeyondWords errors now show in the Classic Editor metabox.
* The "Language" and "Voice" selectors now show in the Classic Editor metabox.

= 4.0.6 =

Release date: 7th September 2023

**Fixes**

* Always call `setAttributes` for `beyondwordsMarker` in the `editor.BlockEdit` filter. Previously this was only being called when the BeyondWords sidebar panel was open.
* Remove the unwanted `blocks.getSaveContent.props` filter. This was doing nothing because the filter name was incorrect (it was a typo for `blocks.getSaveContent.extraProps` which we don't need).

= 4.0.5 =

Release date: 24th August 2023

**Fixes**

* Fix reported PHP Warnings by checking that [get_current_screen](https://developer.wordpress.org/reference/functions/get_current_screen/) return value is truthy.

= 4.0.4 =

Release date: 17th August 2023

**Fixes**

* Fix an intermittent issue where segment markers are not always assigned to blocks. Our `blocks.registerBlockType` filter now matches [the example in the official WordPress docs](https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#blocks-registerblocktype).
* Hide the audio player widget (the player docked to the bottom of the screen when you scroll) in WordPress admin screens.

= 4.0.3 =

Release date: 4th August 2023

**Fixes**

* Cast the return value of [get_the_post_thumbnail_url](https://developer.wordpress.org/reference/functions/get_the_post_thumbnail_url/) to string before sending to the BeyondWords API. This fixes an issue where we were sending boolean `false` as the `image_url` field for posts without a featured image.

= 4.0.2 =

Release date: 3rd August 2023

**Fixes**

* Prefix `/languages` and `/voices` endpoints with `/organization` to reflect recent changes in API. This fixes the issue where no languages or voices could be found.

= 4.0.1 =

Release date: 30th July 2023

**Fixes**

* Set minimum supported versions to PHP 7.4 and WordPress 5.8.
* Fix issue where the audio player was disappearing in Classic Editor when the Player UI setting was "Headless" or "Disabled".
* Remove unwanted console logs.

= 4.0.0 =

Release date: 26th July 2023

**Fixes**

* Fixed broken "Copy" button in Block editor Inspect panel.

**Improvements**

* API calls are now made to the new BeyondWords REST API at [api.beyondwords.io](https://api.beyondwords.io).
* Custom languages and voices for your audio. [Docs here](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/add-languages?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin).
* Our advanced "Latest" audio player is available. [Docs here](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/updating-to-the-latest-player?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin).
* Using "Headless mode" you can take advantage of audio processing and the audio CMS whilst building their own front-end players. [Docs here](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/headless-mode?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin).
* The following new filters have been added.
    * [beyondwords_body_params](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_body_params?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin).
    * [beyondwords_content_id](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_content_id?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)
    * [beyondwords_player_script_onload](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_player_script_onload?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)
    * [beyondwords_player_sdk_params](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_player_sdk_params?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)
    * [beyondwords_post_audio_enabled_blocks](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_post_audio_enabled_blocks?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)
    * [beyondwords_project_id](https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_project_id?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin)

**Breaking changes**

* `beyondwords_podcast_id` is now stored and retrieved as `beyondwords_content_id`.
    * This should not affect most websites, but if your site has custom PHP that uses the `beyondwords_podcast_id` custom field then you need to update your code to prefer `beyondwords_content_id`.
    * Existing `beyondwords_podcast_id` post metadata will be updated to `beyondwords_content_id` automatically as each post is queried in WordPress requests.
* For WpGraphQL, `beyondwords.podcastId` was defined as type: `Int`. We have changed the type to `String` to support the new ID format (UUID). Your code may need to be updated to handle this change.
* Removed built-in WPML support (our compatibility unfortunately failed over time). Support for translation plugins can now be achieved using the following WordPress hooks:
    * `beyondwords_body_params`
    * `beyondwords_project_id`
* Some older deprecated filters have been removed:
    * `speechkit_post_types`: Migrate to `beyondwords_post_types`
    * `speechkit_post_statuses`: Migrate to `beyondwords_post_statuses`
    * `sk_player_before`: Migrate to beyondwords_js_player_html / beyondwords_amp_player_html
    * `sk_the_content`: Migrate to `beyondwords_js_player_html` / `beyondwords_amp_player_html`
    * `sk_player_after` Migrate to `beyondwords_js_player_html` / `beyondwords_amp_player_html`
    * `speechkit_js_player_html` Migrate to `beyondwords_js_player_html`
    * `speechkit_amp_player_html` Migrate to `beyondwords_amp_player_html`
    * `speechkit_post_player_enabled` Migrate to `beyondwords_post_player_enabled`

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
