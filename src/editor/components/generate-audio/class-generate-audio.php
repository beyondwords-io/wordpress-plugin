<?php

declare( strict_types = 1 );

/**
 * BeyondWords Component: Generate audio
 *
 * @package BeyondWords\Editor\Components
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Editor\Components;

/**
 * GenerateAudio
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class GenerateAudio {

	/**
	 * Init.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function init() {
		add_action(
			'wp_loaded',
			function (): void {
				$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

				if ( is_array( $post_types ) ) {
					foreach ( $post_types as $post_type ) {
						add_action( "save_post_{$post_type}", [ self::class, 'save'], 10 );
					}
				}
			}
		);

		add_action( 'admin_enqueue_scripts', [ self::class, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Enqueue the classic-editor Generate audio script.
	 *
	 * Always enqueued (the caption sync needs it); `terms` mode additionally
	 * localizes the term data used to live-update the checkbox.
	 *
	 * @since 7.0.0
	 * @since 7.0.0 Always enqueued (for the caption); preselect data is optional.
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

		$post_type = get_post_type();

		if ( ! in_array( $post_type, \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_register_script(
			'beyondwords-metabox--generate-audio',
			BEYONDWORDS__PLUGIN_URI . 'src/editor/components/generate-audio/classic-metabox.js',
			[],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);

		if ( \BeyondWords\Settings\Preselect::MODE_TERMS === \BeyondWords\Settings\Preselect::get_mode( $post_type ) ) {
			$terms = \BeyondWords\Settings\Preselect::get_selected_terms( $post_type );

			if ( ! empty( $terms ) ) {
				wp_localize_script(
					'beyondwords-metabox--generate-audio',
					'beyondwordsPreselect',
					[
						'mode'  => \BeyondWords\Settings\Preselect::MODE_TERMS,
						'terms' => $terms,
					]
				);
			}
		}

		wp_enqueue_script( 'beyondwords-metabox--generate-audio' );
	}

	/**
	 * Check whether the "Generate audio" checkbox should be preselected.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 * @since 7.0.0 Delegated to Preselect; term-gating reinstated.
	 *
	 * @param \WP_Post|int $post The post object or ID.
	 */
	public static function should_preselect_generate_audio( $post ) {
		return \BeyondWords\Settings\Preselect::should_preselect_for_post( $post );
	}

	/**
	 * Render the element.
	 *
	 * @since 6.0.0 Make static and refactor generate audio check.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function element( $post ) {
		wp_nonce_field( 'beyondwords_generate_audio', 'beyondwords_generate_audio_nonce' );

		$generate_audio = \BeyondWords\Post\Meta::has_generate_audio( $post->ID );

		// State-reflecting caption; the data attributes let classic-metabox.js keep
		// it in step as the checkbox toggles (mirrors the block editor toggle).
		$label_enabled  = __( 'Generation enabled', 'speechkit' );
		$label_disabled = __( 'Generation disabled', 'speechkit' );
		?>
		<!--  checkbox -->
		<p id="beyondwords-metabox-generate-audio">
			<input
				type="checkbox"
				id="beyondwords_generate_audio"
				name="beyondwords_generate_audio"
				value="1"
				<?php checked( $generate_audio ); ?>
			/>
			<label for="beyondwords_generate_audio">
				<span
					id="beyondwords-generate-audio-label"
					data-label-enabled="<?php echo esc_attr( $label_enabled ); ?>"
					data-label-disabled="<?php echo esc_attr( $label_disabled ); ?>"
				><?php echo esc_html( $generate_audio ? $label_enabled : $label_disabled ); ?></span>
			</label>
		</p>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if (
			! isset( $_POST['beyondwords_generate_audio_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_key( $_POST['beyondwords_generate_audio_nonce'] ),
				'beyondwords_generate_audio'
			)
		) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( isset( $_POST['beyondwords_generate_audio'] ) ) {
			update_post_meta( $post_id, 'beyondwords_generate_audio', '1' );
		} else {
			delete_post_meta( $post_id, 'speechkit_error_message' );
			delete_post_meta( $post_id, 'beyondwords_error_message' );
			update_post_meta( $post_id, 'beyondwords_generate_audio', '0' );
		}

		return $post_id;
	}
}
