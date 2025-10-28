<?php

declare(strict_types=1);

/**
 * BeyondWords Bulk Edit.
 *
 * Text Domain: beyondwords
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Posts\BulkEdit;

use Beyondwords\Wordpress\Core\Core;
use Beyondwords\Wordpress\Core\CoreUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Plugin;

/**
 * BulkEdit
 *
 * @since 3.0.0
 */
class BulkEdit
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('bulk_edit_custom_box', [self::class, 'bulkEditCustomBox'], 10, 2);
        add_action('wp_ajax_save_bulk_edit_beyondwords', [self::class, 'saveBulkEdit']);

        add_action('wp_loaded', function (): void {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_filter("bulk_actions-edit-{$postType}", [self::class, 'bulkActionsEdit'], 10, 1);
                    add_filter("handle_bulk_actions-edit-{$postType}", [self::class, 'handleBulkDeleteAction'], 10, 3); // phpcs:ignore Generic.Files.LineLength.TooLong
                    add_filter("handle_bulk_actions-edit-{$postType}", [self::class, 'handleBulkGenerateAction'], 10, 3); // phpcs:ignore Generic.Files.LineLength.TooLong
                }
            }
        });
    }

    /**
     * Adds the meta box container.
     *
     * @since 6.0.0 Make static.
     */
    public static function bulkEditCustomBox(string $columnName, string $postType): void
    {
        if ($columnName !== 'beyondwords') {
            return;
        }

        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (! in_array($postType, $postTypes)) {
            return;
        }

        wp_nonce_field('beyondwords_bulk_edit_nonce', 'beyondwords_bulk_edit');

        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <div class="inline-edit-group wp-clearfix">
                    <label class="alignleft">
                        <span class="title"><?php esc_html_e('BeyondWords', 'speechkit'); ?></span>
                        <select name="beyondwords_generate_audio">
                            <option value="-1"><?php esc_html_e('— No change —', 'speechkit'); ?></option>
                            <option value="generate"><?php esc_html_e('Generate audio', 'speechkit'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete audio', 'speechkit'); ?></option>
                        </select>
                    </label>
                </div>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Save Bulk Edit updates.
     *
     * @link https://rudrastyh.com/wordpress/bulk-edit.html
     *
     * @since 6.0.0 Make static.
     */
    public static function saveBulkEdit()
    {
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */
        if (
            ! isset($_POST['beyondwords_bulk_edit_nonce']) ||
            ! wp_verify_nonce(sanitize_key($_POST['beyondwords_bulk_edit_nonce']), 'beyondwords_bulk_edit')
        ) {
            wp_nonce_ays('');
        }

        if (! isset($_POST['beyondwords_bulk_edit']) || ! isset($_POST['post_ids'])) {
            return [];
        }

        if (is_array($_POST['post_ids']) && count($_POST['post_ids'])) {
            $postIds = array_map('intval', $_POST['post_ids']);
            $postIds = array_filter($postIds);

            switch ($_POST['beyondwords_bulk_edit']) {
                case 'generate':
                    return self::generateAudioForPosts($postIds);
                case 'delete':
                    return self::deleteAudioForPosts($postIds);
            }
        }

        return [];
    }

    /**
     * Generate audio for posts.
     *
     * @since 6.0.0 Make static.
     */
    public static function generateAudioForPosts(array|null $postIds): array
    {
        if (! is_array($postIds)) {
            return [];
        }

        $updatedPostIds = [];

        foreach ($postIds as $postId) {
            if (! get_post_meta($postId, 'beyondwords_content_id', true)) {
                update_post_meta($postId, 'beyondwords_generate_audio', '1');
            }
            $updatedPostIds[] = $postId;
        }

        return $updatedPostIds;
    }

    /**
     * Delete audio for posts.
     *
     * @since 6.0.0 Make static.
     */
    public static function deleteAudioForPosts(array|null $postIds): array
    {
        if (! is_array($postIds)) {
            return [];
        }

        $updatedPostIds = [];

        $response = Core::batchDeleteAudioForPosts($postIds);

        if (! $response) {
            throw new \Exception(esc_html__('Error while bulk deleting audio. Please contact support with reference BULK-NO-RESPONSE.', 'speechkit')); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        // Now process all posts
        $keys = CoreUtils::getPostMetaKeys('all');

        foreach ($response as $postId) {
            foreach ($keys as $key) {
                delete_post_meta($postId, $key);
            }
            $updatedPostIds[] = $postId;
        }

        return $updatedPostIds;
    }

    /**
     * Add custom bulk actions to the posts list table.
     *
     * @since 6.0.0 Make static.
     */
    public static function bulkActionsEdit(array $bulk_array): array
    {
        $bulk_array['beyondwords_generate_audio'] = __('Generate audio', 'speechkit');
        $bulk_array['beyondwords_delete_audio']   = __('Delete audio', 'speechkit');

        return $bulk_array;
    }

    /**
     * Handle the "Generate audio" bulk action.
     *
     * @since 6.0.0 Make static.
     */
    public static function handleBulkGenerateAction(string $redirect, string $doaction, array $objectIds): string
    {
        if ($doaction !== 'beyondwords_generate_audio') {
            return $redirect;
        }

        // Remove query args
        $args = [
            'beyondwords_bulk_generated',
            'beyondwords_bulk_deleted',
            'beyondwords_bulk_failed',
            'beyondwords_bulk_error',
        ];

        $redirect = remove_query_arg($args, $redirect);

        // Order batch by Post ID asc
        sort($objectIds);

        $generated = 0;
        $failed    = 0;

        try {
            // Update all custom fields before attempting any processing
            foreach ($objectIds as $postId) {
                update_post_meta($postId, 'beyondwords_generate_audio', '1');
            }

            // Now process all posts
            foreach ($objectIds as $postId) {
                $response = Core::generateAudioForPost($postId);

                if ($response) {
                    $generated++;
                } else {
                    $failed++;
                }
            }
        } catch (\Exception $e) {
            $redirect = add_query_arg('beyondwords_bulk_error', $e->getMessage(), $redirect);
        }

        // Add $generated & $failed query args into redirect
        $redirect = add_query_arg('beyondwords_bulk_generated', $generated, $redirect);
        $redirect = add_query_arg('beyondwords_bulk_failed', $failed, $redirect);

        // Add nonce to redirect url
        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        return add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);
    }

    /**
     * Handle the "Delete audio" bulk action.
     *
     * @since 6.0.0 Make static.
     */
    public static function handleBulkDeleteAction(string $redirect, string $doaction, array $objectIds): string
    {
        if ($doaction !== 'beyondwords_delete_audio') {
            return $redirect;
        }

        // Remove query args
        $args = [
            'beyondwords_bulk_generated',
            'beyondwords_bulk_deleted',
            'beyondwords_bulk_failed',
            'beyondwords_bulk_error',
        ];

        $redirect = remove_query_arg($args, $redirect);

        // Order batch by Post ID asc
        sort($objectIds);

        $deleted = 0;

        // Handle "Delete audio" bulk action
        try {
            $result = self::deleteAudioForPosts($objectIds);

            $deleted = count($result);

            // Add $deleted query arg into redirect
            $redirect = add_query_arg('beyondwords_bulk_deleted', $deleted, $redirect);
        } catch (\Exception $e) {
            $redirect = add_query_arg('beyondwords_bulk_error', $e->getMessage(), $redirect);
        }

        // Add $nonce query arg into redirect
        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        return add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);
    }
}
