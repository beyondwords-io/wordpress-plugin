<?php

declare(strict_types=1);

/**
 * Setting: Auto-publish
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.1.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\AutoPublish;

use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * AutoPublish
 *
 * @since 5.1.0
 */
class AutoPublish
{
    /**
     * Default value.
     *
     * @var string
     */
    public const DEFAULT_VALUE = true;

    /**
     * Option name.
     *
     * @var string
     */
    public const OPTION_NAME = 'beyondwords_project_auto_publish_enabled';

    /**
     * Init.
     *
     * @since 5.1.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
        });
        add_filter('option_' . self::OPTION_NAME, 'rest_sanitize_boolean');
    }

    /**
     * Init setting.
     *
     * @since 5.1.0
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
            'beyondwords-auto-publish',
            __('Auto-publish', 'speechkit'),
            array($this, 'render'),
            'beyondwords_content',
            'content'
        );
    }

    /**
     * Render setting field.
     *
     * @since 5.1.0
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
                <?php 
                esc_html_e(
                    'When auto-publish is disabled all audio content created in WordPress will need to be manually published in the BeyondWords dashboard',  // phpcs:ignore Generic.Files.LineLength.TooLong
                    'speechkit'
                ); 
                ?>
            </label>
        </div>
        <?php
    }
}
