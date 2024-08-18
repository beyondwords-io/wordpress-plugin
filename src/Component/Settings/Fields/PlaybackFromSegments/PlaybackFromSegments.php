<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlaybackFromSegments;

/**
 * PlaybackFromSegments setup
 *
 * @since 5.0.0
 */
class PlaybackFromSegments
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_clickable_sections';

    /**
     * Init.
     *
     * @since 5.0.0
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
     * Init setting.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            'beyondwords_player_clickable_sections',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
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
     * @since 5.0.0
     *
     * @return void
     **/
    public function render()
    {
        $value = get_option('beyondwords_player_clickable_sections');
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player-playback-from-segments">
            <label>
                <input type="hidden" name="beyondwords_player_clickable_sections" value="" />
                <input
                    type="checkbox"
                    id="beyondwords_player_clickable_sections"
                    name="beyondwords_player_clickable_sections"
                    value="1"
                    <?php checked($value); ?>
                />
                <?php esc_html_e('Allow readers to listen to a paragraph by clicking or tapping on it', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
