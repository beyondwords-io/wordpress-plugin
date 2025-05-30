<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Player content
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.3.0
 */

namespace Beyondwords\Wordpress\Component\Post\PlayerContent;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerContent
 *
 * @since 5.3.0
 */
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
     */
    public function init()
    {
        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_action("save_post_{$postType}", array($this, 'save'), 10);
                }
            }
        });
    }

    /**
     * HTML output for this component.
     *
     * @since 5.3.0 Introduced.
     *
     * @param WP_Post $post The post object.
     *
     * @return string|null
     */
    public function element($post)
    {
        $playerContent = get_post_meta($post->ID, 'beyondwords_player_content', true);

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
                    selected(strval($option['value']), $playerContent),
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
     *
     * @param int $postId The ID of the post being saved.
     */
    public function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (! isset($_POST['beyondwords_player_content']) || ! isset($_POST['beyondwords_player_content_nonce'])) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_player_content_nonce']),
                'beyondwords_player_content'
            )
        ) {
            return $postId;
        }

        $playerContent = sanitize_text_field(wp_unslash($_POST['beyondwords_player_content']));

        if (! empty($playerContent)) {
            update_post_meta($postId, 'beyondwords_player_content', $playerContent);
        } else {
            delete_post_meta($postId, 'beyondwords_player_content');
        }

        return $postId;
    }
}
