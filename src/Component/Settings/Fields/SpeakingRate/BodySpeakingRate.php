<?php

declare(strict_types=1);

/**
 * Setting: BodySpeakingRate
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate;

/**
 * BodySpeakingRate setup
 *
 * @since 4.8.0
 */
class BodySpeakingRate extends SpeakingRate
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
            'beyondwords_body_speaking_rate',
            [
                'default' => '100',
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
        $current = get_option('beyondwords_body_speaking_rate', '1.0');
        // $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting__body-speaking-rate">
            <input
                type="range"
                name="beyondwords_body_speaking_rate"
                class="beyondwords_speaking_rate"
                min="0.5"
                max="3"
                step="0.05"
                value="<?php echo esc_attr($current); ?>"
                oninput="this.nextElementSibling.value = `${Number(this.value).toFixed(1)}`"
                onload="this.nextElementSibling.value = `${Number(this.value).toFixed(1)}`"
            />
            <output><?php echo esc_html(number_format((float)$current, 1)); ?></output>
            <!-- <select
                name="beyondwords_body_speaking_rate"
                class="beyondwords_speaking_rate"
                style="width: 100px;"
            >
                <?php
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option['value']),
                        selected($option['value'], $current),
                        esc_html($option['label'])
                    );
                }
                ?>
            </select> -->
        </div>
        <?php
    }
}
