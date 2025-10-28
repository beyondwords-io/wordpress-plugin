<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Generate audio
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\GenerateAudio;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * GenerateAudio
 *
 * @since 3.0.0
 */
class GenerateAudio
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
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_action("save_post_{$postType}", [self::class, 'save'], 10);
                }
            }
        });
    }

    /**
     * Check whether the post type should preselect the "Generate audio" checkbox.
     *
     * @param \WP_Post|int $post The post object or ID.
     *
     * @todo move this function to somewhere reusable for the Block editor.
     *
     * @since 6.0.0 Make static.
     */
    public static function shouldPreselectGenerateAudio($post)
    {
        $postType = get_post_type($post);

        if (! $postType) {
            return false;
        }

        $preselect = get_option('beyondwords_preselect');

        if (! is_array($preselect)) {
            return false;
        }

        // Preselect if the post type in the settings has been checked (not the taxonomies)
        if (array_key_exists($postType, $preselect) && $preselect[$postType] === '1') {
            return true;
        }

        return false;
    }

    /**
     * Render the element.
     *
     * @since 6.0.0 Make static and refactor generate audio check.
     */
    public static function element($post)
    {
        wp_nonce_field('beyondwords_generate_audio', 'beyondwords_generate_audio_nonce');

        $generateAudio = PostMetaUtils::hasGenerateAudio($post->ID);
        ?>
        <!--  checkbox -->
        <p id="beyondwords-metabox-generate-audio">
            <input
                type="checkbox"
                id="beyondwords_generate_audio"
                name="beyondwords_generate_audio"
                value="1"
                <?php checked($generateAudio); ?>
            />
            <?php esc_html_e('Generate audio', 'speechkit'); ?>
        </p>
        <?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 6.0.0 Make static.
     *
     * @param int $postId The ID of the post being saved.
     */
    public static function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! isset($_POST['beyondwords_generate_audio_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_generate_audio_nonce']),
                'beyondwords_generate_audio'
            )
        ) {
            return $postId;
        }

        if (isset($_POST['beyondwords_generate_audio'])) {
            update_post_meta($postId, 'beyondwords_generate_audio', '1');
        } else {
            delete_post_meta($postId, 'speechkit_error_message');
            delete_post_meta($postId, 'beyondwords_error_message');
            update_post_meta($postId, 'beyondwords_generate_audio', '0');
        }

        return $postId;
    }
}
