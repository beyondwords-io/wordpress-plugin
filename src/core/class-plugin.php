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

		// API client — registers the http_request_args filter that injects
		// auth + Content-Type for outbound BeyondWords API calls. Must run
		// before anything that might issue an API request.
		\BeyondWords\Api\Client::init();

		// Third-party compatibility shims.
		\BeyondWords\Compatibility\WPGraphQL::init();

		// WordPress ↔ BeyondWords post sync (save/trash/delete + meta registration).
		\BeyondWords\Post\Sync::init();

		// Site Health debug panel.
		\BeyondWords\SiteHealth\SiteHealth::init();

		// Front-end player rendering.
		\BeyondWords\Player\Player::init();

		// Post screen entry point — head meta tags for singular pages.
		\BeyondWords\Post\Head::init();

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

		// Block-editor JS bootstrap (registers every @wordpress/plugins slot
		// under src/editor/).
		\BeyondWords\Editor\Block\Assets::init();

		// Posts list screen (edit.php) — column, bulk-edit, admin notices.
		\BeyondWords\AdminPosts\Column::init();
		\BeyondWords\AdminPosts\BulkEdit::init();
		\BeyondWords\AdminPosts\Notices::init();

		// Post edit screen — top-level UI.
		\BeyondWords\Editor\Components\AddPlayer::init();
		\BeyondWords\Editor\Components\AddPlayer\Assets::init();
		\BeyondWords\Editor\Components\BlockAttributes::init();
		\BeyondWords\Editor\Components\ErrorNotice\Assets::init();
		\BeyondWords\Editor\Components\InspectPanel::init();
		\BeyondWords\Editor\Components\InspectPanel\Assets::init();
		\BeyondWords\Editor\Components\Sidebar\Assets::init();

		// Post edit screen — classic-editor metabox controls.
		\BeyondWords\Editor\Components\ContentId::init();
		\BeyondWords\Editor\Components\ContentId\Assets::init();
		\BeyondWords\Editor\Components\GenerateAudio::init();
		\BeyondWords\Editor\Components\DisplayPlayer::init();
		\BeyondWords\Editor\Components\SelectVoice::init();
		\BeyondWords\Editor\Components\SelectVoice\Assets::init();
		\BeyondWords\Editor\Components\PlayerContent::init();
		\BeyondWords\Editor\Components\PlayerStyle::init();
		\BeyondWords\Editor\Classic\Metabox::init();
		\BeyondWords\Editor\Classic\Assets::init();
	}
}
