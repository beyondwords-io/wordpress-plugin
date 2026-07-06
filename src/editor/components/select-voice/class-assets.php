<?php
/**
 * BeyondWords Select Voice — asset enqueue.
 *
 * Owns the classic-editor JS that wires up the language/voice select boxes.
 * Split from [class-select-voice.php](class-select-voice.php).
 *
 * @package BeyondWords\Editor\Components\SelectVoice
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Editor\Components\SelectVoice;

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
			'beyondwords-metabox--select-voice',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/components/select-voice/classic-metabox.js',
			[],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'beyondwords-metabox--select-voice',
			'beyondwordsData',
			[
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'root'             => esc_url_raw( rest_url() ),
				'projectId'        => (string) get_option( 'beyondwords_project_id', '' ),
				'selectVoice'      => __( 'Select a voice', 'speechkit' ),
				'selectModel'      => __( 'Select a model', 'speechkit' ),
				'standardModel'    => __( 'Legacy', 'speechkit' ),
				'elevenLabs'       => \BeyondWords\Editor\Components\SelectVoice::ELEVENLABS_SERVICE,
				'defaultModelId'   => \BeyondWords\Editor\Components\SelectVoice::DEFAULT_ELEVENLABS_VOICE_MODEL_ID,
				'voiceModelLabels' => [
					'eleven_v3'              => __( 'v3', 'speechkit' ),
					'eleven_multilingual_v2' => __( 'Multilingual v2', 'speechkit' ),
					'eleven_flash_v2_5'      => __( 'Flash v2.5', 'speechkit' ),
					'eleven_turbo_v2_5'      => __( 'Turbo v2.5', 'speechkit' ),
				],
			]
		);

		wp_enqueue_script( 'beyondwords-metabox--select-voice' );
	}
}
