<?php

declare( strict_types = 1 );

/**
 * BeyondWords support for Gutenberg blocks.
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 * @since   4.0.0 Renamed from BlockAudioAttribute.php to BlockAttributes.php to support multiple attributes
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * BlockAttributes
 *
 * @since 3.7.0
 * @since 4.0.0 Renamed from BlockAudioAttribute to BlockAttributes to support multiple attributes.
 * @since 6.0.0 Stop adding beyondwordsMarker attribute to blocks.
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class BlockAttributes
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static and remove renderBlock registration.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_filter('register_block_type_args', [self::class, 'register_audio_attribute']);
        add_filter('register_block_type_args', [self::class, 'register_marker_attribute']);
    }

    /**
     * Register "Audio" attribute for Gutenberg blocks.
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function register_audio_attribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = [];
        }

        if (! array_key_exists('beyondwordsAudio', $args['attributes'])) {
            $args['attributes']['beyondwordsAudio'] = [
                'type' => 'boolean',
                'default' => true,
            ];
        }

        return $args;
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
    public static function register_marker_attribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = [];
        }

        if (! array_key_exists('beyondwordsMarker', $args['attributes'])) {
            $args['attributes']['beyondwordsMarker'] = [
                'type' => 'string',
                'default' => '',
            ];
        }

        return $args;
    }
}
