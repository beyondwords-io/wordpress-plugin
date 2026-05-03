<?php
/**
 * BeyondWords Content ID — asset enqueue.
 *
 * Owns the classic-editor JS that powers the "Fetch" button next to the
 * Content ID input. Split from [class-content-id.php](class-content-id.php).
 *
 * @package BeyondWords\Editor\Components\ContentId
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Components\ContentId;

defined( 'ABSPATH' ) || exit;

/**
 * Content ID asset enqueue.
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
	 * Enqueue the Content ID JS on classic-editor post screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin_enqueue_scripts( $hook ): void {
		if ( \BeyondWords\Core\Utils::is_gutenberg_page() ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_register_script(
			'beyondwords-metabox--content-id',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/components/content-id/classic-metabox.js',
			[ 'wp-i18n' ],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'beyondwords-metabox--content-id',
			'beyondwordsData',
			[
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'root'  => esc_url_raw( rest_url() ),
			]
		);

		wp_enqueue_script( 'beyondwords-metabox--content-id' );
	}
}
