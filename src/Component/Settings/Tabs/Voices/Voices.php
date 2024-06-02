<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Voices
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Voices;

use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\BodySpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\TitleSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\BodyVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\TitleVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\DefaultLanguage\DefaultLanguage;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * "Voices" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Voices
{
     /**
     * API Client.
     *
     * @since 4.8.0
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
        (new DefaultLanguage($this->apiClient))->init();
        (new TitleVoice($this->apiClient))->init();
        (new TitleSpeakingRate())->init();
        (new BodyVoice($this->apiClient))->init();
        (new BodySpeakingRate())->init();

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
            'voices',
            __('Voices', 'speechkit'),
            [$this, 'sectionCallback'],
            'beyondwords_voices',
            // [
            //     'before_section' => '<div id="voices" data-tab="voices">',
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
                'Only future content will be affected. To apply changes to existing content, please regenerate each post.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    public function syncCheck($hook)
    {
        if ($hook === 'settings_page_beyondwords') {
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
        $params = [];

        $options = SettingsUtils::getSyncedOptions('project');

        foreach ($options as $name => $args) {
            if (array_key_exists('path', $args)) {
                $t = get_transient('beyondwords/sync/' . $name);
                if ($t !== false) {
                    $params[$args['path']] = $t;
                    add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Detected change, syncing ' . $name . ' to /project.' . $args['path'], 'info');
                    delete_transient('beyondwords/sync/' . $name);
                }
            }
        }

        // wp_die(wp_json_encode($params));

        if (count($params)) {
            // Sync WordPress -> REST API
            $result = $this->apiClient->updateProject($params);

            if ($result) {
                // Success notice
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> The WordPress settings were synced to the BeyondWords dashboard.', 'info');
            } else {
                // Error notice
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', 'error');
            }
        }
    }
}
