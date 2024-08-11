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
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_player_call_to_action';

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('update_option_' . self::OPTION_NAME, function () {
            add_filter('beyondwords_sync_to_dashboard', function ($fields) {
                $fields[] = self::OPTION_NAME;
                return $fields;
            });
        });
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
            'beyondwords_player_call_to_action',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-player-call-to-action',
            __('Call-to-action', 'speechkit'),
            array($this, 'render'),
            'beyondwords_player',
            'styling'
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
        $option = get_option('beyondwords_player_call_to_action');
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player--call-to-action">
            <input
                type="text"
                name="beyondwords_player_call_to_action"
                placeholder="<?php esc_attr_e('Listen to this article', 'speechkit'); ?>"
                value="<?php echo esc_attr($option); ?>"
                size="50"
            />
        </div>
        <?php
    }
}
