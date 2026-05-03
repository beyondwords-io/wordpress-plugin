<?php
/**
 * BeyondWords Post Sidebar — asset enqueue.
 *
 * Owns the block-editor stylesheet for the post sidebar plugin slot.
 *
 * @package BeyondWords\Post\Sidebar
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\Sidebar;

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
		if ( ! \BeyondWords\Core\CoreUtils::is_gutenberg_page() ) {
			return;
		}

		if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'beyondwords-Sidebar',
			BEYONDWORDS__PLUGIN_URI . 'src/post/sidebar/PostSidebar.css',
			[],
			BEYONDWORDS__PLUGIN_VERSION
		);
	}
}
