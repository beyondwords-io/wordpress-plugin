<?php

declare(strict_types=1);

/**
 * Setting: Language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Language;

/**
 * Language setup
 *
 * @since 4.8.0
 */
class Language
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
        register_setting(
            'beyondwords_voices_settings',
            'beyondwords_project_language',
            [
                'default' => '',
            ]
        );

        add_settings_field(
            'beyondwords-default-language',
            __('Language', 'speechkit'),
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

        $current = get_option('beyondwords_project_language', '');
        ?>
        <div class="beyondwords-setting__default-language">
            <select
                id="beyondwords_project_language"
                name="beyondwords_project_language"
                placeholder="<?php _e('Add a language', 'speechkit'); ?>"
                style="width: 250px;"
                autocomplete="off"
            >
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
        <p class="description">
            <?php
            esc_html_e(
                'Choose the default language of your posts.',
                'speechkit'
            );
            ?>
        </p>
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
