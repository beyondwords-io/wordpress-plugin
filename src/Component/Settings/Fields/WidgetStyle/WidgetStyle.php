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

/**
 * WidgetStyle setup
 *
 * @since 4.8.0
 */
class WidgetStyle
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_widget_style';

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
            'beyondwords_player_settings',
            'beyondwords_player_widget_style',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-widget-style',
            __('Widget style', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'widget'
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
        $current = get_option('beyondwords_player_widget_style');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__widget-style">
            <select name="beyondwords_player_widget_style">
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
                esc_html__('The style of widget to display at the bottom of the page once the user scrolls past the inline player. See %s.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
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
                'label' => __('Standard', 'speechkit'),
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
