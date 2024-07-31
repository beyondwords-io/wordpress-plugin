<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlaybackFromSegments;

/**
 * PlaybackFromSegments setup
 *
 * @since 4.8.0
 */
class PlaybackFromSegments
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
            'beyondwords_player_clickable_sections',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-playback-from-segments',
            __('Playback from segments', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'styling'
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
        $prependExcerpt = get_option('beyondwords_player_clickable_sections', '');
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player-playback-from-segments">
            <label>
                <input
                    type="checkbox"
                    name="beyondwords_player_clickable_sections"
                    value="body"
                    <?php checked($prependExcerpt, 'body'); ?>
                />
                <?php esc_html_e('Allow readers to listen to a paragraph by clicking or tapping on it', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
