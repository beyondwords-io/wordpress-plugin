<?php

declare(strict_types=1);

/**
 * Setting: Call to action
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\CallToAction;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * CallToAction
 *
 * @since 5.0.0
 */
class CallToAction
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_player_call_to_action';

    /**
     * Init.
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
        });
    }

    /**
     * Init setting.
     *
     * @since  5.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_player_settings',
            self::OPTION_NAME,
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
     * @since 5.0.0
     *
     * @return void
     **/
    public function render()
    {
        $option = get_option(self::OPTION_NAME);
        ?>
        <div class="beyondwords-setting__player beyondwords-setting__player--call-to-action">
            <input
                type="text"
                name="<?php echo esc_attr(self::OPTION_NAME) ?>"
                placeholder="<?php esc_attr_e('Listen to this article', 'speechkit'); ?>"
                value="<?php echo esc_attr($option); ?>"
                size="50"
            />
        </div>
        <?php
    }
}
