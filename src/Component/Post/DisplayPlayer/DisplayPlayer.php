<?php

declare(strict_types=1);

/**
 * BeyondWords Display Player element.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\DisplayPlayer;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PostMetabox
 *
 * @since 3.0.0
 */
class DisplayPlayer
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_action("save_post_{$postType}", array($this, 'save'), 20);
                }
            }
        });
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $postId The ID of the post being saved.
     */
    public function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! isset($_POST['beyondwords_display_player_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_display_player_nonce']),
                'beyondwords_display_player'
            )
        ) {
            return $postId;
        }

        if (isset($_POST['beyondwords_display_player'])) {
            update_post_meta($postId, 'beyondwords_disabled', '');
        } else {
            update_post_meta($postId, 'beyondwords_disabled', '1');
        }

        return $postId;
    }

    /**
     * Render the element.
     *
     * @param WP_Post $post The post object.
     */
    public function element($post)
    {
        if (!($post instanceof \WP_Post)) {
            return;
        }

        wp_nonce_field('beyondwords_display_player', 'beyondwords_display_player_nonce');

        $displayPlayer = PostMetaUtils::getDisabled($post->ID) !== '1';
        ?>
        <!--  checkbox -->
        <p id="beyondwords-metabox-display-player">
            <input
                type="checkbox"
                id="beyondwords_display_player"
                name="beyondwords_display_player"
                value="1"
                <?php checked($displayPlayer); ?>
            />
            <?php esc_html_e('Display player', 'speechkit'); ?>
        </p>
        <?php
    }
}
