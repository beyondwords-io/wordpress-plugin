<?php

declare(strict_types=1);

/**
 * Setting: Integration method
 *
 * @package Beyondwords\Wordpress
 * @since   6.0.0 Introduced.
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod;

/**
 * IntegrationMethod class.
 *
 * @since 6.0.0 Introduced.
 */
class IntegrationMethod
{
    /**
     * Option name.
     *
     * @since 6.0.0 Introduced.
     *
     * @var string
     */
    public const OPTION_NAME = 'beyondwords_integration_method';

    /**
     * Client-side integration method.
     *
     * @since 6.0.0 Introduced.
     *
     * @var string
     */
    public const CLIENT_SIDE = 'client_side';

    /**
     * REST API integration method.
     *
     * @since 6.0.0 Introduced.
     *
     * @var string
     */
    public const REST_API = 'rest_api';

    /**
     * Constructor
     *
     * @since 6.0.0 Introduced.
     */
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'addSetting'));
    }

    /**
     * Add setting.
     *
     * @since 6.0.0 Introduced.
     *
     * @return void
     */
    public static function addSetting()
    {
        register_setting(
            'beyondwords_content_settings',
            self::OPTION_NAME,
            [
                'default' => IntegrationMethod::REST_API,
            ]
        );

        add_settings_field(
            'beyondwords-integration-method',
            __('Integration method', 'speechkit'),
            array(__CLASS__, 'render'),
            'beyondwords_content',
            'content'
        );
    }

    /**
     * Render setting field.
     *
     * @since 6.0.0 Introduced.
     *
     * @return void
     **/
    public static function render()
    {
        $options = self::getOptions();
        $current = get_option(self::OPTION_NAME);
        ?>
        <div class="beyondwords-setting__content beyondwords-setting__content--integration-method">
            <select name="<?php echo esc_attr(self::OPTION_NAME) ?>">
                <?php foreach ($options as $option) : ?>
                    <option value="<?php esc_attr($option['value']); ?>" <?php selected($option['value'], $current); ?>>
                        <?php echo esc_html($option['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the "Magic Embed" link */
                esc_html__('REST API is the default method. Use Client-side if REST API does not work as expected on your site, or if you are using a page builder plugin/theme such as Elementor.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                sprintf(
                    '<a href="https://github.com/beyondwords-io/player/blob/main/doc/magic-embed.md" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    esc_html__('Magic Embed', 'speechkit')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Returns all options for the setting field.
     *
     * @since 6.0.0 Introduced.
     *
     * @return array Associative array of option values and labels.
     **/
    public static function getOptions()
    {
        $options = [
            IntegrationMethod::CLIENT_SIDE => [
                'value' => IntegrationMethod::CLIENT_SIDE,
                'label' => __('Client-side', 'speechkit'),
            ],
            IntegrationMethod::REST_API => [
                'value' => IntegrationMethod::REST_API,
                'label' => __('REST API', 'speechkit'),
            ],
        ];

        return $options;
    }
}
