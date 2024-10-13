<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\TextHighlighting;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * TextHighlighting
 *
 * @since 5.0.0
 */
class TextHighlighting
{
    /**
     * Default value.
     *
     * @since 5.0.0
     */
    public const DEFAULT_VALUE = '';

    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_player_highlight_sections';

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
                'type'              => 'string',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => self::DEFAULT_VALUE,
            ]
        );

        add_settings_field(
            'beyondwords-text-highlighting',
            __('Text highlighting', 'speechkit'),
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
        $value      = get_option(self::OPTION_NAME);
        $lightTheme = get_option('beyondwords_player_theme_light');
        $darkTheme  = get_option('beyondwords_player_theme_dark');
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player--text-highlighting">
            <label>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME); ?>"  value="" />
                <input
                    type="checkbox"
                    id="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    name="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    value="1"
                    <?php checked($value, 'body'); ?>
                />
                <?php esc_html_e('Highlight the current paragraph during audio playback', 'speechkit'); ?>
            </label>
        </div>
        <div>
            <h3 class="subheading">Light theme settings</h3>
            <?php
            SettingsUtils::colorInput(
                __('Highlight color', 'speechkit'),
                'beyondwords_player_theme_light[highlight_color]',
                $lightTheme['highlight_color'] ?? '',
            );
            ?>
        </div>
        <div>
            <h3 class="subheading">Dark theme settings</h3>
            <?php
            SettingsUtils::colorInput(
                __('Highlight color', 'speechkit'),
                'beyondwords_player_theme_dark[highlight_color]',
                $darkTheme['highlight_color'] ?? '',
            );
            ?>
        </div>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since 5.0.0
     * @param string $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if ($value) {
            return 'body';
        }

        return '';
    }
}
