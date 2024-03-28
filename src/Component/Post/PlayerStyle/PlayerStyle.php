<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Player style
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.1.0
 */

namespace Beyondwords\Wordpress\Component\Post\PlayerStyle;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\PlayerStyle\PlayerStyle as PlayerStyleSetting;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerStyle setup
 *
 * @since 4.1.0
 */
class PlayerStyle
{
    /**
     * Constructor
     */
    public function init()
    {
        add_action('rest_api_init', array($this, 'restApiInit'));

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
     * @since 4.1.0
     * @since 4.5.1 Hide element if no language data exists.
     *
     * @param WP_Post $post The post object.
     *
     * @return string|null
     */
    public function element($post)
    {
        $projectId = PostMetaUtils::getProjectId($post->ID);

        $playerStyle = PostMetaUtils::getPlayerStyle($post->ID);
        $allPlayerStyles = PlayerStyleSetting::getCachedPlayerStyles($projectId);

        if (! is_array($allPlayerStyles) || ! count($allPlayerStyles)) {
            return;
        }

        wp_nonce_field('beyondwords_player_style', 'beyondwords_player_style_nonce');
        ?>
        <p
            id="beyondwords-metabox-player-style"
            class="post-attributes-label-wrapper page-template-label-wrapper"
        >
            <label class="post-attributes-label" for="beyondwords_player_style">
                <?php esc_html_e('Player style', 'speechkit'); ?>
            </label>
        </p>
        <select id="beyondwords_player_style" name="beyondwords_player_style" style="width: 100%;">
            <?php
            foreach ($allPlayerStyles as $item) {
                printf(
                    '<option value="%s" %s %s>%s</option>',
                    esc_attr($item['value']),
                    selected(strval($item['value']), $playerStyle),
                    disabled($item['disabled'] ?? false, true),
                    esc_html($item['label'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 4.1.0
     *
     * @param int $postId The ID of the post being saved.
     */
    public function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (! isset($_POST['beyondwords_player_style']) || ! isset($_POST['beyondwords_player_style_nonce'])) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_text_field($_POST['beyondwords_player_style_nonce']),
                'beyondwords_player_style'
            )
        ) {
            return $postId;
        }

        $playerStyle = sanitize_text_field($_POST['beyondwords_player_style']);

        if (! empty($playerStyle)) {
            update_post_meta($postId, 'beyondwords_player_style', $playerStyle);
        } else {
            delete_post_meta($postId, 'beyondwords_player_style');
        }

        return $postId;
    }

    /**
     * Register WP REST API route
     *
     * @since 4.1.0
     *
     * @return void
     */
    public function restApiInit()
    {
        // Player styles endpoint
        register_rest_route('beyondwords/v1', '/projects/(?P<projectId>[0-9]+)/player-styles', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'playerStylesRestApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * "Player styles" WP REST API response (required for the Gutenberg editor).
     *
     * @since 4.1.0
     *
     * @return \WP_REST_Response
     */
    public function playerStylesRestApiResponse(\WP_REST_Request $data)
    {
        $params = $data->get_url_params();

        $response = PlayerStyleSetting::getCachedPlayerStyles($params['projectId']);

        // Convert from object to array so we can use find() in Block Editor JS.
        $response = array_values($response);

        return new \WP_REST_Response($response);
    }
}
