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
    const DEFAULT_VALUE = '1';

    /**
     * Option name.
     *
     * @var string
     */
    const OPTION_NAME = 'beyondwords_include_title';

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
        add_action( 'admin_init', array( $this, 'addSetting' ) );
        add_action( 'pre_update_option_' . self::OPTION_NAME, array( $this, 'preUpdateOption' ), 10, 2 );
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

    /**
     * Make the REST call every time the option is updated (even  if the
     * value is the same) by using `pre_update_option_{name}` action.
     *
     * @since 4.8.0
     *
     * @param mixed $value    The new option value.
     * @param mixed $oldValue The old option value.
     *
     * @return string
     **/
    public function preUpdateOption($value, $oldValue)
    {
        // Make REST API call
        $response = $this->apiClient->updateProject([
            'title' => [
                'enabled' => (bool) $value,
            ]
        ]);

        if (
            ! empty($response)
            && ! empty($response['title'])
            && is_array($response['title'])
            && array_key_exists('enabled', $response['title'])
        ) {
            // Success
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-controls-volumeon"></span>"Include title in audio" has been synced to the BeyondWords REST API.', 'success');
            return $response['title']['enabled'] ? '1' : '';
        }

        // Error
        add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-controls-volumeon"></span>Error syncing "Include title in audio" to the BeyondWords REST API. Please try again.', 'error');
        return $oldValue;
    }
}
