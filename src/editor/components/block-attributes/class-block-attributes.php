<?php

declare( strict_types = 1 );

/**
 * BeyondWords support for Gutenberg blocks.
 *
 * @package BeyondWords\Editor\Components
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 * @since   4.0.0 Renamed from BlockAudioAttribute.php to BlockAttributes.php to support multiple attributes
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Editor\Components;

/**
 * BlockAttributes
 *
 * @since 3.7.0
 * @since 4.0.0 Renamed from BlockAudioAttribute to BlockAttributes to support multiple attributes.
 * @since 6.0.0 Stop adding beyondwordsMarker attribute to blocks.
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class BlockAttributes {

	/**
	 * Block attribute holding the per-block language code (e.g. `en_GB`).
	 *
	 * @since 7.0.0
	 */
	public const LANGUAGE_ATTRIBUTE = 'beyondwordsLanguageCode';

	/**
	 * Block attribute holding the per-block voice id, which also carries the model.
	 *
	 * @since 7.0.0
	 */
	public const VOICE_ATTRIBUTE = 'beyondwordsVoiceId';

	/**
	 * Init.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static and remove renderBlock registration.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function init() {
		add_filter( 'register_block_type_args', [ self::class, 'register_audio_attribute'] );
		add_filter( 'register_block_type_args', [ self::class, 'register_marker_attribute'] );
		add_filter( 'register_block_type_args', [ self::class, 'register_language_attribute' ] );
		add_filter( 'register_block_type_args', [ self::class, 'register_voice_attribute' ] );
	}

	/**
	 * Register "Audio" attribute for Gutenberg blocks.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function register_audio_attribute( $args ) {
		return self::register_attribute(
			$args,
			'beyondwordsAudio',
			[
				'type'    => 'boolean',
				'default' => true,
			]
		);
	}

	/**
	 * Register "Segment marker" attribute for Gutenberg blocks.
	 *
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @deprecated This attribute is no longer used as of 6.0.0, but kept for backward compatibility.
	 *
	 * @since 6.0.0 Make static.
	 */
	public static function register_marker_attribute( $args ) {
		return self::register_attribute(
			$args,
			'beyondwordsMarker',
			[
				'type'    => 'string',
				'default' => '',
			]
		);
	}

	/**
	 * Register the per-block "Language" attribute for Gutenberg blocks.
	 *
	 * @since 7.0.0
	 */
	public static function register_language_attribute( $args ) {
		return self::register_attribute(
			$args,
			self::LANGUAGE_ATTRIBUTE,
			[
				'type'    => 'string',
				'default' => '',
			]
		);
	}

	/**
	 * Register the per-block "Voice" attribute for Gutenberg blocks.
	 *
	 * @since 7.0.0
	 */
	public static function register_voice_attribute( $args ) {
		return self::register_attribute(
			$args,
			self::VOICE_ATTRIBUTE,
			[
				'type'    => 'string',
				'default' => '',
			]
		);
	}

	/**
	 * Add a block attribute, leaving an existing definition of the same name alone.
	 *
	 * @since 7.0.0
	 *
	 * @param mixed $args The `register_block_type_args` args, which may be null.
	 */
	private static function register_attribute( $args, string $name, array $schema ): array {
		if ( ! isset( $args['attributes'] ) ) {
			$args['attributes'] = [];
		}

		if ( ! array_key_exists( $name, $args['attributes'] ) ) {
			$args['attributes'][ $name ] = $schema;
		}

		return $args;
	}

	/**
	 * Add the segment-scoped voice data attributes to a rendered block.
	 *
	 * Not registered in init(): it is added around the API body build only, so
	 * front-end output is untouched. See doc/block-level-voices.md.
	 *
	 * @since 7.0.0
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The parsed block.
	 */
	public static function add_segment_attributes( $block_content, $block ): string {
		$block_content = (string) $block_content;

		$attrs = ( is_array( $block ) && is_array( $block['attrs'] ?? null ) ) ? $block['attrs'] : [];

		$language = self::attribute_value( $attrs, self::LANGUAGE_ATTRIBUTE );
		$voice_id = self::attribute_value( $attrs, self::VOICE_ATTRIBUTE );

		if ( '' === $language && '' === $voice_id ) {
			return $block_content;
		}

		$processor = new \WP_HTML_Tag_Processor( $block_content );

		// Tagless output (an empty block, or a shortcode block) has nothing to carry them.
		if ( ! $processor->next_tag() ) {
			return $block_content;
		}

		if ( '' !== $language ) {
			$processor->set_attribute( 'data-beyondwords-language', $language );
		}

		if ( '' !== $voice_id ) {
			$processor->set_attribute( 'data-beyondwords-voice-id', $voice_id );
		}

		return $processor->get_updated_html();
	}

	/**
	 * A block attribute as a trimmed string.
	 *
	 * @since 7.0.0
	 *
	 * @return string The value, or '' when unset or non-scalar.
	 */
	private static function attribute_value( array $attrs, string $name ): string {
		$value = $attrs[ $name ] ?? '';

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
