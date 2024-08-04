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
        $submitted = isset($_POST['submit-voices' ]); // phpcs:ignore WordPress.Security.NonceVerification

        if (! $submitted) {
            return;
        }

        // Sync WordPress -> REST API
        $data   = $this->getBodyParams();
        $result = $this->apiClient->updateVoices($data);
        $result = true; // @todo make sync API call on update

        if (! $result) {
            // Error notice
            add_settings_error(
                'beyondwords_settings',
                'beyondwords_settings',
                '<span class="dashicons dashicons-rest-api"></span> Error syncing to the BeyondWords dashboard. The settings may not in sync.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'error'
            );
        }
    }

    /**
     * Get the body params, ready for REST API call.
     *
     * @since 4.8.0
     *
     * @return array REST API body params.
     */
    public function getBodyParams()
    {
        $params = [];

        $params['player_style']      = get_option('beyondwords_player_style');
        $params['theme']             = get_option('beyondwords_player_theme');
        $params['dark_theme']        = get_option('beyondwords_player_dark_theme');
        $params['light_theme']       = get_option('beyondwords_player_light_theme');
        $params['video_theme']       = get_option('beyondwords_player_video_theme');
        $params['call_to_action']    = get_option('beyondwords_player_call_to_action');
        $params['widget_style']      = get_option('beyondwords_player_widget_style');
        $params['widget_position']   = get_option('beyondwords_player_widget_position');
        $params['skip_button_style'] = get_option('beyondwords_player_skip_button_style');

        return array_filter($params);
    }
}
