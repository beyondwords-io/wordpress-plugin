<?php

declare(strict_types=1);

/**
 * Setting: Player colors
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlayerColors;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerColors setup
 *
 * @since 4.8.0
 */
class PlayerColors
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addPlayerThemeSetting'));
        add_action('admin_init', array($this, 'addPlayerColorsSetting'));
    }

    /**
     * Init "Player color" setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addPlayerThemeSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            'beyondwords_player_theme',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-player-theme',
            __('Player theme', 'speechkit'),
            array($this, 'renderPlayerThemeSetting'),
            'beyondwords_player',
            'player',
            [
                'class' => 'beyondwords-settings__player-field-toggle'
            ]
        );
    }

    /**
     * Init "Player colors" setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addPlayerColorsSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            'beyondwords_player_light_theme',
            [
                'default' => [
                    'background_color' => '#F5F5F5',
                    'icon_color'       => '#000',
                    'text_color'       => '#111',
                    'highlight_color'  => '#EEE',
                ],
            ]
        );

        register_setting(
            'beyondwords_player_settings',
            'beyondwords_player_dark_theme',
            [
                'default' => [
                    'background_color' => '#F5F5F5',
                    'icon_color'       => '#000',
                    'text_color'       => '#111',
                    'highlight_color'  => '#EEE',
                ],
            ]
        );

        register_setting(
            'beyondwords_player_settings',
            'beyondwords_player_video_theme',
            [
                'default' => [
                    'background_color' => '#000',
                    'icon_color'       => '#FFF',
                    'text_color'       => '#FFF',
                ],
            ]
        );

        add_settings_field(
            'beyondwords-player-colors',
            __('Player colors', 'speechkit'),
            array($this, 'renderPlayerColorsSetting'),
            'beyondwords_player',
            'player',
            [
                'class' => 'beyondwords-settings__player-field-toggle'
            ]
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     *
     * @return string
     **/
    public function renderPlayerThemeSetting()
    {
        $current = get_option('beyondwords_player_theme');
        $options = $this->getPlayerThemeOptions();
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player--player-colors">
            <select name="beyondwords_player_theme">
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
    public function getPlayerThemeOptions()
    {
        $options = [
            [
                'value' => 'light',
                'label' => 'Light (default)',
            ],
            [
                'value' => 'dark',
                'label' => 'Dark',
            ],
            [
                'value' => 'auto',
                'label' => 'Auto',
            ],
        ];

        return $options;
    }
    /**
     * Render setting field.
     *
     * @since 3.0.0
     *
     * @return string
     **/
    public function renderPlayerColorsSetting()
    {
        $lightTheme = get_option('beyondwords_player_light_theme');
        $darkTheme  = get_option('beyondwords_player_dark_theme');
        $videoTheme = get_option('beyondwords_player_video_theme');

        echo $this->playerColorsTable(
            __('Light theme settings'),
            'beyondwords_player_light_theme',
            $lightTheme,
        );

        echo $this->playerColorsTable(
            __('Dark theme settings'),
            'beyondwords_player_dark_theme',
            $darkTheme,
        );

        echo $this->playerColorsTable(
            __('Video theme settings'),
            'beyondwords_player_video_theme',
            $videoTheme,
        );
    }

    /**
     * A player colors table.
     *
     * @since 4.8.0
     *
     * @return string
     **/
    public function playerColorsTable($title, $name, $value)
    {
        ob_start();
        ?>
        <h3 class="subheading">
            <?php echo esc_html($title); ?>
        </h3>
        <div class="color-pickers">
            <div class="row">
                <?php
                echo SettingsUtils::colorInput(
                    __('Background'),
                    sprintf('%s[background_color]', $name),
                    $value['background_color'] ?? ''
                );
                ?>
            </div>
            <div class="row">
                <?php
                echo SettingsUtils::colorInput(
                    __('Icons'),
                    sprintf('%s[icon_color]', $name),
                    $value['icon_color'] ?? ''
                );
                ?>
            </div>
            <div class="row">
                <?php
                echo SettingsUtils::colorInput(
                    __('Text color'),
                    sprintf('%s[text_color]', $name),
                    $value['text_color'] ?? ''
                );
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
