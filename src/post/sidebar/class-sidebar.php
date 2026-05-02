<?php

declare(strict_types=1);

/**
 * BeyondWords Post Inspect Panel.
 *
 * Text Domain: beyondwords
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * Sidebar
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined('ABSPATH') || exit;

class Sidebar
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
        if (\BeyondWords\Core\CoreUtils::is_gutenberg_page()) {
            $post_type = get_post_type();

            $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

            if (in_array($post_type, $post_types)) {
                // Register the Block Editor "Sidebar" CSS
                wp_enqueue_style(
                    'beyondwords-Sidebar',
                    BEYONDWORDS__PLUGIN_URI . 'src/post/sidebar/PostSidebar.css',
                    [],
                    BEYONDWORDS__PLUGIN_VERSION
                );
            }
        }
    }
}
