<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\TitleVoice;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * TitleVoice setup
 *
 * @since 4.8.0
 */
class TitleVoice
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
            'beyondwords_title_voice',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-title-voice',
            __('Which voice do you want to read titles?', 'speechkit'),
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
        $current = get_option('beyondwords_title_voice');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--title-voice">
            <select name="beyondwords_title_voice">
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
                'value' => 'amy',
                'label' => 'Amy',
            ]
        ];

        return $options;
    }
}
