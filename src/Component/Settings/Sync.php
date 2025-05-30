<?php

declare(strict_types=1);

/**
 * Setting: Sync
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings;

use Beyondwords\Wordpress\Core\ApiClient;
use Beyondwords\Wordpress\Core\Environment;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Sync
 *
 * @since 5.0.0
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class Sync
{
    /**
     * Map settings.
     *
     * @since 5.0.0
     */
    public const MAP_SETTINGS = [
        // Player
        'beyondwords_player_style'              => '[player_settings][player_style]',
        'beyondwords_player_theme'              => '[player_settings][theme]',
        'beyondwords_player_theme_dark'         => '[player_settings][dark_theme]',
        'beyondwords_player_theme_light'        => '[player_settings][light_theme]',
        'beyondwords_player_theme_video'        => '[player_settings][video_theme]',
        'beyondwords_player_call_to_action'     => '[player_settings][call_to_action]',
        'beyondwords_player_widget_style'       => '[player_settings][widget_style]',
        'beyondwords_player_widget_position'    => '[player_settings][widget_position]',
        'beyondwords_player_skip_button_style'  => '[player_settings][skip_button_style]',
        'beyondwords_player_clickable_sections' => '[player_settings][segment_playback_enabled]',
        // Project
        'beyondwords_project_auto_publish_enabled'      => '[project][auto_publish_enabled]',
        'beyondwords_project_language_code'             => '[project][language]',
        'beyondwords_project_body_voice_id'             => '[project][body][voice][id]',
        'beyondwords_project_body_voice_speaking_rate'  => '[project][body][voice][speaking_rate]',
        'beyondwords_project_title_enabled'             => '[project][title][enabled]',
        'beyondwords_project_title_voice_id'            => '[project][title][voice][id]',
        'beyondwords_project_title_voice_speaking_rate' => '[project][title][voice][speaking_rate]',
        // Video
        'beyondwords_video_enabled' => '[video_settings][enabled]',
    ];

    /**
     * PropertyAccessor.
     *
     * @var PropertyAccessor
     *
     * @since 5.0.0
     */
    public $propertyAccessor;

    /**
     * Constructor.
     *
     * @since 5.0.0
     */
    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();
    }

    /**
     * Init.
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('load-settings_page_beyondwords', array($this, 'syncToWordPress'), 30);

        if (Environment::hasAutoSyncSettings()) {
            add_action('load-settings_page_beyondwords', array($this, 'scheduleSyncs'), 20);
            add_action('shutdown', array($this, 'syncToDashboard'));
        }
    }

    /**
     * Should we schedule a sync on the current settings tab?
     *
     * @since 5.0.0
     * @since 5.2.0 Remove API creds validation.
     *
     * @return void
     */
    public function scheduleSyncs()
    {
        $tab       = Settings::getActiveTab();
        $endpoints = [];

        switch ($tab) {
            case 'content':
                $endpoints = ['project'];
                break;
            case 'voices':
                $endpoints = ['project'];
                break;
            case 'player':
                $endpoints = ['player_settings', 'video_settings'];
                break;
        }

        if (count($endpoints)) {
            wp_cache_set('beyondwords_sync_to_wordpress', $endpoints, 'beyondwords', 60);
        }
    }

    /**
     * Sync from the dashboard/BeyondWords REST API to WordPress.
     *
     * @since 5.0.0 Introduced.
     * @since 5.4.0 Stop saving language ID – we only need the ISO code now.
     *
     * @return void
     **/
    public function syncToWordPress()
    {
        $sync_to_wordpress = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');
        wp_cache_delete('beyondwords_sync_to_wordpress', 'beyondwords');

        if (empty($sync_to_wordpress) || ! is_array($sync_to_wordpress)) {
            return;
        }

        $responses = [];

        if (! empty(array_intersect($sync_to_wordpress, ['all', 'project']))) {
            $project = ApiClient::getProject();
            if (! empty($project)) {
                $responses['project'] = $project;
            }
        }

        if (! empty(array_intersect($sync_to_wordpress, ['all', 'player_settings']))) {
            $player_settings = ApiClient::getPlayerSettings();
            if (! empty($player_settings)) {
                $responses['player_settings'] = $player_settings;
            }
        }

        if (! empty(array_intersect($sync_to_wordpress, ['all', 'video_settings']))) {
            $video_settings = ApiClient::getVideoSettings();
            if (! empty($video_settings)) {
                $responses['video_settings'] = $video_settings;
            }
        }

        // Update WordPress options using the REST API response data.
        $this->updateOptionsFromResponses($responses);
    }

    /**
     * Update WordPress options from REST API responses.
     *
     * @since 5.0.0
     *
     * @return boolean
     **/
    public function updateOptionsFromResponses($responses)
    {
        if (empty($responses)) {
            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-controls-volumeon"></span> Unexpected BeyondWords REST API response.',
                'error'
            );
            return false;
        }

        $updated = false;

        foreach (self::MAP_SETTINGS as $optionName => $path) {
            $value = $this->propertyAccessor->getValue($responses, $path);

            if ($value !== null) {
                update_option($optionName, $value, false);
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Sync from WordPress to the dashboard/BeyondWords REST API.
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public function syncToDashboard()
    {
        $options = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        wp_cache_delete('beyondwords_sync_to_dashboard', 'beyondwords');

        if (empty($options) || ! is_array($options)) {
            return;
        }

        $settings = [];

        foreach ($options as $option) {
            if ($this->shouldSyncOptionToDashboard($option)) {
                $this->propertyAccessor->setValue(
                    $settings,
                    self::MAP_SETTINGS[$option],
                    get_option($option)
                );
            }
        }

        // Sync player settings back to API
        if (isset($settings['player_settings'])) {
            ApiClient::updatePlayerSettings($settings['player_settings']);

            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Player settings synced from WordPress to the BeyondWords dashboard.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'success'
            );
        }

        // Sync title voice back to API
        if (in_array('beyondwords_project_title_voice_speaking_rate', $options)) {
            $value = $this->propertyAccessor->getValue(
                $settings,
                self::MAP_SETTINGS['beyondwords_project_title_voice_speaking_rate']
            );

            if ($value !== null) {
                $titleVoiceId = get_option('beyondwords_project_title_voice_id');
                ApiClient::updateVoice($titleVoiceId, [
                    'speaking_rate' => (int)$value,
                ]);
            }
        }

        // Sync body voice back to API
        if (in_array('beyondwords_project_body_voice_speaking_rate', $options)) {
            $value = $this->propertyAccessor->getValue(
                $settings,
                self::MAP_SETTINGS['beyondwords_project_body_voice_speaking_rate']
            );

            if ($value !== null) {
                $bodyVoiceId = get_option('beyondwords_project_body_voice_id');
                ApiClient::updateVoice($bodyVoiceId, [
                    'speaking_rate' => (int)$value,
                ]);
            }
        }

        // Sync project settings back to API
        if (isset($settings['project'])) {
            // Don't send speaking rates back to /project endpoint
            $titleSpeakingRate = $this->propertyAccessor->getValue(
                $settings,
                self::MAP_SETTINGS['beyondwords_project_title_voice_speaking_rate']
            );
            if ($titleSpeakingRate) {
                unset($settings['project']['title']['voice']['speaking_rate']);
            }

            // Don't send speaking rates back to /project endpoint
            $bodySpeakingRate = $this->propertyAccessor->getValue(
                $settings,
                self::MAP_SETTINGS['beyondwords_project_body_voice_speaking_rate']
            );
            if ($bodySpeakingRate) {
                unset($settings['project']['body']['voice']['speaking_rate']);
            }

            ApiClient::updateProject($settings['project']);

            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Project settings synced from WordPress to the BeyondWords dashboard.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'success'
            );
        }
    }

    /**
     * Should we sync this option to the dashboard?
     *
     * @since 5.0.0
     *
     * @param string $option_name Option name.
     *
     * @return void
     **/
    public function shouldSyncOptionToDashboard($option_name)
    {
        if (! array_key_exists($option_name, self::MAP_SETTINGS)) {
            return false;
        }

        // Check the option was updated without error
        $hasErrors = get_settings_errors($option_name);

        return is_array($hasErrors) && count($hasErrors) === 0;
    }

    /**
     * Sync an option to the WordPress dashboard.
     *
     * Note that this DOES NOT make the API call, it instead flags the field
     * as one to sync so that we can group fields and send them in a single
     * request to the BeyondWords REST API.
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public static function syncOptionToDashboard($optionName)
    {
        $options = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        if (! is_array($options)) {
            $options = [];
        }

        $options[] = $optionName;
        $options   = array_unique($options);

        wp_cache_set('beyondwords_sync_to_dashboard', $options, 'beyondwords', 60);
    }
}
