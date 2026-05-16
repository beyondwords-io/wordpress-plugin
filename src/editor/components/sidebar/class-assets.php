<?php
/**
 * BeyondWords Post Sidebar — asset enqueue.
 *
 * Owns the block-editor stylesheet for the post sidebar plugin slot.
 *
 * @package BeyondWords\Editor\Components\Sidebar
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Components\Sidebar;

defined( 'ABSPATH' ) || exit;

/**
 * Sidebar asset enqueue.
 *
 * @since 7.0.0
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_assets', [ self::class, 'enqueue_block_assets' ] );
	}

	/**
	 * Enqueue the post sidebar CSS on Gutenberg screens for compatible post types.
	 */
	public static function enqueue_block_assets(): void {
		if ( ! \BeyondWords\Core\Utils::is_gutenberg_page() ) {
			return;
		}

		if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'beyondwords-sidebar',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/components/sidebar/sidebar.css',
			[],
			BEYONDWORDS__PLUGIN_VERSION
		);
	}
}
