<?php

declare(strict_types=1);

/**
 * Setting: Player style
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.1.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * PlayerStyle
 *
 * @since 4.1.0
 */
class PlayerStyle
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_style';

    public const STANDARD = 'standard';

    public const SMALL = 'small';

    public const LARGE = 'large';

    public const VIDEO = 'video';

    /**
     * API Client.
     *
     * @since 5.0.0
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 5.0.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Constructor
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
     * @since 4.5.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            self::OPTION_NAME,
            [
                'default' => PlayerStyle::STANDARD,
            ]
        );

        add_settings_field(
            'beyondwords-player-style',
            __('Player style', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'styling'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.1.0
     *
     * @return void
     **/
    public function render()
    {
        $value    = get_option(self::OPTION_NAME);
        $selected = PlayerStyle::STANDARD;
        $options  = self::getOptions();

        foreach ($options as $option) {
            if ($option['value'] === $value) {
                $selected = $option['value'];
            }
        }
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player--player-style">
            <select name="<?php echo esc_attr(self::OPTION_NAME) ?>">
                <?php
                foreach ($options as $option) {
                    $disabled = isset($option['disabled']) ? $option['disabled'] : false;

                    printf(
                        '<option value="%s" %s %s>%s</option>',
                        esc_attr($option['value']),
                        selected($option['value'], $selected),
                        disabled($disabled, true),
                        esc_html($option['label'])
                    );
                }
                ?>
            </select>
        </div>
        <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the "playerStyle setting" link */
                esc_html__('The default player style (%s) for the audio player. This can be overridden for each post.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                sprintf(
                    '<a href="https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    esc_html__('playerStyle setting', 'speechkit')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Get all Player styles for the current project.
     *
     * @since 4.1.0
     * @since 5.0.0 Rename beyondwords_player_styles filter to
     *              beyondwords_settings_player_styles.
     *
     * @return string[] Associative array of Player styles and labels.
     **/
    public static function getOptions()
    {
        $styles = [
            PlayerStyle::STANDARD => [
                'value' => PlayerStyle::STANDARD,
                'label' => __('Standard', 'speechkit'),
            ],
            PlayerStyle::SMALL => [
                'value' => PlayerStyle::SMALL,
                'label' => __('Small', 'speechkit'),
            ],
            PlayerStyle::LARGE => [
                'value' => PlayerStyle::LARGE,
                'label' => __('Large', 'speechkit'),
            ],
            PlayerStyle::VIDEO => [
                'value'    => PlayerStyle::VIDEO,
                'label'    => __('Video', 'speechkit'),
                'disabled' => true,
            ],
        ];

        /**
         * Which player style is the default?
         * This is used to preselect the default option.
         */
        $defaultPlayerStyle = get_option(self::OPTION_NAME, PlayerStyle::STANDARD);

        if (isset($styles[$defaultPlayerStyle])) {
            $styles[$defaultPlayerStyle]['default'] = true;
        }

        /**
         * Filters the player styles – the "Player style" `<select>` options
         * presented on the plugin settings page and post edit screens.
         *
         * Each player style is an associative array with the following keys:
         * - string  `label`    The option label e.g. "Standard"
         * - string  `value`    The option value e.g. "standard"
         * - boolean `disabled` (Optional) Is this option disabled?
         * - boolean `default`  (Optional) Is this the default player style, assigned in the plugin settings?
         *
         * @since 4.1.0 Introduced as beyondwords_player_styles.
         * @since 5.0.0 Renamed from beyondwords_player_styles to beyondwords_settings_player_styles.
         *
         * @param array $styles Associative array of player styles.
         */
        $styles = apply_filters('beyondwords_settings_player_styles', $styles);

        if (empty($styles) || ! is_array($styles)) {
            return [];
        }

        /**
         * Is video enabled for this project?
         * If so, we remove the [disabled] attribute from the video <option>.
         * If not, we force a [disabled] attribute on the video <option>.
         */
        if (isset($styles[PlayerStyle::VIDEO]) && is_array($styles[PlayerStyle::VIDEO])) {
            $videoEnabled = get_option('beyondwords_video_enabled');

            if ($videoEnabled) {
                unset($styles[PlayerStyle::VIDEO]['disabled']);
            } else {
                $styles[PlayerStyle::VIDEO]['disabled'] = true;
            }
        }

        return $styles;
    }
}
