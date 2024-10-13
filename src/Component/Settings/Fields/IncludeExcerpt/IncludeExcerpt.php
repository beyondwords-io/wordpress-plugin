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
 * IncludeExcerpt
 *
 * @since 3.0.0
 */
class IncludeExcerpt
{
    /**
     * Default value.
     *
     * @var string
     */
    public const DEFAULT_VALUE = false;

    /**
     * Option name.
     *
     * @var string
     */
    public const OPTION_NAME = 'beyondwords_prepend_excerpt';

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_filter('option_' . self::OPTION_NAME, 'rest_sanitize_boolean');
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
            self::OPTION_NAME,
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => self::DEFAULT_VALUE,
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
        $value = get_option(self::OPTION_NAME);
        ?>
        <div>
            <label>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME); ?>" value="" />
                <input
                    type="checkbox"
                    id="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    name="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    value="1"
                    <?php checked($value); ?>
                />
                <?php esc_html_e('Include excerpts in audio', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
