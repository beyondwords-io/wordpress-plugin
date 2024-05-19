<?php

declare(strict_types=1);

/**
 * Setting: BodySpeakingRate
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\BodySpeakingRate;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * BodySpeakingRate setup
 *
 * @since 4.8.0
 */
class BodySpeakingRate
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
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
        $current = get_option('beyondwords_body_speaking_rate');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--body-speaking-rate">
            <select name="beyondwords_body_speaking_rate">
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

    /**
     * Get all options for the current component.
     *
     * @since 4.8.0
     *
     * @return string[] Associative array of options.
     **/
    public function getOptions()
    {
        $options = [
            [
                'value' => '50',
                'label' => '50%',
            ],
            [
                'value' => '100',
                'label' => '100%',
            ],
            [
                'value' => '200',
                'label' => '200%',
            ],
        ];

        return $options;
    }
}
