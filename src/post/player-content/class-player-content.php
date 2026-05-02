<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Player content
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.3.0
 */

namespace BeyondWords\Post;

/**
 * PlayerContent
 *
 * @since 5.3.0
 */
defined('ABSPATH') || exit;

class PlayerContent
{
    /**
     * Options.
     *
     * @since 5.3.0 Introduced.
     *
     * @var array Associative array of player content values and labels.
     */
    public const OPTIONS = [
        [
            'value' => '',
            'label' => 'Article'
        ],
        [
            'value' => 'summary',
            'label' => 'Summary'
        ],
    ];

    /**
     * Constructor
     *
     * @since 5.3.0 Introduced.
     * @since 6.0.0 Make static.
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
     * HTML output for this component.
     *
     * @since 5.3.0 Introduced.
     * @since 6.0.0 Make static.
     *
     * @param \WP_Post $post The post object.
     *
     * @return string|null
     */
    public static function element($post)
    {
        $player_content = get_post_meta($post->ID, 'beyondwords_player_content', true);

        wp_nonce_field('beyondwords_player_content', 'beyondwords_player_content_nonce');
        ?>
        <p
            id="beyondwords-metabox-player-content"
            class="post-attributes-label-wrapper page-template-label-wrapper"
        >
            <label class="post-attributes-label" for="beyondwords_player_content">
                <?php esc_html_e('Player content', 'speechkit'); ?>
            </label>
        </p>
        <select id="beyondwords_player_content" name="beyondwords_player_content" style="width: 100%;">
            <?php
            foreach (self::OPTIONS as $option) {
                printf(
                    '<option value="%s" %s %s>%s</option>',
                    esc_attr($option['value']),
                    selected(strval($option['value']), $player_content),
                    disabled($option['disabled'] ?? false, true),
                    esc_html($option['label'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 5.3.0 Introduced.
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
        if (! isset($_POST['beyondwords_player_content']) || ! isset($_POST['beyondwords_player_content_nonce'])) {
            return $post_id;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_player_content_nonce']),
                'beyondwords_player_content'
            )
        ) {
            return $post_id;
        }

        $player_content = sanitize_text_field(wp_unslash($_POST['beyondwords_player_content']));

        if (! empty($player_content)) {
            update_post_meta($post_id, 'beyondwords_player_content', $player_content);
        } else {
            delete_post_meta($post_id, 'beyondwords_player_content');
        }

        return $post_id;
    }
}
