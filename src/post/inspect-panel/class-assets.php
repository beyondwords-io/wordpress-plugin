<?php
/**
 * BeyondWords Inspect Panel — asset enqueue.
 *
 * Owns the classic-editor JS for the Inspect metabox. Split from
 * [class-inspect-panel.php](class-inspect-panel.php) so each feature folder has
 * a single class responsible for asset registration.
 *
 * @package BeyondWords\Post\InspectPanel
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\InspectPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Inspect panel asset enqueue.
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
	 * Enqueue Inspect JS on classic-editor post screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin_enqueue_scripts( $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'beyondwords-inspect',
			BEYONDWORDS__PLUGIN_URI . 'src/post/inspect-panel/js/inspect.js',
			[ 'jquery' ],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);
	}
}
