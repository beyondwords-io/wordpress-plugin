<?php

declare(strict_types=1);

/**
 * Setting: Player Version
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\PlayerVersion;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PlayerVersion setup
 *
 * @since 4.0.0
 */
class PlayerVersion
{
    /**
     * Version 0: "Legacy"
     */
    public const LEGACY_VERSION = '0';

    /**
     * Version 1: "Latest"
     */
    public const LATEST_VERSION = '1';

    /**
     * API Client.
     *
     * @since 3.0.0
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 3.0.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSettingsField'));
        add_action('add_option_beyondwords_player_version', array($this, 'onAddPlayerVersionOption'), 10, 2);
        add_action('update_option_beyondwords_player_version', array($this, 'onUpdatePlayerVersionOption'), 10, 2);
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
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
            'beyondwords_player_version',
            [
                'default' => PlayerVersion::LATEST_VERSION,
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-player-version',
            __('Player version', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
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
        $playerVersion = get_option('beyondwords_player_version', PlayerVersion::LATEST_VERSION);

        // Remove any "reverted" notifications if player is "Latest"
        if ($playerVersion === PlayerVersion::LATEST_VERSION) {
            delete_transient('beyondwords_player_reverted');
        }

        // Sync Player Version with API
        $playerSettings = $this->apiClient->getPlayerSettings();

        $syncedPlayerMessage = '';

        // TODO refactor - it's a bit messy :/
        // Does the API response have a player_version?
        if ($playerSettings && array_key_exists('player_version', $playerSettings)) {
            $apiPlayerVersion = $playerSettings['player_version'];

            if ($apiPlayerVersion !== $playerVersion) {
                $allPlayerVersions = PlayerVersion::getAllPlayerVersions();

                if (array_key_exists($apiPlayerVersion, $allPlayerVersions)) {
                    $versionLabel = $allPlayerVersions[$apiPlayerVersion];
                    $syncedPlayerMessage = sprintf(
                        /* translators: %s is replaced with the player version from the BeyondWords API */
                        __('The player version on this WordPress site doesn’t match the “%s” player in your BeyondWords account. Saving the WordPress plugin settings will update your BeyondWords account to keep the player version in sync.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                        $versionLabel
                    );
                } else {
                    $syncedPlayerMessage = __('Your BeyondWords account is currently using another player.', 'speechkit'); // phpcs:ignore Generic.Files.LineLength.TooLong
                }
            }
        }
        ?>
        <div class="beyondwords-setting--player--player-version">
            <select name="beyondwords_player_version">
                <?php
                $playerVersions = PlayerVersion::getAllPlayerVersions();

                foreach ($playerVersions as $version => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($version),
                        selected($version, $playerVersion),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </div>
        <?php if ($syncedPlayerMessage) : ?>
            <p class="description" style="color: #b32d2e;">
                <?php echo esc_html($syncedPlayerMessage); ?>
            </p>
        <?php endif; ?>
        <p class="description">
            <?php
            esc_html_e(
                'We sync changes to the player version with your Beyondwords account when these settings are saved.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <?php
            esc_html_e(
                'If caching is enabled you may need to clear the cache to change the player version for existing posts.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * No sanitization is performed in this method. Instead we use it to set a
     * transient notification if the setting changes from "Latest" to "Legacy"
     * to help us provide technical support for player downgrades.
     *
     * @since 4.0.0
     *
     * @param array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        $currentPlayerVersion = get_option('beyondwords_player_version');

        // Hide "reverted" notification if player is "Latest"
        if ($value === PlayerVersion::LATEST_VERSION) {
            delete_transient('beyondwords_player_reverted');
        }

        // Are we downgrading from "Latest" to "Legacy"?
        if ($currentPlayerVersion === PlayerVersion::LATEST_VERSION && $value === PlayerVersion::LEGACY_VERSION) {
            // Display "reverted" notification for 7 days
            set_transient('beyondwords_player_reverted', gmdate(DATE_ISO8601), 604800);
        }

        return $value;
    }

    /**
     * Fires after the beyondwords_player_version option has been added.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @since 4.0.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     *
     * @return void
     */
    public function onAddPlayerVersionOption($option, $value)
    {
        $this->syncPlayerVersionSettingWithApi($value);
    }

    /**
     * Fires after the value of the beyondwords_player_version option has been
     * successfully updated.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @since 4.0.0
     *
     * @param mixed $oldValue The old option value.
     * @param mixed $value    The new option value.
     *
     * @return void
     */
    public function onUpdatePlayerVersionOption($oldValue, $value)
    {
        $this->syncPlayerVersionSettingWithApi($value);
    }

    /**
     * Sync "Player version" setting with BeyondWords API.
     *
     * @since 4.0.0
     *
     * @param mixed $value The new option value.
     *
     * @return void
     */
    public function syncPlayerVersionSettingWithApi($value)
    {
        // Exit if there are no API settings (required to make the API call)
        if (! SettingsUtils::hasApiSettings()) {
            return;
        }

        // Send updated "Player version" value to our API
        $response = $this->apiClient->updatePlayerSettings([
            'player_version' => $value,
        ]);

        if (
            ! is_array($response)
            || ! array_key_exists('player_version', $response)
            || $response['player_version'] !== $value
        ) {
            $errors = get_transient('beyondwords_settings_errors', []);

            if (empty($value)) {
                $errors['Settings/PlayerVersion'] = __(
                    'There was an error syncing the player version with your BeyondWords account. The versions may be different. Sign in to your BeyondWords dashboard and change the player version there if you need them to be in sync.', // phpcs:ignore Generic.Files.LineLength.TooLong
                    'speechkit'
                );
                set_transient('beyondwords_settings_errors', $errors);
            }
        }
    }

    /**
     * Get all player versions.
     *
     * @since 4.0.0
     *
     * @static
     *
     * @return string[] Associative array of player versions and labels.
     **/
    public static function getAllPlayerVersions()
    {
        return [
            PlayerVersion::LATEST_VERSION => __('Latest', 'speechkit'),
            PlayerVersion::LEGACY_VERSION => __('Legacy', 'speechkit'),
        ];
    }
}
