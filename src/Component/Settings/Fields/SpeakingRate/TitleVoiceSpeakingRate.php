<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate;

/**
 * TitleVoiceSpeakingRate setup
 *
 * @since 4.8.0
 */
class TitleVoiceSpeakingRate extends SpeakingRate
{
    /**
     * Add setting.
     *
     * @since 4.5.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_voices_settings',
            'beyondwords_title_voice_speaking_rate',
            [
                'default' => '100',
            ]
        );

        add_settings_field(
            'beyondwords-title-speaking-rate',
            __('Title voice speaking rate', 'speechkit'),
            array($this, 'render'),
            'beyondwords_voices',
            'voices'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function render()
    {
        $current = get_option('beyondwords_title_voice_speaking_rate', '100');
        ?>
        <div class="beyondwords-setting__title-speaking-rate">
            <input
                type="range"
                name="beyondwords_title_voice_speaking_rate"
                class="beyondwords_speaking_rate"
                min="50"
                max="200"
                step="1"
                value="<?php echo esc_attr($current); ?>"
                oninput="this.nextElementSibling.value = `${this.value}%`"
                onload="this.nextElementSibling.value = `${this.value}%`"
            />
            <output><?php echo esc_html($current); ?>%</output>
        </div>
        <p class="description">
            <?php
            esc_html_e(
                'Choose the default speaking rate for your title voice.',
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
