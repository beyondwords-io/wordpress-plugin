<?php

declare(strict_types=1);

/**
 * Setting: Include excerpt
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   5.0.0 Rename labels from "Prepend excerpt" to "Include excerpt".
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt;

/**
 * IncludeExcerpt setup
 *
 * @since 3.0.0
 */
class IncludeExcerpt
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
     * @since 3.0.0
     * @since 5.0.0 Rename field.
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_content_settings',
            'beyondwords_prepend_excerpt',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        add_settings_field(
            'beyondwords-include-excerpt',
            __('Excerpt', 'speechkit'),
            array($this, 'render'),
            'beyondwords_content',
            'content'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     * @since 4.0.0 Updated label and description
     * @since 5.0.0 Updated label and description
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
                <?php esc_html_e('Include excerpts in audio', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
