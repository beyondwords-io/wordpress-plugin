<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Voices
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Voices;

use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\BodyVoiceSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\TitleVoiceSpeakingRate;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\BodyVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\Voice\TitleVoice;
use Beyondwords\Wordpress\Component\Settings\Fields\Language\Language;

/**
 * "Voices" settings tab
 * @since 5.0.0
 */
class Voices
{
    /**
     * Init
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        Language::init();
        TitleVoice::init();
        TitleVoiceSpeakingRate::init();
        BodyVoice::init();
        BodyVoiceSpeakingRate::init();

        add_action('admin_init', [self::class, 'addSettingsSection'], 5);
    }

    /**
     * Add Settings sections.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function addSettingsSection()
    {
        add_settings_section(
            'voices',
            __('Voices', 'speechkit'),
            [self::class, 'sectionCallback'],
            'beyondwords_voices',
        );
    }

    /**
     * Section callback
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function sectionCallback()
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
}
