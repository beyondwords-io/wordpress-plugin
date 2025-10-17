<?php

declare(strict_types=1);

/**
 * Setting: Title voice speaking rate
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * TitleVoiceSpeakingRate
 *
 * @since 5.0.0
 */
class TitleVoiceSpeakingRate
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_project_title_voice_speaking_rate';

    /**
     * Constructor
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('admin_init', [self::class, 'addSetting']);
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
        });
    }

    /**
     * Add setting.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     */
    public static function addSetting()
    {
        register_setting(
            'beyondwords_voices_settings',
            self::OPTION_NAME,
            [
                'type'    => 'integer',
                'default' => 100,
            ]
        );

        add_settings_field(
            'beyondwords-title-speaking-rate',
            __('Title voice speaking rate', 'speechkit'),
            [self::class, 'render'],
            'beyondwords_voices',
            'voices'
        );
    }

    /**
     * Render setting field.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function render()
    {
        $current = get_option(self::OPTION_NAME);
        ?>
        <div class="beyondwords-setting__title-speaking-rate">
            <input
                type="range"
                id="<?php echo esc_attr(self::OPTION_NAME) ?>"
                name="<?php echo esc_attr(self::OPTION_NAME) ?>"
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
