<?php
/**
 * Plugin bootstrap.
 *
 * Single static `init()` invoked from `speechkit.php` after the autoloader
 * is registered. Runs migrations, registers cross-cutting hooks, then wires
 * up the per-screen UI classes — but only when the API connection is valid,
 * to keep the admin clean for un-authenticated installs.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap.
 *
 * Class references below use fully-qualified names so the wiring map is
 * legible without scrolling — every line shows exactly where a class lives.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Plugin {

	/**
	 * Boot the plugin.
	 *
	 * Order matters — `Updater::run()` first because subsequent classes read
	 * the `beyondwords_version` option it normalises. Settings classes register
	 * before the per-screen UI so that `Settings\Utils::has_valid_api_connection()`
	 * can gate UI registration on the result.
	 */
	public static function init(): void {
		Updater::run();

		// Third-party compatibility shims.
		\BeyondWords\Compatibility\WPGraphQL::init();

		// Core post-lifecycle hooks + meta registration.
		Core::init();

		// Site Health debug panel.
		\BeyondWords\SiteHealth\SiteHealth::init();

		// Front-end player rendering.
		\BeyondWords\Player\Player::init();

		// Post screen entry point — head meta tags for singular pages.
		\BeyondWords\Post\Post::init();

		// Settings page + REST endpoints.
		\BeyondWords\Settings\Tabs::init();
		\BeyondWords\Settings\Fields::init();
		\BeyondWords\Settings\Preselect::init();
		\BeyondWords\Settings\Settings::init();

		// Skip admin UI when we don't have credentials yet — prevents broken
		// JS in the editor for fresh installs.
		if ( ! \BeyondWords\Settings\Utils::has_valid_api_connection() ) {
			return;
		}

		// Posts list screen.
		\BeyondWords\Posts\BulkEdit::init();
		\BeyondWords\Posts\BulkEditNotices::init();
		\BeyondWords\Posts\Column::init();

		// Post edit screen — top-level UI.
		\BeyondWords\Post\AddPlayer::init();
		\BeyondWords\Post\BlockAttributes::init();
		\BeyondWords\Post\ErrorNotice::init();
		\BeyondWords\Post\InspectPanel::init();
		\BeyondWords\Post\Sidebar::init();

		// Post edit screen — classic-editor metabox controls.
		\BeyondWords\Post\ContentId::init();
		\BeyondWords\Post\GenerateAudio::init();
		\BeyondWords\Post\DisplayPlayer::init();
		\BeyondWords\Post\SelectVoice::init();
		\BeyondWords\Post\PlayerContent::init();
		\BeyondWords\Post\PlayerStyle::init();
		\BeyondWords\Post\Metabox::init();
	}
}
