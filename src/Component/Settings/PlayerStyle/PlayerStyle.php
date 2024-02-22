<?php

declare(strict_types=1);

/**
 * Setting: Player style
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.1.0
 */

namespace Beyondwords\Wordpress\Component\Settings\PlayerStyle;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerStyle setup
 *
 * @since 4.1.0
 */
class PlayerStyle
{
    public const STANDARD = 'standard';

    public const SMALL = 'small';

    public const LARGE = 'large';

    public const VIDEO = 'video';

    /**
     * API client, required to check whether video is enabled or not.
     */
    private $apiClient;

    /**
     * Constructor
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
        add_action('admin_init', array($this, 'addSettingsField'));
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
    }

    /**
     * Add settings field.
     *
     * @since 4.5.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        if (! SettingsUtils::hasApiSettings()) {
            return;
        }

        register_setting(
            'beyondwords',
            'beyondwords_player_style',
            [
                'default' => PlayerStyle::STANDARD,
            ]
        );

        add_settings_field(
            'beyondwords-player-style',
            __('Player style', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'player'
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
        $currentStyle = get_option('beyondwords_player_style', PlayerStyle::STANDARD);
        $playerStyles = $this->getPlayerStyles();
        ?>
        <div class="beyondwords-setting--player--player-style">
            <select name="beyondwords_player_style">
                <?php
                foreach ($playerStyles as $item) {
                    $disabled = isset($item['disabled']) ? $item['disabled'] : false;

                    printf(
                        '<option value="%s" %s %s>%s</option>',
                        esc_attr($item['value']),
                        selected($item['value'], $currentStyle),
                        disabled($disabled, true),
                        esc_html($item['label'])
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
     *
     * @return string[] Associative array of Player styles and labels.
     **/
    public function getPlayerStyles()
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
        $defaultPlayerStyle = get_option('beyondwords_player_style', PlayerStyle::STANDARD);

        if (isset($styles[$defaultPlayerStyle])) {
            $styles[$defaultPlayerStyle]['default'] = true;
        }

        /**
         * Is video enabled for this project?
         * If not, the video <option> will have the "disabled" attribute.
         */
        $projectId = get_option('beyondwords_project_id');
        $playerVideoSettings = $this->apiClient->getVideoSettings($projectId);

        if (
            is_array($playerVideoSettings)
            && array_key_exists('enabled', $playerVideoSettings)
            && $playerVideoSettings['enabled']
        ) {
            unset($styles[PlayerStyle::VIDEO]['disabled']);
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
         * Scheduled for removal in plugin version 5.0.0.
         *
         * @since 4.1.0
         *
         * @deprecated 4.3.0 Replaced with beyondwords_settings_player_styles.
         *
         * @param array $styles Associative array of player styles.
         */
        $styles = apply_filters('beyondwords_player_styles', $styles);

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
         * @since 4.3.0 Renamed from beyondwords_player_styles to beyondwords_settings_player_styles.
         *
         * @param array $styles Associative array of player styles.
         */
        $styles = apply_filters('beyondwords_settings_player_styles', $styles);

        $transientName = sprintf('beyondwords_player_styles[%s]', get_option('beyondwords_project_id'));
        set_transient($transientName, $styles);

        return $styles;
    }

    /**
     * Get the cached Player styles for a project.
     *
     * The transient cache should have been set when the plugin settings were
     * updated.
     *
     * @since 4.1.0
     * @since 4.2.0 Fix: return empty array instead of false
     *
     * @param int $projectId BeyondWords Project ID.
     *
     * @return string[] Associative array of Player styles and labels.
     **/
    public static function getCachedPlayerStyles($projectId = '')
    {
        $transientName = sprintf('beyondwords_player_styles[%s]', $projectId);

        $playerStyles = get_transient($transientName);

        if (! is_array($playerStyles)) {
            return [];
        }

        return $playerStyles;
    }

    /**
     * Register the component scripts.
     *
     * @since 4.1.0
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        if ($hook === 'settings_page_beyondwords') {
            wp_enqueue_script(
                'beyondwords-settings--player-style',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Settings/PlayerStyle/settings.js',
                ['jquery', 'underscore'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );
        }
    }
}
