<?php

declare(strict_types=1);

/**
 * Setting: SyncSettings
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SyncSettings;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * SyncSettings setup
 *
 * @since 4.0.0
 */
class SyncSettings
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
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_advanced_settings',
            'beyondwords_sync',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-sync',
            __('Sync settings', 'speechkit'),
            array($this, 'render'),
            'beyondwords_advanced',
            'advanced'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.0.0
     *
     * @return void
     **/
    public function render()
    {
        ?>
        <div class="beyondwords-setting__sync">
            <button
                name="beyondwords_sync"
                class="button button-secondary"
                value="dashboard_to_wordpress"
            >
                <?php echo esc_attr('Dashboard to WordPress', 'speechkit'); ?>
            </button>
            <p class="description">
                <?php
                esc_html_e('Copy the settings from your BeyondWords account to this WordPress site.', 'speechkit');
                ?>
            </p>
            <p class="description description-warning">
                <?php
                esc_html_e('Warning: risk of data loss for the BeyondWords settings in your WordPress database. Proceed with caution.', 'speechkit');
                ?>
            </p>
            <button
                name="beyondwords_sync"
                class="button button-secondary"
                value="wordpress_to_dashboard"
            >
                <?php echo esc_attr('WordPress to Dashboard', 'speechkit'); ?>
            </button>
            <p class="description">
                <?php
                esc_html_e('Copy the settings from this WordPress site to your BeyondWords account.', 'speechkit');
                ?>
            </p>
            <p class="description description-warning">
                <?php
                esc_html_e('Warning: risk of data loss for the settings in your BeyondWords account. Proceed with caution.', 'speechkit');
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  4.8.0
     *
     * @param array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if ($value === 'dashboard_to_wordpress') {
            $this->syncFromRestApi();
        } else if ($value === 'wordpress_to_dashboard') {
            $this->syncToRestApi();
        }
    }

    /**
     * Sync data from the BeyondWords REST API to WordPress.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncFromRestApi()
    {
        // Assume invalid connection
        delete_option('beyondwords_valid_api_connection');

        // Sync REST API -> WordPress
        $project = $this->apiClient->getProject();

        $validConnection = (
            is_array($project)
            && array_key_exists('id', $project)
            && strval($project['id']) === get_option('beyondwords_project_id')
        );

        $updated = [];

        if (! $validConnection) {
            $errors = get_transient('beyondwords_settings_errors', []);

            $errors['Settings/ValidApiConnection'] = __(
                'Please check and re-enter your BeyondWords API key and project ID. They appear to be invalid.',
                'speechkit'
            );

            set_transient('beyondwords_settings_errors', $errors);

            return false;
        }

        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        if (false === get_option('beyondwords_project_language') && $project['language']) {
            $updated = update_option('beyondwords_project_language', $project['language'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.language has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_project_body_voice_id') && $project['body']['voice']['id']) {
            $updated = update_option('beyondwords_project_body_voice_id', $project['body']['voice']['id'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.body has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_project_title_voice_id') && $project['title']['voice']['id']) {
            $updated = update_option('beyondwords_project_title_voice_id', $project['title']['voice']['id'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.title has been synced to WordPress', 'success');
            }
        }

        // Sync Player Settings from REST API -> WordPress
        $playerSettings = $this->apiClient->getPlayerSettings();

        if (! $playerSettings || ! is_array($playerSettings)) {
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Unable to reach BeyondWords REST API to access the player settings.', 'error');

            return;
        }

        if (false === get_option('beyondwords_player_style') && $playerSettings['player_style']) {
            $updated = update_option('beyondwords_player_style', $playerSettings['player_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.player_style has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_theme') && $playerSettings['theme']) {
            $updated = update_option('beyondwords_player_theme', $playerSettings['theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_dark_theme') && $playerSettings['dark_theme']) {
            $updated = update_option('beyondwords_player_dark_theme', $playerSettings['dark_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.dark_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_light_theme') && $playerSettings['light_theme']) {
            $updated = update_option('beyondwords_player_light_theme', $playerSettings['light_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.light_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_video_theme') && $playerSettings['video_theme']) {
            $updated = update_option('beyondwords_player_video_theme', $playerSettings['video_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.video_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_call_to_action') && $playerSettings['call_to_action']) {
            $updated = update_option('beyondwords_player_call_to_action', $playerSettings['call_to_action'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.call_to_action has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_widget_style') && $playerSettings['widget_style']) {
            $updated = update_option('beyondwords_player_widget_style', $playerSettings['widget_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.widget_style has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_widget_position') && $playerSettings['widget_position']) {
            $updated = update_option('beyondwords_player_widget_position', $playerSettings['widget_position'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.widget_position has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_skip_button_style') && $playerSettings['skip_button_style']) {
            $updated = update_option('beyondwords_player_skip_button_style', $playerSettings['skip_button_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.skip_button_style has been synced to WordPress', 'success');
            }
        }

        add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Settings synced from the BeyondWords dashboard to WordPress.', 'success');
    }

    /**
     * Sync data from WordPress to BeyondWords REST API.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncToRestApi()
    {
        // Sync WordPress -> REST API
        $data = SettingsUtils::getProjectPayload();
        // wp_die(var_export($data, true));

        // Sync WordPress -> REST API
        if (!empty($data)) {
            $projectResult = $this->apiClient->updateProject($data);
        }

        // wp_die(var_export($projectResult, true));

        $data = SettingsUtils::getPlayerOptionsPayload();
        if (count($data)) {
            $playerOptionsResult = $this->apiClient->updatePlayerSettings($data);
        }

        if (! $projectResult || ! $playerOptionsResult) {
            // Error notice
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', 'error');
        } else {
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Settings synced from WordPress to the BeyondWords dashboard.', 'success');
        }
    }
}
