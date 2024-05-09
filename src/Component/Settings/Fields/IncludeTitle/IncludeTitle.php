<?php

declare(strict_types=1);

/**
 * Setting: IncludeTitle
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\IncludeTitle;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * IncludeTitle setup
 *
 * @since 3.0.0
 */
class IncludeTitle
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
            'beyondwords_prepend_excerpt',
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
            'beyondwords-include-title',
            __('Title', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'content'
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
        $prependExcerpt = get_option('beyondwords_prepend_excerpt', '');
        ?>
        <div>
            <label>
                <input
                    type="checkbox"
                    name="beyondwords_prepend_excerpt"
                    value="1"
                    <?php checked($prependExcerpt, '1'); ?>
                />
                <?php esc_html_e('Include title in audio', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
