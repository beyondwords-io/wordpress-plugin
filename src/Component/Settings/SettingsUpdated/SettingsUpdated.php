<?php

declare(strict_types=1);

/**
 * Setting: SettingsUpdated
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\SettingsUpdated;

/**
 * SettingsUpdated setup
 *
 * @since 4.0.0
 */
class SettingsUpdated
{
    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSettingsField'));
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
        register_setting(
            'beyondwords',
            'beyondwords_settings_updated',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-settings-updated',
            __('Settings Updated', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'basic',
            [
                'class' => 'hidden'
            ]
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
        $settingsUpdated = get_option('beyondwords_settings_updated');
        ?>
        <input
            type="hidden"
            name="beyondwords_settings_updated"
            value="<?php echo esc_attr($settingsUpdated); ?>"
        />
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * This ignores the value passed as a param and instead always uses
     * an ISO8601 date string.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @since  4.0.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        $date = gmdate(DATE_ISO8601);

        $user = wp_get_current_user();

        if ($user instanceof \WP_User) {
            return $user->user_login . '@' . $date;
        }

        return $date;
    }
}
