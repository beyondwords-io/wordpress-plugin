<?php

declare(strict_types=1);

/**
 * Setting: Sync
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Sync setup
 *
 * @since 4.8.0
 */
class Sync
{
    /**
     * Map settings.
     *
     * @since 4.8.0
     */
    public const MAP_SETTINGS = [
        // Player
        'beyondwords_player_style'              => '[player][player_style]',
        'beyondwords_player_theme'              => '[player][theme]',
        'beyondwords_player_dark_theme'         => '[player][dark_theme]',
        'beyondwords_player_light_theme'        => '[player][light_theme]',
        'beyondwords_player_video_theme'        => '[player][video_theme]',
        'beyondwords_player_call_to_action'     => '[player][call_to_action]',
        'beyondwords_player_widget_style'       => '[player][widget_style]',
        'beyondwords_player_widget_position'    => '[player][widget_position]',
        'beyondwords_player_skip_button_style'  => '[player][skip_button_style]',
        // Project
        'beyondwords_include_title'             => '[project][title][enabled]',
        'beyondwords_project_language_code'     => '[project][language]',
        'beyondwords_project_language_id'       => '[project][language_id]',
        'beyondwords_project_body_voice_id'     => '[project][body][voice][id]',
        'beyondwords_project_title_voice_id'    => '[project][title][voice][id]',
        // Title Voice
        'beyondwords_title_voice_speaking_rate' => '[title_voice][speaking_rate]',
        // Body Voice
        'beyondwords_body_voice_speaking_rate'  => '[body_voice][speaking_rate]',
    ];

    /**
     * API Client.
     *
     * @since 4.8.0
     */
    private $apiClient;

    /**
     * PropertyAccessor.
     *
     * @var PropertyAccessor
     *
     * @since 4.8.0
     */
    public $propertyAccessor;

    /**
     * Constructor.
     *
     * @since 4.8.0
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
     * @since 4.8.0
     */
    public function init()
    {
        // @todo put this behind another check so we only Sync on settings pages.
        add_action('shutdown', array($this, 'syncToDashboard'), 100);
        add_action('shutdown', array($this, 'syncToWordPress'), 200);
    }

    /**
     * Sync from the dashboard/BeyondWords REST API to WordPress.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncToWordPress()
    {
        $beyondwordsApiSync = apply_filters('beyondwords_sync_to_wordpress', false);

        if (! $beyondwordsApiSync) {
            return;
        }

        $settings = [
            'project'     => $this->apiClient->getProject(),
            'player'      => $this->apiClient->getPlayerSettings(),
            // todo move this to later on because we don't have the voice IDs yet
            // 'title_voice' => $this->apiClient->getVoice(get_option('beyondwords_project_title_voice_id')),
            // 'body_voice'  => $this->apiClient->getVoice(get_option('beyondwords_project_body_voice_id')),
        ];

        $this->setLanguageId($settings);

        foreach (self::MAP_SETTINGS as $optionName => $path) {
            $value = $this->propertyAccessor->getValue($settings, $path);
            if ($value !== null) {
                update_option($optionName, $value, false);
            }
        }

        // Get the voice sopeaking rates now that the Voice IDs have been saved.
        $settings = [
            'title_voice' => $this->apiClient->getVoice(get_option('beyondwords_project_title_voice_id')),
            'body_voice'  => $this->apiClient->getVoice(get_option('beyondwords_project_body_voice_id')),
        ];

        foreach (self::MAP_SETTINGS as $optionName => $path) {
            $value = $this->propertyAccessor->getValue($settings, $path);
            if ($value !== null) {
                update_option($optionName, $value, false);
            }
        }

        add_settings_error(
            'beyondwords_settings',
            'beyondwords_settings',
            '<span class="dashicons dashicons-rest-api"></span> Settings synced from the BeyondWords dashboard to WordPress.', // phpcs:ignore Generic.Files.LineLength.TooLong
            'success'
        );
    }

    /**
     * Sync from WordPress to the dashboard/BeyondWords REST API.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncToDashboard()
    {
        if (!is_admin()) {
            return;
        }

        $beyondwordsApiSync = apply_filters('beyondwords_sync_to_dashboard', []);

        if (empty($beyondwordsApiSync)) {
            return;
        }

        $settings = [];

        foreach ($beyondwordsApiSync as $option) {
            if (empty(self::MAP_SETTINGS[$option])) {
                continue;
            }

            $this->propertyAccessor->setValue($settings, self::MAP_SETTINGS[$option], get_option($option));
        }

        if (isset($settings['player'])) {
            $this->apiClient->updatePlayerSettings($settings['player']);
        }

        if (isset($settings['project'])) {
            $this->apiClient->updateProject($settings['project']);
        }

        if (isset($settings['title_voice'])) {
            $titleVoiceId = get_option('beyondwords_project_title_voice_id');
            $this->apiClient->updateVoice($titleVoiceId, $settings['title_voice']);
        }

        if (isset($settings['body_voice'])) {
            $bodyVoiceId = get_option('beyondwords_project_body_voice_id');
            $this->apiClient->updateVoice($bodyVoiceId, $settings['body_voice']);
        }
    }

    /**
     * Set the language ID in the project settings.
     *
     * In the REST API query we receive the language code but we need a numeric
     * ID so we make a API call to get the ID and add it to the settings.
     *
     * @since 4.8.0
     *
     * @param array $settings Project settings.
     *
     * @return void
     **/
    public function setLanguageId(&$settings)
    {
        $languages = $this->apiClient->getLanguages();
        $language  = false;

        if (
            is_array($languages)
            && is_array($settings['project'])
            && array_key_exists('language', $settings['project'])
        ) {
            $language = array_column(
                $languages,
                null,
                'code'
            )[$settings['project']['language']] ?? false;
        }

        if ($language && is_array($language) && array_key_exists('id', $language)) {
            $settings['project']['language_id'] = $language['id'];
        } else {
            $settings['project']['language_id'] = '';
        }
    }
}
