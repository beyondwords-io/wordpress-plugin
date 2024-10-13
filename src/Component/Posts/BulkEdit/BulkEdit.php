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
     */
    public function init()
    {
        add_action('bulk_edit_custom_box', array($this, 'bulkEditCustomBox'), 10, 2);
        add_action('wp_ajax_save_bulk_edit_beyondwords', array($this, 'saveBulkEdit'));

        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_filter("bulk_actions-edit-{$postType}", array($this, 'bulkActionsEdit'), 10, 1);
                    add_filter("handle_bulk_actions-edit-{$postType}", array($this, 'handleBulkDeleteAction'), 10, 3);
                    add_filter("handle_bulk_actions-edit-{$postType}", array($this, 'handleBulkGenerateAction'), 10, 3);
                }
            }
        });
    }

    /**
     * Adds the meta box container.
     */
    public function bulkEditCustomBox($columnName, $postType)
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
     */
    public function saveBulkEdit()
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
                    return $this->generateAudioForPosts($postIds);
                    break;
                case 'delete':
                    return $this->deleteAudioForPosts($postIds);
                    break;
            }
        }

        return [];
    }

    public function generateAudioForPosts($postIds)
    {
        if (! is_array($postIds)) {
            return false;
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
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function deleteAudioForPosts($postIds)
    {
        global $beyondwords_wordpress_plugin;

        if (! is_array($postIds)) {
            return false;
        }

        $updatedPostIds = [];

        $response = $beyondwords_wordpress_plugin->core->batchDeleteAudioForPosts($postIds);

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
     *
     */
    public function bulkActionsEdit($bulk_array)
    {
        $bulk_array['beyondwords_generate_audio'] = __('Generate audio', 'speechkit');
        $bulk_array['beyondwords_delete_audio']   = __('Delete audio', 'speechkit');

        return $bulk_array;
    }

    /**
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function handleBulkGenerateAction($redirect, $doaction, $objectIds)
    {
        if ($doaction !== 'beyondwords_generate_audio') {
            return $redirect;
        }

        global $beyondwords_wordpress_plugin;

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
                if (
                    $beyondwords_wordpress_plugin instanceof Plugin
                    && $beyondwords_wordpress_plugin->core instanceof Core
                ) {
                    $response = $beyondwords_wordpress_plugin->core->generateAudioForPost($postId);

                    if ($response) {
                        $generated++;
                    } else {
                        $failed++;
                    }
                } else {
                    throw new \Exception(esc_html__('Error while bulk generating audio. Please contact support with reference BULK-NO-PLUGIN.', 'speechkit')); // phpcs:ignore Generic.Files.LineLength.TooLong
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
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        return $redirect;
    }

    /**
     *
     */
    public function handleBulkDeleteAction($redirect, $doaction, $objectIds)
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
            $result = $this->deleteAudioForPosts($objectIds);

            $deleted = count($result);

            // Add $deleted query arg into redirect
            $redirect = add_query_arg('beyondwords_bulk_deleted', $deleted, $redirect);
        } catch (\Exception $e) {
            $redirect = add_query_arg('beyondwords_bulk_error', $e->getMessage(), $redirect);
        }

        // Add $nonce query arg into redirect
        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        return $redirect;
    }
}
