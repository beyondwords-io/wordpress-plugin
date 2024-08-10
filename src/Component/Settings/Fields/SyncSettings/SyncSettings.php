<?php

declare(strict_types=1);

/**
 * Setting: SyncSettings
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SyncSettings;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * SyncSettings setup
 *
 * @since 4.8.0
 */
class SyncSettings
{
    /**
     * Map settings.
     *
     * @since 4.8.0
     */
    public const MAP_SETTINGS = [
        'beyondwords_player_style'             => '[player][player_style]',
        'beyondwords_player_theme'             => '[player][theme]',
        'beyondwords_player_dark_theme'        => '[player][dark_theme]',
        'beyondwords_player_light_theme'       => '[player][light_theme]',
        'beyondwords_player_video_theme'       => '[player][video_theme]',
        'beyondwords_player_call_to_action'    => '[player][call_to_action]',
        'beyondwords_player_widget_style'      => '[player][widget_style]',
        'beyondwords_player_widget_position'   => '[player][widget_position]',
        'beyondwords_player_skip_button_style' => '[player][skip_button_style]',
        'beyondwords_project_language'         => '[project][language_id]',
        'beyondwords_project_body_voice_id'    => '[project][body][voice][id]',
        'beyondwords_project_title_voice_id'   => '[project][title][voice][id]',
    ];

    /**
     * API Client.
     *
     * @since 4.8.0
     */
    private $apiClient;

    /**
     * PropertyAccessor.
     *
     * @var PropertyAccessor
     *
     * @since 4.8.0
     */
    public $propertyAccessor;

    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();
    }

    /**
     * Init.
     *
     * @since 4.8.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since 4.8.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_advanced_settings',
            'beyondwords_sync',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-sync',
            __('Sync settings', 'speechkit'),
            array($this, 'render'),
            'beyondwords_advanced',
            'advanced'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function render()
    {
        ?>
        <div class="beyondwords-setting__sync">
            <button
                name="beyondwords_sync"
                class="button button-secondary"
                value="dashboard_to_wordpress"
            >
                <?php echo esc_attr('Dashboard to WordPress', 'speechkit'); ?>
            </button>
            <p class="description">
                <?php
                esc_html_e('Copy the settings from your BeyondWords account to this WordPress site.', 'speechkit'); // phpcs:ignore Generic.Files.LineLength.TooLong
                ?>
            </p>
            <p class="description description-warning">
                <?php
                esc_html_e('Warning: risk of data loss for the BeyondWords settings in your WordPress database. Proceed with caution.', 'speechkit'); // phpcs:ignore Generic.Files.LineLength.TooLong
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  4.8.0
     *
     * @param array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if ($value === 'dashboard_to_wordpress') {
            $this->syncFromRestApi(true);
        }
    }

    /**
     * Sync data from the BeyondWords REST API to WordPress.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncFromRestApi($overwrite = true)
    {
        $settings = [
            'project' => $this->apiClient->getProject(),
            'player'  => $this->apiClient->getPlayerSettings(),
        ];

        $this->setLanguageId($settings);

        foreach (self::MAP_SETTINGS as $optionName => $path) {
            if (! $overwrite && false !== get_option($optionName)) {
                continue;
            }

            try {
                $value = $this->propertyAccessor->getValue($settings, $path);

                $updated[$optionName] = update_option($optionName, $value, false);

                if ($updated[$optionName]) {
                    add_settings_error(
                        'beyondwords_settings',
                        'beyondwords_settings',
                        '<span class="dashicons dashicons-rest-api"></span> REST API project.' . $path . ' has been synced to WordPress', // phpcs:ignore Generic.Files.LineLength.TooLong
                        'success'
                    );
                }
            } catch (\Exception) {
                add_settings_error(
                    'beyondwords_settings',
                    'beyondwords_settings',
                    '<span class="dashicons dashicons-rest-api"></span> Error syncing API.', // phpcs:ignore Generic.Files.LineLength.TooLong
                    'error'
                );
                return;
            }
        }

        add_settings_error(
            'beyondwords_settings',
            'beyondwords_settings',
            '<span class="dashicons dashicons-rest-api"></span> Settings synced from the BeyondWords dashboard to WordPress.', // phpcs:ignore Generic.Files.LineLength.TooLong
            'success'
        );
    }

    /**
     * Set the language code in the project settings.
     *
     * In the REST API query we receive the language code but we need a numeric
     * ID so we make a API call to get the ID and add it to the settings.
     *
     * @since 4.8.0
     *
     * @param array $settings Project settings.
     *
     * @return void
     **/
    public function setLanguageId(&$settings)
    {
        $languages = $this->apiClient->getLanguages();
        $language  = false;

        if (
            is_array($languages)
            && is_array($settings['project'])
            && array_key_exists('language', $settings['project'])
        ) {
            $language = array_column(
                $languages,
                null,
                'code'
            )[$settings['project']['language']] ?? false;
        }

        if ($language && is_array($language) && array_key_exists('id', $language)) {
            $settings['project']['language_id'] = $language['id'];
        } else {
            $settings['project']['language_id'] = '';
        }
    }
}
