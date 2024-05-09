<?php

declare(strict_types=1);

/**
 * Setting: BodyVoice
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\BodyVoice;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * BodyVoice setup
 *
 * @since 4.8.0
 */
class BodyVoice
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
            'beyondwords_body_voice',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-body-voice',
            __('Which voice do you want to read body content?', 'speechkit'),
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
        $current = get_option('beyondwords_body_voice');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--body-voice">
            <select name="beyondwords_body_voice">
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
