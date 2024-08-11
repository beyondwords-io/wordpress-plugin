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

/**
 * IncludeTitle setup
 *
 * @since 4.8.0
 */
class IncludeTitle
{
    /**
     * Default value.
     *
     * @var string
     */
    public const DEFAULT_VALUE = '1';

    /**
     * Option name.
     *
     * @var string
     */
    public const OPTION_NAME = 'beyondwords_include_title';

    /**
     * @var \Beyondwords\Wordpress\Core\ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init.
     *
     * @since 4.8.0
     */
    public function init()
    {
        add_action('admin_init', array( $this, 'addSetting' ));
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
     * @since 4.8.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_content_settings',
            self::OPTION_NAME,
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => self::DEFAULT_VALUE,
            ]
        );

        add_settings_field(
            'beyondwords-include-title',
            __('Title', 'speechkit'),
            array($this, 'render'),
            'beyondwords_content',
            'content'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function render()
    {
        $optionValue = get_option(self::OPTION_NAME, self::DEFAULT_VALUE);
        ?>
        <div>
            <label>
                <input
                    type="checkbox"
                    id="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    name="<?php echo esc_attr(self::OPTION_NAME); ?>"
                    value="1"
                    <?php checked($optionValue, self::DEFAULT_VALUE); ?>
                />
                <?php esc_html_e('Include title in audio', 'speechkit'); ?>
            </label>
        </div>
        <?php
    }
}
