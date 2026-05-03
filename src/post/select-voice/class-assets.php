<?php
/**
 * BeyondWords Select Voice — asset enqueue.
 *
 * Owns the classic-editor JS that wires up the language/voice select boxes.
 * Split from [class-select-voice.php](class-select-voice.php).
 *
 * @package BeyondWords\Post\SelectVoice
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\SelectVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Select Voice asset enqueue.
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
	 * Enqueue the Select Voice JS on classic-editor post screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin_enqueue_scripts( $hook ): void {
		if ( \BeyondWords\Core\CoreUtils::is_gutenberg_page() ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_register_script(
			'beyondwords-metabox--select-voice',
			BEYONDWORDS__PLUGIN_URI . 'src/post/select-voice/classic-metabox.js',
			[ 'jquery', 'underscore' ],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'beyondwords-metabox--select-voice',
			'beyondwordsData',
			[
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'root'  => esc_url_raw( rest_url() ),
			]
		);

		wp_enqueue_script( 'beyondwords-metabox--select-voice' );
	}
}
