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

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * PlayerUI
 *
 * @since 4.0.0
 */
class PlayerUI
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_ui';

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
        add_action('admin_init', array($this, 'addSetting'));
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
        });
    }

    /**
     * Add setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            self::OPTION_NAME,
            [
                'default' => PlayerUI::ENABLED,
            ]
        );

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
        $currentUi = get_option(self::OPTION_NAME, PlayerUI::ENABLED);
        $playerUIs = PlayerUI::getAllPlayerUIs();
        ?>
        <div class="beyondwords-setting__player--player-ui">
            <select name="<?php echo esc_attr(self::OPTION_NAME) ?>">
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
