<?php

declare(strict_types=1);

/**
 * Setting: BodyVoiceSpeakingRate
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate;

/**
 * BodyVoiceSpeakingRate setup
 *
 * @since 4.8.0
 */
class BodyVoiceSpeakingRate
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_body_voice_speaking_rate';

    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('update_option_' . self::OPTION_NAME, function () {
            add_filter('beyondwords_sync_to_dashboard', function ($fields) {
                $fields[] = self::OPTION_NAME;
                return $fields;
            });
        });
    }

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
            'beyondwords_body_voice_speaking_rate',
            [
                'type'    => 'integer',
                'default' => 100,
            ]
        );

        add_settings_field(
            'beyondwords-body-speaking-rate',
            __('Default body speaking rate', 'speechkit'),
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
        $current = get_option('beyondwords_body_voice_speaking_rate');
        ?>
        <div class="beyondwords-setting__body-speaking-rate">
            <input
                type="range"
                name="beyondwords_body_voice_speaking_rate"
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
                'Choose the default speaking rate for your article body voice.',
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
