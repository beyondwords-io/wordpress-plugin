<?php

declare(strict_types=1);

/**
 * Setting: Text highlighting
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\TextHighlighting;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * TextHighlighting setup
 *
 * @since 4.8.0
 */
class TextHighlighting
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
        // if (! SettingsUtils::hasApiSettings()) {
        //     return;
        // }

        register_setting(
            'beyondwords',
            'beyondwords_text_highlighting',
            [
                'default' => '',
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
            'beyondwords-text-highlighting',
            __('Text highlighting', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'player'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     * @since 4.0.0 Updated label and description
     *
     * @return void
     **/
    public function render()
    {
        $prependExcerpt = get_option('beyondwords_text_highlighting', '');
        ?>
        <div>
            <label>
                <input
                    type="checkbox"
                    name="beyondwords_text_highlighting"
                    value="1"
                    <?php checked($prependExcerpt, '1'); ?>
                />
                <?php esc_html_e('Highlight the current paragraph during audio playback', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
