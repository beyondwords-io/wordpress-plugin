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

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Sync
 *
 * @since 5.0.0
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
        'beyondwords_project_language_code'             => '[project][language]',
        'beyondwords_project_language_id'               => '[project][language_id]',
        'beyondwords_project_body_voice_id'             => '[project][body][voice][id]',
        'beyondwords_project_body_voice_speaking_rate'  => '[project][body][voice][speaking_rate]',
        'beyondwords_project_title_enabled'             => '[project][title][enabled]',
        'beyondwords_project_title_voice_id'            => '[project][title][voice][id]',
        'beyondwords_project_title_voice_speaking_rate' => '[project][title][voice][speaking_rate]',
        // Video
        'beyondwords_video_enabled' => '[video_settings][enabled]',
    ];

    /**
     * API Client.
     *
     * @since 5.0.0
     */
    private $apiClient;

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
    public function __construct($apiClient)
    {
        $this->apiClient        = $apiClient;
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
        add_action('shutdown', array($this, 'syncToDashboard'), 100);
        add_action('shutdown', array($this, 'syncToWordPress'), 200);
    }

    /**
     * Should we check for syncs on the current page?
     *
     * @since 5.0.0
     */
    public function shouldCheckForSyncs()
    {
        global $pagenow;

        if (! is_admin()) {
            return false;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $page = null;
        if (isset($_GET['page'])) {
            $page = sanitize_key($_GET['page']);
        }
        
        $tab = null;
        if (isset($_GET['tab'])) {
            $tab = sanitize_key($_GET['tab']);
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        
        if (
            $pagenow === 'options-general.php'
            && $page === 'beyondwords'
            && in_array($tab, [ '', 'credentials', 'advanced' ], true)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Sync from the dashboard/BeyondWords REST API to WordPress.
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public function syncToWordPress()
    {
        if (! is_admin()) {
            return;
        }

        $options = apply_filters('beyondwords_sync_to_wordpress', false);

        if (! $options) {
            return;
        }

        $responses = [];
        $responses['project']         = $this->apiClient->getProject();
        $responses['player_settings'] = $this->apiClient->getPlayerSettings();
        $responses['video_settings']  = $this->apiClient->getVideoSettings();

        // Add the language ID to the project settings response.
        $this->setLanguageId($responses);

        // Update WordPress options using the REST API response data.
        $this->updateOptionsFromResponses($responses);

        add_settings_error(
            'beyondwords_settings',
            'beyondwords_settings',
            '<span class="dashicons dashicons-rest-api"></span> Settings synced from the BeyondWords dashboard to WordPress.', // phpcs:ignore Generic.Files.LineLength.TooLong
            'success'
        );
    }

    /**
     * Update WordPress options from REST API responses.
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public function updateOptionsFromResponses($responses)
    {
        foreach (self::MAP_SETTINGS as $optionName => $path) {
            $value = $this->propertyAccessor->getValue($responses, $path);

            if ($value !== null) {
                update_option($optionName, $value, false);
            }
        }
    }

    /**
     * Sync from WordPress to the dashboard/BeyondWords REST API.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * 
     * @since 5.0.0
     * 
     * @return void
     **/
    public function syncToDashboard()
    {
        if (! is_admin()) {
            return;
        }

        $options = apply_filters('beyondwords_sync_to_dashboard', []);
        $options = array_unique($options);

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
            $this->apiClient->updatePlayerSettings($settings['player_settings']);
        }

        // Sync title voice back to API
        if (in_array('beyondwords_project_title_voice_speaking_rate', $options)) {
            $value = $this->propertyAccessor->getValue(
                $settings, 
                self::MAP_SETTINGS['beyondwords_project_title_voice_speaking_rate']
            );

            if ($value !== null) {
                $titleVoiceId = get_option('beyondwords_project_title_voice_id');
                $this->apiClient->updateVoice($titleVoiceId, [
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
                $this->apiClient->updateVoice($bodyVoiceId, [
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

            $this->apiClient->updateProject($settings['project']);
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
        if (empty(self::MAP_SETTINGS[$option_name])) {
            return false;
        }

        // Check the option was updated without error
        $hasErrors = get_settings_errors($option_name);

        return is_array($hasErrors) && count($hasErrors) === 0;
    }

    /**
     * Set the language ID in the project settings.
     *
     * In the REST API query we receive the language code but we need a numeric
     * ID so we make a API call to get the ID and add it to the settings.
     *
     * @since 5.0.0
     *
     * @param array $settings Project settings.
     *
     * @return void
     **/
    public function setLanguageId(&$settings)
    {
        $language_code = $this->propertyAccessor->getValue($settings, '[project][language]');

        if (null === $language_code) {
            $this->propertyAccessor->setValue($settings, '[project][language_id]', '');
        }

        $language  = false;
        $languages = $this->apiClient->getLanguages();

        if (is_array($languages)) {
            $language = array_column(
                $languages,
                null,
                'code'
            )[$language_code] ?? false;
        }

        if (is_array($language) && array_key_exists('id', $language)) {
            $this->propertyAccessor->setValue($settings, '[project][language_id]', $language['id']);
        }
    }
}
