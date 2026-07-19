<?php
/**
 * Plugin bootstrap.
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
	 * Order matters: `Updater::run()` normalises the version option later classes
	 * read, and Settings must register before the API-connection-gated UI wiring.
	 */
	public static function init(): void {
		Updater::run();

		// Must run before anything that might issue an API request.
		\BeyondWords\Api\Client::init();

		\BeyondWords\Compatibility\WPGraphQL::init();
		\BeyondWords\Post\Sync::init();
		\BeyondWords\SiteHealth\SiteHealth::init();
		\BeyondWords\Player\Player::init();
		\BeyondWords\Post\Head::init();

		\BeyondWords\Settings\Tabs::init();
		\BeyondWords\Settings\Fields::init();
		\BeyondWords\Settings\Preselect::init();
		\BeyondWords\Settings\Settings::init();

		// Skip admin UI without credentials — prevents broken editor JS on fresh installs.
		if ( ! \BeyondWords\Settings\Utils::has_valid_api_connection() ) {
			return;
		}

		\BeyondWords\Editor\Block\Assets::init();

		\BeyondWords\PostsList\Column::init();
		\BeyondWords\PostsList\BulkEdit::init();
		\BeyondWords\PostsList\Notices::init();

		\BeyondWords\Editor\Components\AddPlayer::init();
		\BeyondWords\Editor\Components\BlockAttributes::init();
		\BeyondWords\Editor\Components\ErrorNotice\Assets::init();
		\BeyondWords\Editor\Components\InspectPanel::init();
		\BeyondWords\Editor\Components\InspectPanel\Assets::init();
		\BeyondWords\Editor\Components\Sidebar\Assets::init();

		\BeyondWords\Editor\Components\ContentId::init();
		\BeyondWords\Editor\Components\ContentId\Assets::init();
		\BeyondWords\Editor\Components\GenerateAudio::init();
		\BeyondWords\Editor\Components\SelectVoice::init();
		\BeyondWords\Editor\Components\SelectVoice\Assets::init();
		\BeyondWords\Editor\Components\SettingsFields::init();
		\BeyondWords\Editor\Components\SettingsFields\Assets::init();
		\BeyondWords\Editor\Classic\Metabox::init();
		\BeyondWords\Editor\Classic\Assets::init();
	}
}
