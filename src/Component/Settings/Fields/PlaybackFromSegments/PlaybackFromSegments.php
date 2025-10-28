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

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * PlaybackFromSegments
 *
 * @since 5.0.0
 */
class PlaybackFromSegments
{
    /**
     * Default value.
     *
     * @var string
     */
    public const DEFAULT_VALUE = false;

    /**
     * Option name.
     *
     * @var string
     */
    public const OPTION_NAME = 'beyondwords_player_clickable_sections';

    /**
     * Init.
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
        add_filter('option_' . self::OPTION_NAME, 'rest_sanitize_boolean');
    }

    /**
     * Init setting.
     *
     * @since 5.0.0
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
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => self::DEFAULT_VALUE,
            ]
        );

        add_settings_field(
            'beyondwords-playback-from-segments',
            __('Playback from segments', 'speechkit'),
            [self::class, 'render'],
            'beyondwords_player',
            'styling'
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
        $value = get_option(self::OPTION_NAME);
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player-playback-from-segments">
            <label>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME) ?>" value="" />
                <input
                    type="checkbox"
                    id="<?php echo esc_attr(self::OPTION_NAME) ?>"
                    name="<?php echo esc_attr(self::OPTION_NAME) ?>"
                    value="1"
                    <?php checked($value); ?>
                />
                <?php esc_html_e('Allow readers to listen to a paragraph by clicking or tapping on it', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
