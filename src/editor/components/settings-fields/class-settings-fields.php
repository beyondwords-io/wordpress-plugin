<?php

declare( strict_types = 1 );

/**
 * BeyondWords Component: Settings Fields (Classic editor).
 *
 * The classic-editor counterparts of the block editor's Content, Format and
 * Player settings sections. Each section renders server-side <select> controls;
 * the dynamic show/hide + option-recompute behaviour lives in the bundled
 * [classic-metabox.js](classic-metabox.js), which mirrors the block editor's
 * [helpers.js](../settings-panel/helpers.js).
 *
 * The option-derivation helpers (source/output/embed) are kept as pure static
 * methods so they can be unit-tested and reused by both the renderers here and,
 * conceptually, the JS mirror.
 *
 * @package BeyondWords\Editor\Components
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   7.0.0
 */

namespace BeyondWords\Editor\Components;

/**
 * SettingsFields
 *
 * @since 7.0.0
 */
defined( 'ABSPATH' ) || exit;

class SettingsFields {

	public const SOURCE_POST            = 'post';
	public const SOURCE_SCRIPT          = 'script';
	public const SOURCE_POST_AND_SCRIPT = 'post_and_script';

	public const OUTPUT_AUDIO           = 'audio';
	public const OUTPUT_VIDEO           = 'video';
	public const OUTPUT_AUDIO_AND_VIDEO = 'audio_and_video';

	public const EMBED_NONE         = 'none';
	public const EMBED_AUDIO_POST   = 'audio_post';
	public const EMBED_AUDIO_SCRIPT = 'audio_script';
	public const EMBED_VIDEO_POST   = 'video_post';
	public const EMBED_VIDEO_SCRIPT = 'video_script';

	/**
	 * Init.
	 *
	 * @since 7.0.0
	 */
	public static function init() {
		add_action(
			'wp_loaded',
			function (): void {
				$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

				if ( is_array( $post_types ) ) {
					foreach ( $post_types as $post_type ) {
						add_action( "save_post_{$post_type}", [ self::class, 'save' ], 10 );
					}
				}
			}
		);
	}

	/**
	 * Render the nonce field shared by all Settings Fields sections.
	 *
	 * Called once by the metabox so a single nonce guards the combined
	 * Content/Format/Player save.
	 *
	 * @since 7.0.0
	 */
	public static function nonce(): void {
		wp_nonce_field( 'beyondwords_settings_fields', 'beyondwords_settings_fields_nonce' );
	}

	// Option helpers (mirror src/editor/components/settings-panel/helpers.js).

	/**
	 * The "Project default" leaf option. An empty value defers to the project
	 * setting — the plugin omits the field from the content payload when empty.
	 *
	 * @since 7.0.0
	 *
	 * @return array{label: string, value: string}
	 */
	public static function project_default_option(): array {
		return [
			'label' => __( 'Project default', 'speechkit' ),
			'value' => '',
		];
	}

	/**
	 * Source dropdown options.
	 *
	 * @since 7.0.0
	 *
	 * @return array<array{label: string, value: string}>
	 */
	public static function source_options(): array {
		return [
			[
				'label' => __( 'Post', 'speechkit' ),
				'value' => self::SOURCE_POST,
			],
			[
				'label' => __( 'Script', 'speechkit' ),
				'value' => self::SOURCE_SCRIPT,
			],
			[
				'label' => __( 'Post + script', 'speechkit' ),
				'value' => self::SOURCE_POST_AND_SCRIPT,
			],
		];
	}

	/**
	 * Output dropdown options.
	 *
	 * @since 7.0.0
	 *
	 * @return array<array{label: string, value: string}>
	 */
	public static function output_options(): array {
		return [
			[
				'label' => __( 'Audio', 'speechkit' ),
				'value' => self::OUTPUT_AUDIO,
			],
			[
				'label' => __( 'Video', 'speechkit' ),
				'value' => self::OUTPUT_VIDEO,
			],
			[
				'label' => __( 'Audio + video', 'speechkit' ),
				'value' => self::OUTPUT_AUDIO_AND_VIDEO,
			],
		];
	}

	/**
	 * Whether the source includes the post body.
	 *
	 * @since 7.0.0
	 *
	 * @param string $source One of the SOURCE_* constants.
	 */
	public static function source_includes_post( string $source ): bool {
		return in_array( $source, [ self::SOURCE_POST, self::SOURCE_POST_AND_SCRIPT ], true );
	}

	/**
	 * Whether the source includes a generated script.
	 *
	 * @since 7.0.0
	 *
	 * @param string $source One of the SOURCE_* constants.
	 */
	public static function source_includes_script( string $source ): bool {
		return in_array( $source, [ self::SOURCE_SCRIPT, self::SOURCE_POST_AND_SCRIPT ], true );
	}

