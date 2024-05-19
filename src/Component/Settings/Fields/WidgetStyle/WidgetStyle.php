<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\WidgetStyle;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * WidgetStyle setup
 *
 * @since 4.8.0
 */
class WidgetStyle
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
            'beyondwords_widget_style',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-widget-style',
            __('Widget style', 'speechkit'),
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
        $current = get_option('beyondwords_widget_style');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--widget-style">
            <select name="beyondwords_widget_style">
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
            <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the "widgetStyle setting" link */
                esc_html__('The default player style (%s) for the audio player.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                sprintf(
                    '<a href="https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    esc_html__('widgetStyle setting', 'speechkit')
                )
            );
            ?>
        </p>
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
                'value' => 'standard',
                'label' => __('Standard (default)', 'speechkit'),
            ],
            [
                'value' => 'none',
                'label' => __('None', 'speechkit'),
            ],
            [
                'value' => 'small',
                'label' => __('Small', 'speechkit'),
            ],
            [
                'value' => 'large',
                'label' => __('Large', 'speechkit'),
            ],
            [
                'value' => 'video',
                'label' => __('Video', 'speechkit'),
            ],
        ];

        return $options;
    }
}
