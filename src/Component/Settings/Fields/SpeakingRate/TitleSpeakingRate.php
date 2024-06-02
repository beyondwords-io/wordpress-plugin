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
 * TitleSpeakingRate setup
 *
 * @since 4.8.0
 */
class TitleSpeakingRate extends SpeakingRate
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
            'beyondwords_title_speaking_rate',
            [
                'default' => '100',
            ]
        );

        add_settings_field(
            'beyondwords-title-speaking-rate',
            __('Default title speaking rate', 'speechkit'),
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
        $current = get_option('beyondwords_title_speaking_rate');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--title-speaking-rate">
            <select
                name="beyondwords_title_speaking_rate"
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
            </select>
        </div>
        <?php
    }
}
