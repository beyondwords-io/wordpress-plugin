<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\TitleSpeakingRate;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * TitleSpeakingRate setup
 *
 * @since 4.8.0
 */
class TitleSpeakingRate
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
        add_action('admin_init', array($this, 'addSettingsField'));
    }

    /**
     * Add settings field.
     *
     * @since 4.5.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        // if (! SettingsUtils::hasApiSettings()) {
        //     return;
        // }

        register_setting(
            'beyondwords',
            'beyondwords_title_speaking_rate',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-title-speaking-rate',
            __('Default title speaking rate', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
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
            <select name="beyondwords_title_speaking_rate">
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
                'value' => 'example-option',
                'label' => 'Example option',
            ]
        ];

        return $options;
    }
}
