<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlaybackControls;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * PlaybackControls
 *
 * @since 5.0.0
 */
class PlaybackControls
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_skip_button_style';

    /**
     * Player Settings docs URL.
     *
     * @var string
     */
    public const PLAYER_SETTINGS_DOCS_URL = 'https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md';

    /**
     * Init.
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
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
            self::OPTION_NAME,
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-player-skip-button-style',
            __('Skip button style', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'playback-controls'
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
        $current = get_option(self::OPTION_NAME);
        ?>
        <div class="beyondwords-setting__player-skip-button-style">
            <input
                type="text"
                name="<?php echo esc_attr(self::OPTION_NAME) ?>"
                placeholder="auto"
                value="<?php echo esc_attr($current); ?>"
                size="20"
            />
            <p class="description" style="max-width: 740px;">
                <?php
                echo wp_kses_post(__('The style of skip buttons to show in the player.', 'speechkit')) . " ";
                echo wp_kses_post(__('Possible values are <code>auto</code>, <code>segments</code>, <code>seconds</code> or <code>audios</code>.', 'speechkit')) . " "; // phpcs:ignore Generic.Files.LineLength.TooLong
                echo wp_kses_post(__('You can specify the number of seconds to skip, e.g. <code>seconds-15</code> or <code>seconds-15-30</code>.', 'speechkit')) . " "; // phpcs:ignore Generic.Files.LineLength.TooLong
                ?>
            </p>
            <p class="description" style="max-width: 740px;">
                <?php
                printf(
                    /* translators: %s is replaced with the link to the Player Settings docs */
                    esc_html__('Refer to the %s docs for more details.', 'speechkit'),
                    sprintf(
                        '<a href="%s" target="_blank" rel="nofollow">%s</a>',
                        esc_url(PlaybackControls::PLAYER_SETTINGS_DOCS_URL),
                        esc_html__('Player Settings', 'speechkit')
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }
}