	/**
	 * Whether the output includes audio.
	 *
	 * @since 7.0.0
	 *
	 * @param string $output One of the OUTPUT_* constants.
	 */
	public static function output_includes_audio( string $output ): bool {
		return in_array( $output, [ self::OUTPUT_AUDIO, self::OUTPUT_AUDIO_AND_VIDEO ], true );
	}

	/**
	 * Whether the output includes video.
	 *
	 * @since 7.0.0
	 *
	 * @param string $output One of the OUTPUT_* constants.
	 */
	public static function output_includes_video( string $output ): bool {
		return in_array( $output, [ self::OUTPUT_VIDEO, self::OUTPUT_AUDIO_AND_VIDEO ], true );
	}

	/**
	 * Derive the valid "Embed" dropdown options from the current Source × Output.
	 *
	 * Returns None plus one entry for each asset combination the current
	 * source/output would produce.
	 *
	 * @since 7.0.0
	 *
	 * @param string $source One of the SOURCE_* constants.
	 * @param string $output One of the OUTPUT_* constants.
	 *
	 * @return array<array{label: string, value: string}>
	 */
	public static function embed_options( string $source, string $output ): array {
		$options = [
			[
				'label' => __( 'None', 'speechkit' ),
				'value' => self::EMBED_NONE,
			],
		];

		if ( self::output_includes_audio( $output ) ) {
			if ( self::source_includes_post( $source ) ) {
				$options[] = [
					'label' => __( 'Audio (post)', 'speechkit' ),
					'value' => self::EMBED_AUDIO_POST,
				];
			}
			if ( self::source_includes_script( $source ) ) {
				$options[] = [
					'label' => __( 'Audio (script)', 'speechkit' ),
					'value' => self::EMBED_AUDIO_SCRIPT,
				];
			}
		}

		if ( self::output_includes_video( $output ) ) {
			if ( self::source_includes_post( $source ) ) {
				$options[] = [
					'label' => __( 'Video (post)', 'speechkit' ),
					'value' => self::EMBED_VIDEO_POST,
				];
			}
			if ( self::source_includes_script( $source ) ) {
				$options[] = [
					'label' => __( 'Video (script)', 'speechkit' ),
					'value' => self::EMBED_VIDEO_SCRIPT,
				];
			}
		}

		return $options;
	}

	/**
	 * Whether the given embed value is selectable for the current Source × Output.
	 *
	 * @since 7.0.0
	 *
	 * @param string $embed  One of the EMBED_* constants.
	 * @param string $source One of the SOURCE_* constants.
	 * @param string $output One of the OUTPUT_* constants.
	 */
	public static function is_embed_valid( string $embed, string $source, string $output ): bool {
		foreach ( self::embed_options( $source, $output ) as $option ) {
			if ( $option['value'] === $embed ) {
				return true;
			}
		}

		return false;
	}

	// Renderers.

	/**
	 * Render the Content section fields: Source + Script template.
	 *
	 * @since 7.0.0
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function render_content_section( $post ): void {
		$source             = self::get_meta( $post->ID, 'beyondwords_source', self::SOURCE_POST );
		$script_template_id = self::get_meta( $post->ID, 'beyondwords_script_template_id', '' );

		$templates = \BeyondWords\Api\Client::get_summarization_settings_templates();
		$templates = is_array( $templates ) ? $templates : [];

		self::render_select(
			'beyondwords_source',
			__( 'Source', 'speechkit' ),
			self::source_options(),
			$source
		);

		self::render_select(
			'beyondwords_script_template_id',
			__( 'Script template', 'speechkit' ),
			array_merge(
				[ self::project_default_option() ],
				self::templates_to_options( $templates )
			),
			$script_template_id,
			! self::source_includes_script( $source )
		);
	}

	/**
	 * Render the Format section fields: Output + Video template + Video size.
	 *
	 * @since 7.0.0
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function render_format_section( $post ): void {
		$output            = self::get_meta( $post->ID, 'beyondwords_output', self::OUTPUT_AUDIO );
		$video_template_id = self::get_meta( $post->ID, 'beyondwords_video_template_id', '' );
		$video_size        = self::get_meta( $post->ID, 'beyondwords_video_size', '' );

		$templates = \BeyondWords\Api\Client::get_video_settings_templates();
		$templates = is_array( $templates ) ? $templates : [];

		$video_settings = \BeyondWords\Api\Client::get_video_settings();
		$sizes          = is_array( $video_settings ) && isset( $video_settings['sizes'] ) && is_array( $video_settings['sizes'] )
			? $video_settings['sizes']
			: [];

		$hide_video = ! self::output_includes_video( $output );

		self::render_select(
			'beyondwords_output',
			__( 'Output', 'speechkit' ),
			self::output_options(),
			$output
		);

		self::render_select(
			'beyondwords_video_template_id',
			__( 'Video template', 'speechkit' ),
			array_merge(
				[ self::project_default_option() ],
				self::templates_to_options( $templates )
			),
			$video_template_id,
			$hide_video
		);

		self::render_select(
			'beyondwords_video_size',
			__( 'Video size', 'speechkit' ),
			array_merge(
				[ self::project_default_option() ],
				self::sizes_to_options( $sizes )
			),
			$video_size,
			$hide_video
		);
	}

	/**
	 * Render the Player section fields: Embed.
	 *
	 * @since 7.0.0
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function render_player_section( $post ): void {
		$source = self::get_meta( $post->ID, 'beyondwords_source', self::SOURCE_POST );
		$output = self::get_meta( $post->ID, 'beyondwords_output', self::OUTPUT_AUDIO );
		$embed  = self::get_meta( $post->ID, 'beyondwords_embed', self::EMBED_NONE );

		// Fall back to None if the persisted value no longer fits Source × Output.
		if ( ! self::is_embed_valid( $embed, $source, $output ) ) {
			$embed = self::EMBED_NONE;
		}

		self::render_select(
			'beyondwords_embed',
			__( 'Embed', 'speechkit' ),
			self::embed_options( $source, $output ),
			$embed,
			false,
			__(
				'Pick which generated asset is shown on this post. All other generated assets stay available in BeyondWords.', // phpcs:ignore Generic.Files.LineLength.TooLong
				'speechkit'
			)
		);
	}

	/**
	 * Convert a list of API templates to <select> options.
	 *
	 * @since 7.0.0
	 *
	 * @param array<array<string, mixed>> $templates API templates.
	 *
	 * @return array<array{label: string, value: string}>
	 */
	private static function templates_to_options( array $templates ): array {
		$options = [];

		foreach ( $templates as $template ) {
			if ( ! isset( $template['id'] ) ) {
				continue;
			}

			$options[] = [
				'label' => (string) ( $template['name'] ?? $template['slug'] ?? '' ),
				'value' => (string) $template['id'],
			];
		}

		return $options;
	}

