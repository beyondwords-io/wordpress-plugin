<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Content ID
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   6.3.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * ContentId
 *
 * @since 6.3.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined('ABSPATH') || exit;

class ContentId
{
    /**
     * Init.
     *
     * @since 6.3.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('admin_enqueue_scripts', [self::class, 'admin_enqueue_scripts_callback']);

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
     * @since 6.3.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param \WP_Post $post The post object.
     */
    public static function element($post)
    {
        $content_id = PostMetaUtils::get_content_id($post->ID) ?: '';
        $project_id = PostMetaUtils::get_project_id($post->ID) ?: get_option('beyondwords_project_id', '');
        $post_type = get_post_type($post);
        $post_type_object = $post_type ? get_post_type_object($post_type) : null;
        $rest_base = ($post_type_object && ! empty($post_type_object->rest_base)) ? $post_type_object->rest_base : $post_type;

        wp_nonce_field('beyondwords_content_id', 'beyondwords_content_id_nonce');
        ?>
        <div id="beyondwords-metabox-content-id" style="margin: 8px 0 13px;">
            <p class="post-attributes-label-wrapper">
                <label for="beyondwords_content_id" class="post-attributes-label">
                    <?php esc_html_e('Content ID', 'speechkit'); ?>
                </label>
            </p>
            <div style="display: flex; gap: 4px; align-items: center;">
                <input
                    type="text"
                    id="beyondwords_content_id"
                    name="beyondwords_content_id"
                    value="<?php echo esc_attr($content_id); ?>"
                    style="flex: 1;"
                />
                <button
                    type="button"
                    id="beyondwords__content-id--fetch"
                    class="button"
                    data-project-id="<?php echo esc_attr($project_id); ?>"
                    data-rest-base="<?php echo esc_attr($rest_base); ?>"
                >
                    <?php esc_html_e('Fetch', 'speechkit'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Register the component scripts.
     *
     * @since 6.3.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param string $hook Page hook
     */
    public static function admin_enqueue_scripts_callback($hook)
    {
        if (! \BeyondWords\Core\CoreUtils::is_gutenberg_page() && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_register_script(
                'beyondwords-metabox--content-id',
                BEYONDWORDS__PLUGIN_URI . 'src/post/content-id/classic-metabox.js',
                ['wp-i18n'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );

            wp_localize_script(
                'beyondwords-metabox--content-id',
                'beyondwordsData',
                [
                    'nonce' => wp_create_nonce('wp_rest'),
                    'root' => esc_url_raw(rest_url()),
                ]
            );

            wp_enqueue_script('beyondwords-metabox--content-id');
        }
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 6.3.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public static function save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (
            ! isset($_POST['beyondwords_content_id_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_content_id_nonce']),
                'beyondwords_content_id'
            )
        ) {
            return $post_id;
        }

        if (isset($_POST['beyondwords_content_id'])) {
            update_post_meta(
                $post_id,
                'beyondwords_content_id',
                sanitize_text_field(wp_unslash($_POST['beyondwords_content_id']))
            );
        }

        return $post_id;
    }
}
