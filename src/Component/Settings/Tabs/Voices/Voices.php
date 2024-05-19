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

use Beyondwords\Wordpress\Component\Settings\Fields\BodySpeakingRate\BodySpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\BodyVoice\BodyVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\TitleSpeakingRate\TitleSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\TitleVoice\TitleVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\DefaultLanguage\DefaultLanguage;

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
        (new TitleVoice())->init();
        (new TitleSpeakingRate())->init();
        (new BodyVoice())->init();
        (new BodySpeakingRate())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
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
            array($this, 'sectionCallback'),
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
}
