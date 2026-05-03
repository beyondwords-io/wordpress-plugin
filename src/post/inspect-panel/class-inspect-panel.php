<?php

declare( strict_types = 1 );

/**
 * BeyondWords Post Inspect Panel.
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * Inspect
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class InspectPanel
{
    /**
     * Constructor
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('add_meta_boxes', [self::class, 'add_meta_box_callback']);
        add_action('rest_api_init', [self::class, 'rest_api_init_callback']);

        add_filter('default_hidden_meta_boxes', [self::class, 'hide_meta_box']);

        add_action('wp_loaded', function (): void {
            $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

            if (is_array($post_types)) {
                foreach ($post_types as $post_type) {
                    add_action("save_post_{$post_type}", [self::class, 'save'], 5);
                }
            }
        });
    }

    /**
     * Hides the metabox by default.
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param string[] $hidden An array of IDs of meta boxes hidden by default.
     */
    public static function hide_meta_box($hidden)
    {
        $hidden[] = 'beyondwords__inspect';
        return $hidden;
    }

    /**
     * Adds the meta box container for the Classic Editor.
     *
     * The Block Editor UI is handled using JavaScript.
     *
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param string $post_type
     */
    public static function add_meta_box_callback($post_type)
    {
        $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

        if (! in_array($post_type, $post_types)) {
            return;
        }

        add_meta_box(
            'beyondwords__inspect',
            __('BeyondWords', 'speechkit') . ': ' . __('Inspect', 'speechkit'),
            [self::class, 'render_meta_box_content'],
            $post_type,
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
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param \WP_Post $post The post object.
     */
    public static function render_meta_box_content($post)
    {
        $metadata = PostMetaUtils::get_all_beyondwords_metadata($post->ID);

        self::post_meta_table($metadata);
        ?>
        <button
            type="button"
            id="beyondwords__inspect--copy"
            class="button button-large"
            style="margin: 10px 0 0;"
            data-clipboard-text="<?php echo esc_attr(self::get_clipboard_text($metadata)); ?>"
        >
            <?php esc_html_e('Copy', 'speechkit'); ?>
            <span
                id="beyondwords__inspect--copy-confirm"
                style="display: none; margin: 5px 0 0;"
                class="dashicons dashicons-yes"
            ></span>
        </button>

        <button
            type="button"
            id="beyondwords__inspect--remove"
            class="button button-large button-link-delete"
            style="margin: 10px 0 0; float: right;"
        >
            <?php esc_html_e('Remove', 'speechkit'); ?>
            <span
                id="beyondwords__inspect--remove"
                style="display: none; margin: 5px 0 0;"
                class="dashicons dashicons-yes"
            ></span>
        </button>

        <?php
    }

    /**
     * Render Meta Box table.
     *
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param array   $metadata The metadata returned by has_meta.
     *
     * @since v3.0.0
     * @since v3.9.0 Change $post_meta_keys param to $metadata, to support meta_ids.
     * @since 6.0.0 Make static.
     */
    public static function post_meta_table($metadata)
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

                        $meta_id    = $item['meta_id'] ?: $item['meta_key'];
                        $meta_key   = $item['meta_key'];
                        $meta_value = self::format_post_meta_value($item['meta_value']);
                        ?>
                        <tr id="beyondwords-inspect-<?php echo esc_attr($meta_id); ?>" class="alternate">
                            <td class="left">
                                <label
                                    class="screen-reader-text"
                                    for="beyondwords-inspect-<?php echo esc_attr($meta_id); ?>-key"
                                >
                                    <?php esc_html_e('Key', 'speechkit'); ?>
                                </label>
                                <input
                                    id="beyondwords-inspect-<?php echo esc_attr($meta_id); ?>-key"
                                    type="text"
                                    size="20"
                                    value="<?php echo esc_attr($meta_key); ?>"
                                    readonly
                                />
                            </td>
                            <td>
                                <label
                                    class="screen-reader-text"
                                    for="beyondwords-inspect-<?php echo esc_attr($meta_id); ?>-value"
                                >
                                    <?php esc_html_e('Value', 'speechkit'); ?>
                                </label>
                                <textarea
                                    id="beyondwords-inspect-<?php echo esc_attr($meta_id); ?>-value"
                                    rows="2"
                                    cols="30"
                                    data-beyondwords-metavalue="true"
                                    readonly
                                ><?php echo esc_html($meta_value); ?></textarea>
                            </td>
                        </tr>
                        <?php
                    endforeach;

                    wp_nonce_field('beyondwords_delete_content', 'beyondwords_delete_content_nonce');
                    ?>
                    <input
                        type="hidden"
                        id="beyondwords_delete_content"
                        name="beyondwords_delete_content"
                        value="1"
                        disabled
                    />
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Format post meta value.
     *
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param mixed $value The metadata value.
     *
     * @since 3.9.0
     * @since 6.0.0 Make static.
     */
    public static function format_post_meta_value($value)
    {
        if (is_numeric($value) || is_string($value)) {
            return $value;
        }

        return wp_json_encode($value);
    }

    /**
     * Get Clipboard Text.
     *
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param array $metadata Post metadata.
     *
     * @since 3.0.0
     * @since 3.9.0 Output all post meta data from the earlier has_meta() call instead of
     *              the previous multiple get_post_meta() calls.
     * @since 6.0.0 Make static.
     *
     * @return string
     */
    public static function get_clipboard_text($metadata)
    {
        $lines = [];

        foreach ($metadata as $m) {
            $lines[] = $m['meta_key'] . "\r\n" . self::format_post_meta_value($m['meta_value']);
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
            ! isset($_POST['beyondwords_delete_content_nonce']) ||
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_delete_content_nonce']),
                'beyondwords_delete_content'
            )
        ) {
            return $post_id;
        }

        if (isset($_POST['beyondwords_delete_content'])) {
            // Set the flag - the DELETE request is performed at a later priority
            update_post_meta($post_id, 'beyondwords_delete_content', '1');
        }

        return $post_id;
    }

    /**
     * REST API init.
     *
     * Register REST API routes.
     *
     * @since 6.0.0 Make static.
     **/
    public static function rest_api_init_callback()
    {
        register_rest_route('beyondwords/v1', '/projects/(?P<projectId>[0-9]+)/content/(?P<beyondwordsId>[a-zA-Z0-9\-]+)', [ // phpcs:ignore Generic.Files.LineLength.TooLong
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'rest_api_response'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }

    /**
     * REST API response.
     *
     * Fetches a content object from the BeyondWords REST API.
     *
     * @since 6.0.0 Make static.
     *
     * @param \WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response
     **/
    public static function rest_api_response(\WP_REST_Request $request)
    {
        $project_id     = $request['projectId'] ?? '';
        $beyondwords_id = $request['beyondwordsId'] ?? ''; // Can be either contentId or sourceId

        if (! is_numeric($project_id)) {
            return rest_ensure_response(
                new \WP_Error(
                    400,
                    __('Invalid Project ID', 'speechkit'),
                    ['projectId' => $project_id]
                )
            );
        }

        if (empty($beyondwords_id)) {
            return rest_ensure_response(
                new \WP_Error(
                    400,
                    __('Invalid Content ID', 'speechkit'),
                    ['beyondwordsId' => $beyondwords_id]
                )
            );
        }

        $response = \BeyondWords\Api\Client::get_content($beyondwords_id, $project_id);

        // Check for REST API connection errors.
        if (is_wp_error($response)) {
            return rest_ensure_response(
                new \WP_Error(
                    500,
                    __('Could not connect to BeyondWords API', 'speechkit'),
                    $response->get_error_data()
                )
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Check for REST API response errors.
        if ($code < 200 || $code >= 300) {
            return rest_ensure_response(
                new \WP_Error(
                    $code,
                    /* translators: %d is replaced with the error code. */
                    sprintf(__('BeyondWords REST API returned error code %d', 'speechkit'), $code),
                    [
                        'body' => $body,
                    ]
                )
            );
        }

        $data = json_decode($body, true);

        // Check for REST API JSON response.
        if (! is_array($data)) {
            return rest_ensure_response(
                new \WP_Error(
                    500,
                    __('Invalid response from BeyondWords API', 'speechkit')
                )
            );
        }

        // Return the project ID in the response.
        $data['project_id'] = $project_id;

        return rest_ensure_response($data);
    }
}
