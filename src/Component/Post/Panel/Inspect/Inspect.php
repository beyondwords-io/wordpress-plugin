<?php

declare(strict_types=1);

/**
 * BeyondWords Post Inspect Panel.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\Panel\Inspect;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * Inspect
 *
 * @since 3.0.0
 */
class Inspect
{
    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        add_action("add_meta_boxes", array($this, 'addMetaBox'));

        add_filter('default_hidden_meta_boxes', array($this, 'hideMetaBox'));

        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_action("save_post_{$postType}", array($this, 'save'), 5);
                }
            }
        });
    }

    /**
     * Enqueue JS for Inspect feature.
     */
    public function adminEnqueueScripts($hook)
    {
        // Only enqueue for Post screens
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            // @todo Add Clipboard.js as an npm dependency (it's in the inspect.js file for now)
            wp_enqueue_script(
                'beyondwords-inspect',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/Panel/Inspect/js/inspect.js',
                array('jquery'),
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );
        }
    }

    /**
     * Hides the metabox by default.
     *
     * @param string[] $hidden An array of IDs of meta boxes hidden by default.
     */
    public function hideMetaBox($hidden)
    {
        $hidden[] = 'beyondwords__inspect';
        return $hidden;
    }

    /**
     * Adds the meta box container for the Classic Editor.
     *
     * The Block Editor UI is handled using JavaScript.
     *
     * @param string $postType
     */
    public function addMetaBox($postType)
    {
        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (is_array($postTypes) && ! in_array($postType, $postTypes)) {
            return;
        }

        add_meta_box(
            'beyondwords__inspect',
            __('BeyondWords', 'speechkit') . ': ' . __('Inspect', 'speechkit'),
            array($this, 'renderMetaBoxContent'),
            $postType,
            'advanced',
            'low',
            [
                '__back_compat_meta_box' => true,
            ]
        );
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     *
     * @since 3.0.0 Introduced.
     * @since 5.2.3 Copy more metadata than we display.
     */
    public function renderMetaBoxContent($post)
    {
        // Copy all metadata, but only display a subset
        $copy    = PostMetaUtils::getMetadata($post->ID, 'all');
        $display = PostMetaUtils::getMetadata($post->ID);

        $this->postMetaTable($display);
        ?>
        <button
            type="button"
            id="beyondwords__inspect--copy"
            class="button button-large"
            style="margin: 10px 10px 0 0;"
            data-clipboard-text="<?php echo esc_attr($this->getClipboardText($copy)); ?>"
        >
            <?php esc_html_e('Copy', 'speechkit'); ?>
            <span
                id="beyondwords__inspect--copy-confirm"
                style="display: none; margin: 5px 0 0;"
                class="dashicons dashicons-yes"
            ></span>
        </button>

        <div style="float: right;">
            <button
                type="button"
                id="beyondwords__inspect--edit"
                class="button button-large"
                style="margin: 10px 0 0 10px;"
            >
                <?php esc_html_e('Edit', 'speechkit'); ?>
            </button>

            <button
                type="button"
                id="beyondwords__inspect--remove"
                class="button button-large button-link-delete"
                style="margin: 10px 0 0 10px;"
            >
                <?php esc_html_e('Remove', 'speechkit'); ?>
                <span
                    id="beyondwords__inspect--remove"
                    style="display: none; margin: 5px 0 0;"
                    class="dashicons dashicons-yes"
                ></span>
            </button>
        </div>
        <?php
    }

    /**
     * Render Meta Box table.
     *
     * @param array   $metadata The metadata returned by has_meta.
     *
     * @since v3.0.0
     * @since v3.9.0 Change $postMetaKeys param to $metadata, to support meta_ids.
     */
    public function postMetaTable($metadata)
    {
        if (! is_array($metadata)) {
            return;
        }
        ?>
        <div id="postcustomstuff">
            <table id="inspect-table">
                <thead>
                    <tr>
                        <th class="left"><?php esc_html_e('Name', 'speechkit'); ?></th>
                        <th><?php esc_html_e('Value', 'speechkit'); ?></th>
                    </tr>
                </thead>
                <tbody id="inspect-table-list">
                    <?php
                    foreach ($metadata as $item) :
                        if (
                            ! is_array($item) ||
                            ! array_key_exists('meta_id', $item) ||
                            ! array_key_exists('meta_key', $item) ||
                            ! array_key_exists('meta_value', $item)
                        ) {
                            continue;
                        }

                        $metaId    = $item['meta_id'] ? $item['meta_id'] : $item['meta_key'];
                        $metaKey   = $item['meta_key'];
                        $metaValue = $this->formatPostMetaValue($item['meta_value']);
                        ?>
                        <tr id="beyondwords-inspect-<?php echo esc_attr($metaId); ?>" class="alternate">
                            <td class="left">
                                <label
                                    class="screen-reader-text"
                                    for="beyondwords-inspect-<?php echo esc_attr($metaId); ?>-key"
                                >
                                    <?php esc_html_e('Key', 'speechkit'); ?>
                                </label>
                                <input
                                    id="beyondwords-inspect-<?php echo esc_attr($metaId); ?>-key"
                                    type="text"
                                    size="20"
                                    value="<?php echo esc_attr($metaKey); ?>"
                                    readonly
                                />
                            </td>
                            <td>
                                <label
                                    class="screen-reader-text"
                                    for="beyondwords-inspect-<?php echo esc_attr($metaId); ?>-value"
                                >
                                    <?php esc_html_e('Value', 'speechkit'); ?>
                                </label>
                                <textarea
                                    id="beyondwords-inspect-<?php echo esc_attr($metaId); ?>-value"
                                    name="beyondwords_inspect_panel[<?php echo esc_attr($metaKey); ?>]"
                                    rows="2"
                                    cols="30"
                                    data-beyondwords-metavalue="true"
                                    readonly
                                ><?php echo esc_html($metaValue); ?></textarea>
                            </td>
                        </tr>
                        <?php
                    endforeach;

                    wp_nonce_field('beyondwords_inspect_panel', 'beyondwords_inspect_panel_nonce');
                    ?>
                    <input
                        type="hidden"
                        id="beyondwords_inspect_panel_action"
                        name="beyondwords_inspect_panel_action"
                        value=""
                    />
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Format post meta value.
     *
     * @param mixed $value The metadata value.
     *
     * @since v3.9.0
     */
    public function formatPostMetaValue($value)
    {
        if (is_numeric($value) || is_string($value)) {
            return $value;
        }

        return wp_json_encode($value);
    }

    /**
     * Get Clipboard Text.
     *
     * @param array $metadata Post metadata.
     *
     * @since 3.0.0
     * @since 3.9.0 Output all post meta data from the earlier has_meta() call instead of
     *              the previous multiple get_post_meta() calls.
     *
     * @return string
     */
    public function getClipboardText($metadata)
    {
        $lines = [];

        foreach ($metadata as $m) {
            $lines[] = $m['meta_key'] . "\r\n" . $this->formatPostMetaValue($m['meta_value']);
        }

        $lines[] = "=== " . __('Copied using the Classic Editor', 'speechkit') . " ===\r\n\r\n";

        return implode("\r\n\r\n", $lines);
    }

    /**
     * Runs when a post is saved.
     *
     * If "Remove" has been pressed in the Classic Editor we set the `beyondwords_delete_content`
     * custom field. At a later priority we check for this custom field and if it's set
     * we make a DELETE request to the BeyondWords REST API, keeping WordPress and the
     * REST API in sync.
     *
     * If we don't perform a DELETE REST API request to keep them in sync then the
     * API will respond with a "source_id is already in use" error message whenver we
     * attempt to regenerate audio for a post that has audio content "Removed" in
     * WordPress but still exists in the REST API.
     *
     * @since 4.0.7
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
            ! isset($_POST['beyondwords_inspect_panel_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_inspect_panel_nonce']),
                'beyondwords_inspect_panel'
            )
        ) {
            return $postId;
        }

        $action = '';
        if (isset($_POST['beyondwords_inspect_panel_action'])) {
            $action = sanitize_key($_POST['beyondwords_inspect_panel_action']);
        }

        if ('delete' === $action) {
            // Set a flag - the post meta is deleted later along with a DELETE REST API request
            update_post_meta($postId, 'beyondwords_delete_content', '1');
        } elseif ('edit' === $action) {
            $postedFields = $_POST['beyondwords_inspect_panel'] ?? [];

            if (is_array($postedFields)) {
                $currentMetaKeys = CoreUtils::getPostMetaKeys('current');

                foreach ($postedFields as $metaKey => $metaValue) {
                    if (in_array($metaKey, $currentMetaKeys)) {
                        update_post_meta($postId, $metaKey, $metaValue);
                    }
                }
            }
        }

        return $postId;
    }
}
