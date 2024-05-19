<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlaybackControls;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlaybackControls setup
 *
 * @since 4.8.0
 */
class PlaybackControls
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            'beyondwords_playback_controls_skipping',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-playback-controls-skipping',
            __('Skipping', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'playback-controls'
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
        $current = get_option('beyondwords_playback_controls_skipping');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting--playback-controls-skipping">
            <select name="beyondwords_playback_controls_skipping">
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
                'value' => 'audo',
                'label' => __('Audo (default)', 'speechkit'),
            ],
            [
                'value' => 'segments',
                'label' => __('Segments', 'speechkit'),
            ],
            [
                'value' => 'seconds',
                'label' => __('Seconds', 'speechkit'),
            ],
        ];

        return $options;
    }
}
