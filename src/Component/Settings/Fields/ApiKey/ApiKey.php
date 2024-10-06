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
 * ApiKey
 *
 * @since 3.0.0
 */
class ApiKey
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_api_key';

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
     * @since  3.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_credentials_settings',
            self::OPTION_NAME,
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
        $value = get_option(self::OPTION_NAME);
        ?>
        <input
            type="text"
            id="<?php echo esc_attr(self::OPTION_NAME); ?>"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>"
            value="<?php echo esc_attr($value); ?>"
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
        set_transient('beyondwords_validate_api_connection', true, 30);

        $errors = get_transient('beyondwords_settings_errors');

        if (empty($errors)) {
            $errors = [];
        }

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