	/**
	 * Convert a list of API video sizes to <select> options.
	 *
	 * @since 7.0.0
	 *
	 * @param array<array<string, mixed>> $sizes API video sizes.
	 *
	 * @return array<array{label: string, value: string}>
	 */
	private static function sizes_to_options( array $sizes ): array {
		$options = [];

		foreach ( $sizes as $size ) {
			if ( ! isset( $size['name'] ) || ( isset( $size['enabled'] ) && false === $size['enabled'] ) ) {
				continue;
			}

			$label = ! empty( $size['description'] )
				? sprintf( '%s (%s)', $size['name'], $size['description'] )
				: (string) $size['name'];

			$options[] = [
				'label' => $label,
				'value' => (string) $size['name'],
			];
		}

		return $options;
	}

	/**
	 * Render a labelled <select> control in the classic-metabox style.
	 *
	 * @since 7.0.0
	 *
	 * @param string                                     $id       Field id/name.
	 * @param string                                     $label    Field label.
	 * @param array<array{label: string, value: string}> $options Select options.
	 * @param string                                     $selected Selected value.
	 * @param bool                                       $hidden   Whether the field starts hidden.
	 * @param string                                     $help     Optional help text.
	 */
	private static function render_select(
		string $id,
		string $label,
		array $options,
		string $selected,
		bool $hidden = false,
		string $help = ''
	): void {
		$wrapper_id = 'beyondwords-metabox-settings--' . str_replace( '_', '-', $id );
		?>
		<div
			id="<?php echo esc_attr( $wrapper_id ); ?>"
			class="beyondwords-metabox-settings__field"
			<?php echo $hidden ? 'style="display: none;"' : ''; ?>
		>
			<p class="post-attributes-label-wrapper page-template-label-wrapper">
				<label class="post-attributes-label" for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
			</p>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" style="width: 100%;">
				<?php
				foreach ( $options as $option ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $option['value'] ),
						selected( strval( $option['value'] ), strval( $selected ), false ),
						esc_html( $option['label'] )
					);
				}
				?>
			</select>
			<?php if ( $help ) : ?>
				<p class="description" style="margin-top: 4px;"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Read a post-meta value, falling back to a default when empty.
	 *
	 * @since 7.0.0
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $key      The meta key.
	 * @param string $fallback The default value.
	 */
	private static function get_meta( int $post_id, string $key, string $fallback ): string {
		$value = get_post_meta( $post_id, $key, true );

		return ( '' === $value || null === $value || false === $value ) ? $fallback : (string) $value;
	}

	/**
	 * Save the Content/Format/Player meta when the post is saved.
	 *
	 * @since 7.0.0
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @return int
	 */
	public static function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if (
			! isset( $_POST['beyondwords_settings_fields_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_key( $_POST['beyondwords_settings_fields_nonce'] ),
				'beyondwords_settings_fields'
			)
		) {
			return $post_id;
		}

		$keys = [
			'beyondwords_source',
			'beyondwords_script_template_id',
			'beyondwords_output',
			'beyondwords_video_template_id',
			'beyondwords_video_size',
			'beyondwords_embed',
		];

		foreach ( $keys as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, $value );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}

		return $post_id;
	}
}
