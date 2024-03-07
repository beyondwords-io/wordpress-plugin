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

use Beyondwords\Wordpress\Component\Post\PostContentUtils;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\PlayerUI\PlayerUI;

/**
 * BlockAttributes setup
 *
 * @since 3.7.0
 * @since 4.0.0 Renamed from BlockAudioAttribute to BlockAttributes to support multiple attributes
 */
class BlockAttributes
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_filter('register_block_type_args', array($this, 'registerAudioAttribute'));
        add_filter('register_block_type_args', array($this, 'registerMarkerAttribute'));
        add_filter('render_block', array($this, 'renderBlock'), 10, 2);
    }

    /**
     * Register "Audio" attribute for Gutenberg blocks.
     */
    public function registerAudioAttribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = array();
        }

        if (! array_key_exists('beyondwordsAudio', $args['attributes'])) {
            $args['attributes']['beyondwordsAudio'] = array(
                'type' => 'boolean',
                'default' => true,
            );
        }

        return $args;
    }

    /**
     * Register "Segment marker" attribute for Gutenberg blocks.
     */
    public function registerMarkerAttribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = array();
        }

        if (! array_key_exists('beyondwordsMarker', $args['attributes'])) {
            $args['attributes']['beyondwordsMarker'] = array(
                'type' => 'string',
                'default' => '',
            );
        }

        return $args;
    }

    /**
     * Render block as HTML.
     *
     * Performs some checks and then attempts to add data-beyondwords-marker
     * attribute to the root element of Gutenberg blocks.
     *
     * @since 4.0.0
     * @since 4.2.2 Rename method to renderBlock.
     *
     * @param string $blockContent The block content (HTML).
     * @param string $block        The full block, including name and attributes.
     *
     * @return string Block Content (HTML).
     */
    public function renderBlock($blockContent, $block)
    {
        // Skip adding marker if player UI is disabled
        if (get_option('beyondwords_player_ui', PlayerUI::ENABLED) === PlayerUI::DISABLED) {
            return $blockContent;
        }

        // Skip adding marker if no content ID exists
        if (! PostMetaUtils::getContentId(get_the_ID())) {
            return $blockContent;
        }

        $marker = $block['attrs']['beyondwordsMarker'] ?? '';

        return PostContentUtils::addMarkerAttribute(
            $blockContent,
            $marker
        );
    }
}
