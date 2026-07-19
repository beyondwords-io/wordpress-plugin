<?php
/**
 * BeyondWords block-editor asset enqueue.
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
	 * No API-valid check needed: class-plugin.php only calls init() with a
	 * valid API connection.
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
