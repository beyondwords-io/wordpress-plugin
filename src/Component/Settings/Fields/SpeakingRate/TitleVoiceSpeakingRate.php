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
            'beyondwords_project_title_speaking_rate',
            [
                'default' => '1.0',
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
        $current = get_option('beyondwords_project_title_speaking_rate', '1.0');
        ?>
        <div class="beyondwords-setting__title-speaking-rate">
            <input
                type="range"
                name="beyondwords_project_title_speaking_rate"
                class="beyondwords_speaking_rate"
                min="0.5"
                max="3"
                step="0.05"
                value="<?php echo esc_attr($current); ?>"
                oninput="this.nextElementSibling.value = `${Number(this.value).toFixed(2)}`"
                onload="this.nextElementSibling.value = `${Number(this.value).toFixed(2)}`"
            />
            <output><?php echo esc_html(number_format((float)$current, 2)); ?></output>
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
