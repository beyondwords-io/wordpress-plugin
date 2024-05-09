<?php

declare(strict_types=1);

/**
 * Setting: Call to action
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\CallToAction;

/**
 * CallToAction setup
 *
 * @since 4.8.0
 */
class CallToAction
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'registerSetting'));
        add_action('admin_init', array($this, 'addSettingsField'));
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function registerSetting()
    {
        register_setting(
            'beyondwords',
            'beyondwords_call_to_action',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        add_settings_field(
            'beyondwords-call-to-action',
            __('Call-to-action', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'player'
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
        $option = get_option('beyondwords_call_to_action');
        ?>
        <input
            type="text"
            name="beyondwords_call_to_action"
            placeholder="<?php esc_attr_e('Listen to this article', 'speechkit'); ?>"
            value="<?php echo esc_attr($option); ?>"
            size="50"
        />
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  4.8.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        $errors = get_transient('beyondwords_settings_errors', []);

        if (empty($value)) {
            $errors['Settings/CallToAction'] = __(
                'Please enter the BeyondWords API key. This can be found in your project settings.',
                'speechkit'
            );
            set_transient('beyondwords_settings_errors', $errors);
        }

        return $value;
    }
}
