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

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

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
     * @since 3.0.0
     * @since 5.2.0 Remove creds validation from here.
     *
     * @param array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if (empty($value)) {
            SettingsUtils::addSettingsErrorMessage(
                __(
                    'Please enter the BeyondWords API key. This can be found in your project settings.',
                    'speechkit'
                ),
                'Settings/ApiKey'
            );
        }

        return $value;
    }
}
