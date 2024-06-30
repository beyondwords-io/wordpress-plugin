<?php

declare(strict_types=1);

/**
 * Setting: SyncSettings
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SyncSettings;

/**
 * SyncSettings setup
 *
 * @since 4.0.0
 */
class SyncSettings
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_advanced_sync',
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
     * @since 4.0.0
     *
     * @return void
     **/
    public function render()
    {
        ?>
        <div class="beyondwords-setting__sync">
            <input type="submit" class="button button-secondary" value="<?php echo esc_attr('Dashboard to WordPress', 'speechkit'); ?>" />
            <p class="description">
                <?php
                esc_html_e('Copy the settings from your BeyondWords account to this WordPress site.', 'speechkit');
                ?>
            </p>
            <p class="description description-warning">
                <?php
                esc_html_e('Warning: risk of data loss for the BeyondWords settings in your WordPress database. Proceed with caution.', 'speechkit');
                ?>
            </p>
            <input type="submit" class="button button-secondary" value="<?php echo esc_attr('WordPress to Dashboard', 'speechkit'); ?>" />
            <p class="description">
                <?php
                esc_html_e('Copy the settings from this WordPress site to your BeyondWords account.', 'speechkit');
                ?>
            </p>
            <p class="description description-warning">
                <?php
                esc_html_e('Warning: risk of data loss for the settings in your BeyondWords account. Proceed with caution.', 'speechkit');
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  4.0.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if (empty($value)) {
            $value = [];
        }

        return $value;
    }
}
