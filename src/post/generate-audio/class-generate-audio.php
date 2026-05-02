<?php

declare( strict_types = 1 );

/**
 * BeyondWords Component: Generate audio
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * GenerateAudio
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class GenerateAudio
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
        add_action('wp_loaded', function (): void {
            $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

            if (is_array($post_types)) {
                foreach ($post_types as $post_type) {
                    add_action("save_post_{$post_type}", [self::class, 'save'], 10);
                }
            }
        });
    }

    /**
     * Check whether the post type should preselect the "Generate audio" checkbox.
     *
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param \WP_Post|int $post The post object or ID.
     *
     * @todo move this function to somewhere reusable for the Block editor.
     *
     * @since 6.0.0 Make static.
     */
    public static function should_preselect_generate_audio($post)
    {
        $post_type = get_post_type($post);

        if (! $post_type) {
            return false;
        }

        $preselect = get_option('beyondwords_preselect');

        if (! is_array($preselect)) {
            return false;
        }

        // Preselect if the post type in the settings has been checked (not the taxonomies)
        if (array_key_exists($post_type, $preselect) && $preselect[$post_type] === '1') {
            return true;
        }

        return false;
    }

    /**
     * Render the element.
     *
     * @since 6.0.0 Make static and refactor generate audio check.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function element($post)
    {
        wp_nonce_field('beyondwords_generate_audio', 'beyondwords_generate_audio_nonce');

        $generate_audio = PostMetaUtils::has_generate_audio($post->ID);
        ?>
        <!--  checkbox -->
        <p id="beyondwords-metabox-generate-audio">
            <input
                type="checkbox"
                id="beyondwords_generate_audio"
                name="beyondwords_generate_audio"
                value="1"
                <?php checked($generate_audio); ?>
            />
            <?php esc_html_e('Generate audio', 'speechkit'); ?>
        </p>
        <?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
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
            ! isset($_POST['beyondwords_generate_audio_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_generate_audio_nonce']),
                'beyondwords_generate_audio'
            )
        ) {
            return $post_id;
        }

        if (isset($_POST['beyondwords_generate_audio'])) {
            update_post_meta($post_id, 'beyondwords_generate_audio', '1');
        } else {
            delete_post_meta($post_id, 'speechkit_error_message');
            delete_post_meta($post_id, 'beyondwords_error_message');
            update_post_meta($post_id, 'beyondwords_generate_audio', '0');
        }

        return $post_id;
    }
}
