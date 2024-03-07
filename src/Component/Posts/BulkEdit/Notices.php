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

/**
 * BulkEdit setup
 *
 * @since 4.5.0
 */
class Notices
{
    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_notices', array($this, 'generatedNotice'));
        add_action('admin_notices', array($this, 'deletedNotice'));
        add_action('admin_notices', array($this, 'failedNotice'));
        add_action('admin_notices', array($this, 'errorNotice'));
    }

    /**
     * @since 4.1.0
     */
    public function generatedNotice()
    {
        if (! isset($_GET['beyondwords_bulk_edit_result_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field($_GET['beyondwords_bulk_generated'] ?? ''));

        if ($count) {
            $message = sprintf(
                /* translators: %d is replaced with the number of posts processed */
                _n(
                    'Audio was requested for %d post.',
                    'Audio was requested for %d posts.',
                    $count,
                    'speechkit'
                ),
                $count
            );
            ?>
            <div id="beyondwords-bulk-edit-notice-generated" class="notice notice-info is-dismissible">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function deletedNotice()
    {
        if (! isset($_GET['beyondwords_bulk_edit_result_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field($_GET['beyondwords_bulk_deleted'] ?? ''));

        if ($count) {
            $message = sprintf(
                /* translators: %d is replaced with the number of posts processed */
                _n(
                    'Audio was deleted for %d post.',
                    'Audio was deleted for %d posts.',
                    $count,
                    'speechkit'
                ),
                $count
            );
            ?>
            <div id="beyondwords-bulk-edit-notice-deleted" class="notice notice-info is-dismissible">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function failedNotice()
    {
        if (! isset($_GET['beyondwords_bulk_edit_result_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field($_GET['beyondwords_bulk_failed'] ?? ''));

        if ($count) {
            $message = sprintf(
                /* translators: %d is replaced with the number of posts that were skipped */
                _n(
                    '%d post failed, check for errors in the BeyondWords column below.',
                    '%d posts failed, check for errors in the BeyondWords column below.',
                    $count,
                    'speechkit'
                ),
                $count
            );
            ?>
            <div id="beyondwords-bulk-edit-notice-failed" class="notice notice-error is-dismissible">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function errorNotice()
    {
        if (! isset($_GET['beyondwords_bulk_edit_result_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $message = sanitize_text_field($_GET['beyondwords_bulk_error'] ?? '');

        if ($message) {
            ?>
            <div id="beyondwords-bulk-edit-notice-error" class="notice notice-error is-dismissible">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>
            <?php
        }
    }
}
