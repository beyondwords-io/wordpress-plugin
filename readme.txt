=== BeyondWords - AI audio for publishers ===

Contributors: beyondwords, stuartmcalpine
Donate link: https://beyondwords.io
Tags: text-to-speech, tts, audio, AI, voice cloning
Stable tag: 7.0.0
Requires at least: 6.6
Requires PHP: 8.0
Tested up to: 7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress articles into audio as you publish, with tools for distribution, monetization, and analytics.

== Description ==

Turn WordPress articles into audio as you publish, with tools for distribution, monetization, and analytics.

= Let audiences listen to your articles =

BeyondWords turns selected WordPress posts and pages into audio automatically.

Your editors keep working in WordPress. BeyondWords generates the audio, adds a customizable player, and automatically update audio when articles changes.

Choose from premade voices, generate a voice from a prompt, or create Instant and Professional voice clones. Voices are powered by ElevenLabs.

= What you can do =

* Choose which posts and pages become audio
* Create full audio articles or shorter audio summaries
* Add a customizable player with automatic word highlighting
* Correct pronunciations and review audio before publishing
* Build playlists and podcast feeds
* Monetize listening through advertising and subscriptions
* Measure plays, engagement, listening time, and completion

= Distribute in WordPress and beyond =

Embed audio in your articles, create playlists, publish podcast feeds to services such as Spotify and Apple Podcasts, share audio via URL, or share and download audio files.

= Built for WordPress workflows =

Set defaults for your publication, then override them on individual posts.

BeyondWords can regenerate audio when an article changes. You can also use WordPress bulk actions to generate audio for existing articles.

For page-builder setups such as Elementor, Magic Embed provides an alternative to the standard REST API integration.

= Monetize listening =

Make audio a subscriber benefit with full, ad-free listening, while using previews or ad-supported audio to encourage other readers to register or subscribe.

Upload your own audio ads or connect your existing ad server through VAST, the common standard for audio advertising.

= What publishers say =

> "We've had great customer feedback and the team have been quick to make adjustments based on our suggestions."
>
> Kenneth Creamer, Creamer Media

= Get started =

Book a demo and we'll help you choose the right setup, voices, player, and monetization options for your publication.

