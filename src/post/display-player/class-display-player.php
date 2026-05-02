<?php

declare(strict_types=1);

/**
 * BeyondWords Display Player element.
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace BeyondWords\Post;

/**
 * PostMetabox
 *
 * @since 3.0.0
 */
defined('ABSPATH') || exit;

class DisplayPlayer
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('wp_loaded', function (): void {
            $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

            if (is_array($post_types)) {
                foreach ($post_types as $post_type) {
                    add_action("save_post_{$post_type}", [self::class, 'save'], 20);
                }
            }
        });
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 6.0.0 Make static.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public static function save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! isset($_POST['beyondwords_display_player_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_display_player_nonce']),
                'beyondwords_display_player'
            )
        ) {
            return $post_id;
        }

        if (isset($_POST['beyondwords_display_player'])) {
            update_post_meta($post_id, 'beyondwords_disabled', '');
        } else {
            update_post_meta($post_id, 'beyondwords_disabled', '1');
        }

        return $post_id;
    }

    /**
     * Render the element.
     *
     * @since 6.0.0 Make static, fix checkbox checked bug.
     *
     * @param \WP_Post $post The post object.
     */
    public static function element($post)
    {
        if (!($post instanceof \WP_Post)) {
            return;
        }

        wp_nonce_field('beyondwords_display_player', 'beyondwords_display_player_nonce');

        $display_player = ! PostMetaUtils::get_disabled($post->ID);
        ?>
        <!--  checkbox -->
        <p id="beyondwords-metabox-display-player">
            <input
                type="checkbox"
                id="beyondwords_display_player"
                name="beyondwords_display_player"
                value="1"
                <?php checked($display_player); ?>
            />
            <?php esc_html_e('Display player', 'speechkit'); ?>
        </p>
        <?php
    }
}
