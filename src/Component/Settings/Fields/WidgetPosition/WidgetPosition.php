<?php

declare(strict_types=1);

/**
 * Setting: Widget position
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\WidgetPosition;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * WidgetPosition
 *
 * @since 5.0.0
 */
defined('ABSPATH') || exit;

class WidgetPosition
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_player_widget_position';

    /**
     * Constructor
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('admin_init', [self::class, 'addSetting']);
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
        });
    }

    /**
     * Add setting.
     *
     * @since 4.5.0
     * @since 6.0.0 Make static.
     *
     * @return void
     */
    public static function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            self::OPTION_NAME,
            [
                'default' => 'auto',
            ]
        );

        add_settings_field(
            'beyondwords-widget-position',
            __('Widget position', 'speechkit'),
            [self::class, 'render'],
            'beyondwords_player',
            'widget'
        );
    }

    /**
     * Render setting field.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function render()
    {
        $current = get_option(self::OPTION_NAME);
        $options = self::getOptions();
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__widget-position">
            <select name="<?php echo esc_attr(self::OPTION_NAME) ?>">
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
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return string[] Associative array of options.
     **/
    public static function getOptions()
    {
        return [
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
    }
}
