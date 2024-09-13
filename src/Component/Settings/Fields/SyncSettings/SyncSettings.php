<?php

declare(strict_types=1);

/**
 * Setting: SyncSettings
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SyncSettings;

/**
 * SyncSettings
 *
 * @since 5.0.0
 */
class SyncSettings
{
    /**
     * Init.
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
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
     * @since 5.0.0
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
     * @since 5.0.0
     *
     * @param array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if ($value === 'dashboard_to_wordpress') {
            add_filter('beyondwords_sync_to_wordpress', '__return_true');
        }
    }
}
