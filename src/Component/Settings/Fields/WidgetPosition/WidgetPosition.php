<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\WidgetPosition;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * WidgetPosition setup
 *
 * @since 4.8.0
 */
class WidgetPosition
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
            'beyondwords_player_settings',
            'beyondwords_widget_position',
            [
                'default' => 'auto',
            ]
        );

        add_settings_field(
            'beyondwords-widget-position',
            __('Widget position', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'player'
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
        $current = get_option('beyondwords_widget_position');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--widget-position">
            <select name="beyondwords_widget_position">
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
                'value' => 'auto',
                'label' => __('Auto (default)', 'speechkit'),
            ],
            [
                'value' => 'center',
                'label' => __('Center', 'speechkit'),
            ],
            [
                'value' => 'left',
                'label' => __('Left', 'speechkit'),
            ],
            [
                'value' => 'right',
                'label' => __('Right', 'speechkit'),
            ],
        ];

        return $options;
    }
}