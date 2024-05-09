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
        add_action('admin_init', array($this, 'registerSetting'));
        add_action('admin_init', array($this, 'addSettingsField'));
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function registerSetting()
    {
        // if (! SettingsUtils::hasApiSettings()) {
        //     return;
        // }

        register_setting(
            'beyondwords',
            'beyondwords_playback_controls',
            [
                'default' => '',
            ]
        );
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        add_settings_field(
            'beyondwords-playback-controls',
            __('Playback controls', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'player'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     * @since 4.0.0 Updated label and description
     *
     * @return void
     **/
    public function render()
    {
        $option = get_option('beyondwords_playback_controls', '');
        ?>
        <div>
            <label>
                <input
                    type="checkbox"
                    name="beyondwords_playback_controls"
                    value="1"
                    <?php checked($option, '1'); ?>
                />
                <?php esc_html_e('Skipping', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
