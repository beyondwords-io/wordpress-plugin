<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\DefaultLanguage;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * DefaultLanguage setup
 *
 * @since 4.8.0
 */
class DefaultLanguage
{
    /**
     * API Client.
     *
     * @since 4.8.0
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
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Add setting.
     *
     * @since 4.8.0
     *
     * @return void
     */
    public function addSetting()
    {
        // if (! SettingsUtils::hasApiSettings()) {
        //     return;
        // }

        register_setting(
            'beyondwords_voices_settings',
            'beyondwords_default_language',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-default-language',
            __('Default language', 'speechkit'),
            array($this, 'render'),
            'beyondwords_voices',
            'voices'
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
        $options = $this->getOptions();

        $current = get_option('beyondwords_default_language', '');
        ?>
        <div class="beyondwords-setting--default-language">
            <select name="beyondwords_default_language">
                <?php
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option['value']),
                        selected($option['value'], $current),
                        esc_html($option['label'])
                    );
                }
                ?>
            </select>
        </div>
        <?php
    }

    /**
     * Get options for the <select> element.
     *
     * @since 4.8.0
     *
     * @return string[] Array of options (value, label).
     **/
    public function getOptions()
    {
        $languages = $this->apiClient->getLanguages();

        if (! is_array($languages)) {
            $languages = [];
        }

        $options = array_map(function ($language) {
            return [
                'value' => $language['id'],
                'label' => $language['name'],
            ];
        }, $languages);

        return $options;
    }
}
