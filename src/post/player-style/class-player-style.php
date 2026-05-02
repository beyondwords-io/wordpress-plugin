<?php

declare( strict_types = 1 );

/**
 * BeyondWords Component: Player style
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.1.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * PlayerStyle
 *
 * @since 4.1.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class PlayerStyle
{
    /**
     * Player styles.
     *
     * @var array Arry of player styles.
     */
    public const PLAYER_STYLES = [
        'small',
        'standard',
        'large',
        'screen',
        'video',
    ];

    /**
     * Player style options for the per-post selector.
     *
     *
     * @return array<string,array{value:string,label:string,disabled?:bool}>
     */
    public static function get_options(): array
    {
        $styles = [
            'standard' => ['value' => 'standard', 'label' => __('Standard', 'speechkit')],
            'small'    => ['value' => 'small',    'label' => __('Small', 'speechkit')],
            'large'    => ['value' => 'large',    'label' => __('Large', 'speechkit')],
            'video'    => ['value' => 'video',    'label' => __('Video', 'speechkit')],
        ];

        /**
         * Filters the player styles offered in the per-post selector.
         *
         * @since 4.1.0 Introduced as `beyondwords_player_styles`.
         * @since 5.0.0 Renamed to `beyondwords_settings_player_styles`.
         *
         * @param array $styles Associative array of player styles.
         */
        $styles = apply_filters('beyondwords_settings_player_styles', $styles);

        return is_array($styles) ? $styles : [];
    }

    /**
     * Constructor
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('rest_api_init', [self::class, 'rest_api_init_callback']);

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
     * @since 4.1.0
     * @since 4.5.1 Hide element if no language data exists.
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param \WP_Post $post The post object.
     *
     * @return string|null
     */
    public static function element($post)
    {
        $player_style     = PostMetaUtils::get_player_style($post->ID);
        $all_player_styles = self::get_options();

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
            <option value=""></option>
            <?php
            foreach ($all_player_styles as $item) {
                printf(
                    '<option value="%s" %s %s>%s</option>',
                    esc_attr($item['value']),
                    selected(strval($item['value']), $player_style),
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
        if (! isset($_POST['beyondwords_player_style']) || ! isset($_POST['beyondwords_player_style_nonce'])) {
            return $post_id;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_player_style_nonce']),
                'beyondwords_player_style'
            )
        ) {
            return $post_id;
        }

        $player_style = sanitize_text_field(wp_unslash($_POST['beyondwords_player_style']));

        if (! empty($player_style)) {
            update_post_meta($post_id, 'beyondwords_player_style', $player_style);
        } else {
            delete_post_meta($post_id, 'beyondwords_player_style');
        }

        return $post_id;
    }

    /**
     * Register WP REST API route
     *
     * @since 4.1.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @return void
     */
    public static function rest_api_init_callback()
    {
        // Player styles endpoint
        register_rest_route('beyondwords/v1', '/projects/(?P<projectId>[0-9]+)/player-styles', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'player_styles_rest_api_response'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }

    /**
     * "Player styles" WP REST API response (required for the Gutenberg editor).
     *
     * @since 4.1.0
     * @since 5.0.0 Stop saving a dedicated player styles transient for each project ID.
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function player_styles_rest_api_response()
    {
        $response = self::get_options();

        // Convert from object to array so we can use find() in Block Editor JS.
        $response = array_values($response);

        return new \WP_REST_Response($response);
    }
}
