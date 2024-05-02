<?php

declare(strict_types=1);

/**
 * Setting: Player UI
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerUI setup
 *
 * @since 4.0.0
 */
class PlayerUI
{
    public const ENABLED  = 'enabled';

    public const HEADLESS = 'headless';

    public const DISABLED = 'disabled';

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
     * Register setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function registerSetting()
    {
        if (! SettingsUtils::hasApiSettings()) {
            return;
        }

        register_setting(
            'beyondwords',
            'beyondwords_player_ui',
            [
                'default' => PlayerUI::ENABLED,
            ]
        );
    }

    /**
     * Add settings field.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        add_settings_field(
            'beyondwords-player-ui',
            __('Player UI', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'player'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.0.0
     *
     * @return void
     **/
    public function render()
    {
        $currentUi = get_option('beyondwords_player_ui', PlayerUI::ENABLED);
        $playerUIs = PlayerUI::getAllPlayerUIs();

        ?>
        <div class="beyondwords-setting--player--player-ui">
            <select name="beyondwords_player_ui">
                <?php
                foreach ($playerUIs as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($value, $currentUi),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>
        <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the "headless mode" link */
                esc_html__('Enable or disable the player, or set it to %s.', 'speechkit'),
                sprintf(
                    '<a href="https://github.com/beyondwords-io/player/blob/gh-pages/doc/building-your-own-ui.md" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    esc_html__('headless mode', 'speechkit')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Get all Player UIs.
     *
     * @since 4.0.0
     *
     * @static
     *
     * @return string[] Associative array of Player UIs and labels.
     **/
    public static function getAllPlayerUIs()
    {
        return [
            PlayerUI::ENABLED  => __('Enabled', 'speechkit'),
            PlayerUI::HEADLESS => __('Headless', 'speechkit'),
            PlayerUI::DISABLED => __('Disabled', 'speechkit'),
        ];
    }
}
