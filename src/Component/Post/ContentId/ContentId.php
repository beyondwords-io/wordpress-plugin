<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Content ID
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   6.1.0
 */

namespace Beyondwords\Wordpress\Component\Post\ContentId;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * ContentId
 *
 * @since 6.1.0
 */
defined('ABSPATH') || exit;

class ContentId
{
    /**
     * Init.
     *
     * @since 6.1.0
     */
    public static function init()
    {
        add_action('admin_enqueue_scripts', [self::class, 'adminEnqueueScripts']);

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
     * HTML output for this component.
     *
     * @since 6.1.0
     *
     * @param \WP_Post $post The post object.
     */
    public static function element($post)
    {
        $contentId = PostMetaUtils::getContentId($post->ID) ?: '';
        $projectId = get_option('beyondwords_project_id', '');

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
                    value="<?php echo esc_attr($contentId); ?>"
                    style="flex: 1;"
                />
                <button
                    type="button"
                    id="beyondwords__content-id--fetch"
                    class="button"
                    data-project-id="<?php echo esc_attr($projectId); ?>"
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
     * @since 6.1.0
     *
     * @param string $hook Page hook
     */
    public static function adminEnqueueScripts($hook)
    {
        if (! CoreUtils::isGutenbergPage() && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_register_script(
                'beyondwords-metabox--content-id',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/ContentId/classic-metabox.js',
                ['jquery'],
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
     * @since 6.1.0
     *
     * @param int $postId The ID of the post being saved.
     */
    public static function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        if (
            ! isset($_POST['beyondwords_content_id_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_content_id_nonce']),
                'beyondwords_content_id'
            )
        ) {
            return $postId;
        }

        if (isset($_POST['beyondwords_content_id'])) {
            update_post_meta(
                $postId,
                'beyondwords_content_id',
                sanitize_text_field(wp_unslash($_POST['beyondwords_content_id']))
            );
        }

        return $postId;
    }
}
