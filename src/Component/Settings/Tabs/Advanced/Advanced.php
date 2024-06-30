<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > General
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Advanced;

use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;
use Beyondwords\Wordpress\Component\Settings\Fields\SyncSettings\SyncSettings;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * "Advanced" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Advanced
{
    /**
     * API client.
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init
     */
    public function init()
    {
        (new Languages($this->apiClient))->init();
        (new SyncSettings())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
        add_action('admin_enqueue_scripts', array($this, 'syncCheck'));
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'advanced',
            __('Advanced', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_advanced',
            // [
            //     'before_section' => '<div id="advanced" data-tab="advanced">',
            //     'after_section' => '</div>',
            // ]
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
                'Do we want a description for consistency?', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Check whether we want to sync to/from the API.
     *
     * We don't automatically sync to the API. We only sync if a
     * "Sync Settings to Dashboard" button is pressed.
     *
     * @return void
     */
    public function syncCheck($hook)
    {
        if ($hook !== 'settings_page_beyondwords') {
            return;
        }

        $syncToApi = isset($_GET['sync_to_api']);

        if ($syncToApi) {
            $this->syncToRestApi();
        }
    }

    /**
     * Sync with BeyondWords REST API.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncToRestApi()
    {
        $playerOptions = [];

        // @todo get all player options
        // $playerOptions = SettingsUtils::getPlayerOptions();

        // @todo Sync other settings too

        if (count($playerOptions)) {
            // Sync WordPress -> REST API
            $result = $this->apiClient->updatePlayerSettings($playerOptions);

            if (! $result) {
                // Error notice
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', 'error');
            }
        }
    }
}
