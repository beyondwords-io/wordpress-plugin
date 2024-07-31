<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Credentials
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Credentials;

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * "Credentials" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Credentials
{
    /**
     * Init
     */
    public function init()
    {
        (new ApiKey())->init();
        (new ProjectId())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
        add_action('admin_init', array($this, 'maybeSync'), 10);
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'credentials',
            __('Credentials', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_credentials',
        );
    }

    /**
     * Section callback
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function sectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Please add your Project ID and API key to authenticate your BeyondWords account.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Maybe sync to REST API.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function maybeSync()
    {
        $submitted = isset($_POST['submit-credentials' ]); // phpcs:ignore WordPress.Security.NonceVerification

        if (! $submitted) {
            return;
        }

        // @todo Make API Call

        $result = false;

        if (! $result) {
            // Error notice
            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'error'
            );
        } else {
            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Settings synced from WordPress to the BeyondWords dashboard.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'success'
            );
        }
    }
}
