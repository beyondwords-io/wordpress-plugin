<?php
/**
 * BeyondWords Metabox — asset enqueue.
 *
 * @package BeyondWords\Editor\Classic
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Classic;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox asset enqueue.
 *
 * @since 7.0.0
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ self::class, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Enqueue Metabox CSS on classic-editor post screens for compatible post types.
	 *
	 * No API-valid check needed: class-plugin.php only calls init() with a
	 * valid API connection.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin_enqueue_scripts( $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'beyondwords-metabox',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/classic/classic.css',
			false,
			BEYONDWORDS__PLUGIN_VERSION
		);
	}
}
