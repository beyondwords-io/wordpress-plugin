<?php
/**
 * BeyondWords Metabox — asset enqueue.
 *
 * Owns the classic-editor metabox stylesheet. Split from
 * [class-metabox.php](class-metabox.php) so the container/render concerns
 * stay separate from CSS registration.
 *
 * @package BeyondWords\Post\Metabox
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\Metabox;

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
	 * Enqueue Metabox CSS on classic-editor post screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin_enqueue_scripts( $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'beyondwords-Metabox',
			BEYONDWORDS__PLUGIN_URI . 'src/post/metabox/Metabox.css',
			false,
			BEYONDWORDS__PLUGIN_VERSION
		);
	}
}
