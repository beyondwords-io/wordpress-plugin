<?php
/**
 * BeyondWords Settings Fields — asset enqueue.
 *
 * Owns the classic-editor JS that wires up the Content/Format/Player <select>
 * controls (show/hide + Embed option recompute). Split from
 * [class-settings-fields.php](class-settings-fields.php).
 *
 * @package BeyondWords\Editor\Components\SettingsFields
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Components\SettingsFields;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Fields asset enqueue.
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
	 * Enqueue the Settings Fields JS on classic-editor post screens.
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

		if ( ! in_array( get_post_type(), \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_register_script(
			'beyondwords-metabox--settings-fields',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/components/settings-fields/classic-metabox.js',
			[ 'jquery' ],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'beyondwords-metabox--settings-fields',
			'beyondwordsSettingsFields',
			[
				'embedLabels' => [
					'none'         => __( 'None', 'speechkit' ),
					'audio_post'   => __( 'Audio (post)', 'speechkit' ),
					'audio_script' => __( 'Audio (script)', 'speechkit' ),
					'video_post'   => __( 'Video (post)', 'speechkit' ),
					'video_script' => __( 'Video (script)', 'speechkit' ),
				],
			]
		);

		wp_enqueue_script( 'beyondwords-metabox--settings-fields' );
	}
}
