<?php

declare(strict_types=1);

/**
 * Setting: API Key
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\ApiKey;

/**
 * ApiKey setup
 *
 * @since 3.0.0
 */
class ApiKey
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('update_option_beyondwords_api_key', function () {
            add_filter('beyondwords_sync_to_wordpress', '__return_true');
        });
    }

    /**
     * Init setting.
     *
     * @since  3.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_credentials_settings',
            'beyondwords_api_key',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-api-key',
            __('API key', 'speechkit'),
            array($this, 'render'),
            'beyondwords_credentials',
            'credentials'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     *
     * @return void
     **/
    public function render()
    {
        $api_key = get_option('beyondwords_api_key');
        ?>
        <input
            type="text"
            id="beyondwords_api_key"
            name="beyondwords_api_key"
            value="<?php echo esc_attr($api_key); ?>"
            size="50"
        />
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  3.0.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        $errors = get_transient('beyondwords_settings_errors', []);

        if (empty($value)) {
            $errors['Settings/ApiKey'] = __(
                'Please enter the BeyondWords API key. This can be found in your project settings.',
                'speechkit'
            );
            set_transient('beyondwords_settings_errors', $errors);
        }

        return $value;
    }
}
