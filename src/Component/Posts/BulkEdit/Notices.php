<?php

declare(strict_types=1);

/**
 * BeyondWords BulkEdit Notices.
 *
 * Text Domain: beyondwords
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Posts\BulkEdit;

/**
 * Notices
 *
 * @since 4.5.0
 */
defined('ABSPATH') || exit;

class Notices
{
    /**
     * Constructor
     *
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('admin_notices', [self::class, 'generatedNotice']);
        add_action('admin_notices', [self::class, 'deletedNotice']);
        add_action('admin_notices', [self::class, 'failedNotice']);
        add_action('admin_notices', [self::class, 'errorNotice']);
    }

    /**
     * Generated audio notice.
     *
     * @since 4.1.0
     * @since 6.0.0 Make static.
     */
    public static function generatedNotice()
    {
        if (
            ! isset($_GET['beyondwords_bulk_edit_result_nonce'])
            || ! isset($_GET['beyondwords_bulk_generated'])
        ) {
            return;
        }

        if (! wp_verify_nonce(sanitize_key($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field(wp_unslash($_GET['beyondwords_bulk_generated'])));

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
     * Deleted audio notice.
     *
     * @since 6.0.0 Make static.
     */
    public static function deletedNotice()
    {
        if (
            ! isset($_GET['beyondwords_bulk_edit_result_nonce'])
            || ! isset($_GET['beyondwords_bulk_deleted'])
        ) {
            return;
        }

        if (! wp_verify_nonce(sanitize_key($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field(wp_unslash($_GET['beyondwords_bulk_deleted'])));

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
     * Failed audio notice.
     *
     * @since 6.0.0 Make static.
     */
    public static function failedNotice()
    {
        if (
            ! isset($_GET['beyondwords_bulk_edit_result_nonce'])
            || ! isset($_GET['beyondwords_bulk_failed'])
        ) {
            return;
        }

        if (! wp_verify_nonce(sanitize_key($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $count = intval(sanitize_text_field(wp_unslash($_GET['beyondwords_bulk_failed'])));

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
     * Error notice.
     *
     * @since 6.0.0 Make static.
     */
    public static function errorNotice()
    {
        if (
            ! isset($_GET['beyondwords_bulk_edit_result_nonce'])
            || ! isset($_GET['beyondwords_bulk_error'])
        ) {
            return;
        }

        if (! wp_verify_nonce(sanitize_key($_GET['beyondwords_bulk_edit_result_nonce']), 'beyondwords_bulk_edit_result')) { // phpcs:ignore Generic.Files.LineLength.TooLong
            wp_nonce_ays('');
        }

        $message = sanitize_text_field(wp_unslash($_GET['beyondwords_bulk_error']));

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
