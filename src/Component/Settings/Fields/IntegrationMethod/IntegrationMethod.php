<?php

declare(strict_types=1);

/**
 * Setting: Integration method
 *
 * @package Beyondwords\Wordpress
 * @since   6.0.0 Introduced.
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod;

use WP_Post;

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
    public const CLIENT_SIDE = 'client-side';

    /**
     * REST API integration method.
     *
     * @since 6.0.0 Introduced.
     *
     * @var string
     */
    public const REST_API = 'rest-api';

    /**
     * Default value.
     *
     * @since 6.0.0 Introduced.
     *
     * @var string
     */
    public const DEFAULT_VALUE = self::REST_API;

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
                'type'    => 'string',
                'default' => self::DEFAULT_VALUE,
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
        $current = get_option(self::OPTION_NAME, self::DEFAULT_VALUE);
        ?>
        <div class="beyondwords-setting__content beyondwords-setting__content--integration-method">
            <select name="<?php echo esc_attr(self::OPTION_NAME) ?>" id="<?php echo esc_attr(self::OPTION_NAME) ?>">
                <?php foreach ($options as $option) : ?>
                    <option
                        value="<?php echo esc_attr($option['value']); ?>"
                        <?php selected($option['value'], $current); ?>
                    >
                        <?php echo esc_html($option['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the "Client-Side integration" link */
                esc_html__('REST API is currently the default method. %s should be selected if REST API does not work as expected on your site. It should, for instance, improve compatibility on sites using a page builder plugin/theme such as Elementor.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                sprintf(
                    '<a href="https://github.com/beyondwords-io/player/blob/main/doc/client-side-integration.md" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    esc_html__('Client-Side integration', 'speechkit')
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
        return [
            self::REST_API => [
                'value' => self::REST_API,
                'label' => __('REST API', 'speechkit'),
            ],
            self::CLIENT_SIDE => [
                'value' => self::CLIENT_SIDE,
                'label' => __('Magic Embed', 'speechkit'),
            ],
        ];
    }

    /**
     * Get integration method options. First tries the post meta, then the option.
     *
     * @since 6.0.0 Introduced.
     *
     * @param \WP_Post|int|null $post WordPress Post object or ID. If null, uses the current post.
     *
     * @return string The integration method value.
     **/
    public static function getIntegrationMethod($post = null)
    {
        if (! is_a($post, 'WP_Post')) {
            $post = get_post($post);

            if (! $post) {
                return self::DEFAULT_VALUE;
            }
        }

        $integrationMethod = get_post_meta($post->ID, self::OPTION_NAME, true);

        if (empty($integrationMethod)) {
            $integrationMethod = get_option(self::OPTION_NAME, self::DEFAULT_VALUE);
        }

        if (! in_array($integrationMethod, [self::REST_API, self::CLIENT_SIDE], true)) {
            $integrationMethod = self::DEFAULT_VALUE;
        }

        return $integrationMethod;
    }
}
