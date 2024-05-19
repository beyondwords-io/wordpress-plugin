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
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since  4.8.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            'beyondwords_call_to_action',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-call-to-action',
            __('Call-to-action', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
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
}
