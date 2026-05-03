<?php
/**
 * BeyondWords block-editor asset enqueue.
 *
 * Owns the registration and enqueue of the bundled block-editor JS that
 * registers every `@wordpress/plugins` slot under [src/editor/block/](src/editor/block/).
 *
 * @package BeyondWords\Editor\Block
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Block-editor asset enqueue.
 *
 * @since 7.0.0
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_block_editor_assets' ], 1, 0 );
	}

	/**
	 * Enqueue the bundled block-editor JS on compatible post-type screens.
	 *
	 * The API-valid gate is handled at bootstrap time in
	 * [src/core/class-plugin.php](src/core/class-plugin.php) — `init()`
	 * isn't called without a valid API connection — so we only need the
	 * per-request post-type check here.
	 */
	public static function enqueue_block_editor_assets(): void {
		if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		$asset_file = include BEYONDWORDS__PLUGIN_DIR . 'build/index.asset.php';

		wp_enqueue_script(
			'beyondwords-block-js',
			BEYONDWORDS__PLUGIN_URI . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	}
}
