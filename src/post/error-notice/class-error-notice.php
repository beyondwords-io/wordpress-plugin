<?php

declare(strict_types=1);

/**
 * BeyondWords Post ErrorNotice.
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * ErrorNotice
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined('ABSPATH') || exit;

class ErrorNotice
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('enqueue_block_assets', [self::class, 'enqueue_block_assets']);
    }

    /**
     * Enqueue Block Editor assets.
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function enqueue_block_assets()
    {
        // Only enqueue for Gutenberg screens
        if (\BeyondWords\Core\CoreUtils::is_gutenberg_page()) {
            // Register the Block Editor "Error Notice" CSS
            wp_enqueue_style(
                'beyondwords-ErrorNotice',
                BEYONDWORDS__PLUGIN_URI . 'src/post/error-notice/error-notice.css',
                [],
                BEYONDWORDS__PLUGIN_VERSION
            );
        }
    }
}
