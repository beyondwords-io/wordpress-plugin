<?php
/**
 * BeyondWords Metabox — asset enqueue.
 *
 * Owns the classic-editor metabox stylesheet. Split from
 * [class-metabox.php](class-metabox.php) so the container/render concerns
 * stay separate from CSS registration.
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
	 * The API-valid gate is handled at bootstrap time in
	 * [src/core/class-plugin.php](src/core/class-plugin.php) — `init()`
	 * isn't called without a valid API connection — so we only need the
	 * per-request hook + post-type checks here.
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
