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

use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\BodyVoiceSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\TitleVoiceSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\BodyVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\TitleVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\Language\Language;

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
        (new Language($this->apiClient))->init();
        (new TitleVoice($this->apiClient))->init();
        (new TitleVoiceSpeakingRate())->init();
        (new BodyVoice($this->apiClient))->init();
        (new BodyVoiceSpeakingRate())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
        add_action('admin_head', array($this, 'maybeSync'), 20);
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
                'Choose the default voices you want for your audio.',
                'speechkit'
            );
            ?>
        </p>
        <p class="description hint">
            <em>
                <?php
                esc_html_e(
                    'To generate audio for existing posts or apply updates to them, you must update the posts.',
                    'speechkit'
                );
                ?>
            </em>
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
        $submitted = isset($_POST['submit-voices']); // phpcs:ignore WordPress.Security.NonceVerification

        if (! $submitted) {
            return;
        }

        // Sync project from WordPress -> REST API
        $projectParams = $this->getProjectRequestParams();
        $projectResult = $this->apiClient->updateProject($projectParams);

        // Sync title voice from WordPress -> REST API
        $titleVoiceId     = get_option('beyondwords_project_title_voice_id');
        $titleVoiceParams = $this->getTitleVoiceRequestParams();
        $titleVoiceResult = $this->apiClient->updateVoice($titleVoiceId, $titleVoiceParams);

        // Sync body voice from WordPress -> REST API
        $bodyVoiceId      = get_option('beyondwords_project_body_voice_id');
        $bodyVoiceParams  = $this->getBodyVoiceRequestParams();
        $bodyVoiceResult  = $this->apiClient->updateVoice($bodyVoiceId, $bodyVoiceParams);

        if (! $projectResult || ! $titleVoiceResult || ! $bodyVoiceResult) {
            // Error notice
            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'error'
            );
        }

        // Save the language code from the response because we use ID on our side.
        if ($projectResult && array_key_exists('language', $projectResult)) {
            update_option('beyondwords_project_language_code', $projectResult['language'], false);
        }
    }

    /**
     * Get the params for project REST API request.
     *
     * @since 4.8.0
     *
     * @return array REST API body params.
     */
    public function getProjectRequestParams()
    {
        $params['language'] = get_option('beyondwords_project_language_code');
        $params['title']['voice']['id'] = get_option('beyondwords_project_title_voice_id');
        $params['body']['voice']['id'] = get_option('beyondwords_project_body_voice_id');

        return array_filter($params);
    }

    /**
     * Get the params for title voice REST API request.
     *
     * @since 4.8.0
     *
     * @return array REST API body params.
     */
    public function getTitleVoiceRequestParams()
    {
        $params['speaking_rate'] = get_option('beyondwords_title_voice_speaking_rate');

        return array_filter($params);
    }

    /**
     * Get the params for body voice REST API request.
     *
     * @since 4.8.0
     *
     * @return array REST API body params.
     */
    public function getBodyVoiceRequestParams()
    {
        $params['speaking_rate'] = get_option('beyondwords_body_voice_speaking_rate');

        return array_filter($params);
    }
}