[Book a demo](https://beyondwords.io/book-a-demo/)

For questions or support, email <support@beyondwords.io>.

== Installation ==

1. Install and activate the BeyondWords plugin.
2. Connect it to your BeyondWords project.
3. Choose which posts and pages should become audio.
4. Set your default voice and player preferences.
5. Publish as usual.

== Frequently asked questions ==

= Can I choose which articles become audio? =

Yes. Set defaults for posts and pages, then change the settings for individual articles from the WordPress editor.

= What happens when I update an article? =

BeyondWords can regenerate the audio using the updated article text. You can also keep the existing audio when an update doesn't require a new version.

= Can we use our own voices? =

Yes. You can create Instant or Professional voice clones for journalists, authors, analysts, presenters, or other speakers. You must have the speaker's permission.

You can also choose a premade voice or generate a voice from a prompt.

= Can we monetize the audio? =

Yes. Upload your own audio ads, connect an existing ad server, or use access tiers to create different listening experiences for visitors, registered readers, and subscribers.

= Can we publish the audio as a podcast? =

Yes. BeyondWords lets you create podcast feeds for services such as Spotify and Apple Podcasts.

= Does it work with Elementor and other page builders? =

Yes. The plugin normally connects WordPress to BeyondWords through our REST API. For Elementor and other page-builder setups where that approach isn’t suitable, Magic Embed uses a script on your site to extract and sync the article content instead.

== Changelog ==

= 7.0.1 =

**Compatibility**

* [#624](https://github.com/beyondwords-io/wordpress-plugin/pull/624) Tested up to WordPress 7.1.

**Codebase Enhancements**

* [#624](https://github.com/beyondwords-io/wordpress-plugin/pull/624) Turn off Cypress web security so the block editor specs run on WordPress 7.1.
* [#568](https://github.com/beyondwords-io/wordpress-plugin/pull/568) Correct the 7.0.0 changelog: the live player preview for the "Add Player" block was reverted before release, and the block shows a static placeholder in the editor.

= 7.0.0 =

Release date: 12th August 2026

**Enhancements**

* [#566](https://github.com/beyondwords-io/wordpress-plugin/pull/566) Poll the content status before embedding the player.
    * The block and classic editor previews now wait until content has finished processing — showing a loading spinner meanwhile — instead of embedding the player immediately and caching a 404 when the audio or video isn't ready yet.
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
* [#555](https://github.com/beyondwords-io/wordpress-plugin/pull/555) Live player preview for the "Add Player" block. (Reverted in [#568](https://github.com/beyondwords-io/wordpress-plugin/pull/568) before 7.0.0 shipped — the block shows a static placeholder in the editor; the player still renders on the front end.)
* [#563](https://github.com/beyondwords-io/wordpress-plugin/pull/563) State-reflecting generation labels and "Legacy" model rename.
    * The "Generate audio" toggle now reads "Generation enabled" / "Generation disabled"; the "Standard" voice model bucket is relabelled "Legacy".
* [#532](https://github.com/beyondwords-io/wordpress-plugin/pull/532) Cache API reads and defer audio generation on WordPress VIP.
    * Editor dropdown data is cached in 15-minute transients; on VIP, audio create/update is deferred to WP-Cron so the save request returns immediately.
* [#598](https://github.com/beyondwords-io/wordpress-plugin/pull/598) Send taxonomy terms as `tags`.
    * Breaking: the `metadata` param is no longer sent. Code hooking `beyondwords_content_params` to write `$params['metadata']` must append to `$params['tags']` instead, or it will raise a fatal error.
* [#611](https://github.com/beyondwords-io/wordpress-plugin/pull/611) Tidy the classic editor metabox spacing and Content ID layout.

**Fixes**

* [#618](https://github.com/beyondwords-io/wordpress-plugin/pull/618) Poll content status before embedding the block-editor Preview panel player, so a still-processing post (more likely with a customised voice/model) no longer shows a broken preview.
* [#613](https://github.com/beyondwords-io/wordpress-plugin/pull/613) Fix author names with an ampersand showing as `Smith &amp; Sons` in the BeyondWords dashboard.
* [#612](https://github.com/beyondwords-io/wordpress-plugin/pull/612) Fix tags with an ampersand showing as `r&amp;d` in the BeyondWords dashboard.
* [#610](https://github.com/beyondwords-io/wordpress-plugin/pull/610) Stop the bulk "Generate audio" notice counting skipped posts as failures.
* [#606](https://github.com/beyondwords-io/wordpress-plugin/pull/606) Fix posts published with no player when two saves race to create audio.
* [#564](https://github.com/beyondwords-io/wordpress-plugin/pull/564) Sweep the paired `_transient_timeout_beyondwords_*` rows on uninstall, so no orphaned option rows are left behind.
* [#540](https://github.com/beyondwords-io/wordpress-plugin/pull/540) Escape the player `onload` attribute to prevent stored XSS via the Content ID.
* [#539](https://github.com/beyondwords-io/wordpress-plugin/pull/539) Add capability checks to the bulk-edit AJAX handler.
* [#541](https://github.com/beyondwords-io/wordpress-plugin/pull/541) Prevent a player SDK error from clobbering saved content on Fetch.
* [#554](https://github.com/beyondwords-io/wordpress-plugin/pull/554) Return JSON from the bulk-edit AJAX handler and catch delete errors.
* [#553](https://github.com/beyondwords-io/wordpress-plugin/pull/553) Resolve the post ID in `Content::get_content_without_excluded_blocks`.
* [#551](https://github.com/beyondwords-io/wordpress-plugin/pull/551) Track live meta for the Inspect panel Remove button.
* [#550](https://github.com/beyondwords-io/wordpress-plugin/pull/550) Coerce non-string API error messages to avoid a fatal `TypeError`.
* [#543](https://github.com/beyondwords-io/wordpress-plugin/pull/543) Guard a null languages API result in the classic editor voice select.
* [#542](https://github.com/beyondwords-io/wordpress-plugin/pull/542) Surface a `WP_Error` from `get_content()` instead of a fatal `TypeError`.
* [#588](https://github.com/beyondwords-io/wordpress-plugin/pull/588) Ship `symfony/dom-crawler` 5.4.52 to fix CVE-2026-45071 (XXE / local file disclosure).
    * The composer constraint now floors at the patched release, so a vulnerable version can no longer be bundled.

**Deprecations**

* Removed the `beyondwords_player_style`, `beyondwords_player_content`, `beyondwords_title_voice_id`, `beyondwords_summary_voice_id` and `beyondwords_disabled` post meta keys.
    * Existing values are preserved in the database and only removed on full uninstall; `beyondwords_disabled` is migrated to the new "Embed" setting on upgrade.
* The `beyondwords-*` `<head>` meta tags are now only emitted for the client-side (Magic Embed) integration.
* [#601](https://github.com/beyondwords-io/wordpress-plugin/pull/601) Removed the "Script" option from the post Source setting, which now offers "Post" or "Post + script".
    * Posts saved as "Script" are migrated to "Post + script" on upgrade, and keep embedding their script asset.

**Compatibility**

* Tested up to WordPress 7.0.
* [#515](https://github.com/beyondwords-io/wordpress-plugin/pull/515) PHP 8.5 support.
    * Run unit and e2e tests against PHP 8.0 and PHP 8.5 in GitHub Actions.
    * Bumped `phpVersion` in wp-env to 8.5.

**Codebase Enhancements**

* [#619](https://github.com/beyondwords-io/wordpress-plugin/pull/619) Run the Cypress suite with pretty permalinks, fixing the block editor Preview panel content-status poll spec.
* [#591](https://github.com/beyondwords-io/wordpress-plugin/pull/591) Refresh the developer documentation in `doc/` against the v7 codebase.
* [#608](https://github.com/beyondwords-io/wordpress-plugin/pull/608) Fix the intermittent detached-DOM failures in the block editor Cypress specs.
* [#607](https://github.com/beyondwords-io/wordpress-plugin/pull/607) Update two PHPUnit tests left stale by the Script-only Source removal.
* [#602](https://github.com/beyondwords-io/wordpress-plugin/pull/602) Align the player-visibility docs and Cypress specs with the "Embed" setting.
* [#600](https://github.com/beyondwords-io/wordpress-plugin/pull/600) Add must-follow documentation and changelog rules to `AGENTS.md`, with `CLAUDE.md` and Copilot pointer files.
* [#533](https://github.com/beyondwords-io/wordpress-plugin/pull/533) Fix failing Cypress tests for v7.
* [#538](https://github.com/beyondwords-io/wordpress-plugin/pull/538) Remove the unused `updatePostMeta` util from the Inspect panel.
* [#562](https://github.com/beyondwords-io/wordpress-plugin/pull/562) Re-enable Plugin Check on `plugin-check-action` v1.1.7.
* [#560](https://github.com/beyondwords-io/wordpress-plugin/pull/560) Cancel superseded CI runs via a concurrency group.
* [#516](https://github.com/beyondwords-io/wordpress-plugin/pull/516) Remove `environment` from the GitHub workflows.
* [#517](https://github.com/beyondwords-io/wordpress-plugin/pull/517), [#535](https://github.com/beyondwords-io/wordpress-plugin/pull/535) Update GitHub Actions dependencies.
* [#514](https://github.com/beyondwords-io/wordpress-plugin/pull/514), [#529](https://github.com/beyondwords-io/wordpress-plugin/pull/529), [#534](https://github.com/beyondwords-io/wordpress-plugin/pull/534), [#616](https://github.com/beyondwords-io/wordpress-plugin/pull/616) Dependency upgrades.
* Various dependency updates to clear Dependabot security warnings.

--------

[See the previous changelogs here](https://plugins.trac.wordpress.org/browser/speechkit/trunk/changelog.txt).
