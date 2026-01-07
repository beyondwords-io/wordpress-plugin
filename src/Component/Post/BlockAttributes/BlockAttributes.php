<?php

declare(strict_types=1);

/**
 * BeyondWords support for Gutenberg blocks.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 * @since   4.0.0 Renamed from BlockAudioAttribute.php to BlockAttributes.php to support multiple attributes
 */

namespace Beyondwords\Wordpress\Component\Post\BlockAttributes;

/**
 * BlockAttributes
 *
 * @since 3.7.0
 * @since 4.0.0 Renamed from BlockAudioAttribute to BlockAttributes to support multiple attributes.
 * @since 6.0.0 Stop adding beyondwordsMarker attribute to blocks.
 */
defined('ABSPATH') || exit;

class BlockAttributes
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static and remove renderBlock registration.
     */
    public static function init()
    {
        add_filter('register_block_type_args', [self::class, 'registerAudioAttribute']);
        add_filter('register_block_type_args', [self::class, 'registerMarkerAttribute']);
    }

    /**
     * Register "Audio" attribute for Gutenberg blocks.
     *
     * @since 6.0.0 Make static.
     */
    public static function registerAudioAttribute($args)
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
     * @deprecated This attribute is no longer used as of 6.0.0, but kept for backward compatibility.
     *
     * @since 6.0.0 Make static.
     */
    public static function registerMarkerAttribute($args)
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
