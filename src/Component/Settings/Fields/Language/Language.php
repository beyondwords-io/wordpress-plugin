<?php

declare(strict_types=1);

/**
 * Setting: Language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Language;

use Beyondwords\Wordpress\Component\Settings\Sync;
use Beyondwords\Wordpress\Core\ApiClient;

/**
 * Language
 *
 * @since 5.0.0
 */
class Language
{
    /**
     * Option name.
     */
    public const OPTION_NAME_ID = 'beyondwords_project_language_id';

    /**
     * Option name.
     */
    public const OPTION_NAME_CODE = 'beyondwords_project_language_code';

    /**
     * Constructor
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('add_option_' . self::OPTION_NAME_ID, array($this, 'setLanguageCode'));
        add_action('update_option_' . self::OPTION_NAME_ID, array($this, 'setLanguageCode'));
        add_action('pre_update_option_' . self::OPTION_NAME_CODE, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME_CODE);
            return $value;
        });
    }

    /**
     * Add setting.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_voices_settings',
            self::OPTION_NAME_ID,
            [
                'type' => 'integer',
                'default' => null,
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
     * @since 5.0.0
     *
     * @return void
     **/
    public function render()
    {
        $options = $this->getOptions();

        $current = get_option(self::OPTION_NAME_ID);
        ?>
        <div class="beyondwords-setting__default-language">
            <select
                id="<?php echo esc_attr(self::OPTION_NAME_ID) ?>"
                name="<?php echo esc_attr(self::OPTION_NAME_ID) ?>"
                placeholder="<?php esc_attr_e('Add a language', 'speechkit'); ?>"
                style="width: 250px;"
                autocomplete="off"
            >
                <?php
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" data-voices=\'%s\' %s>%s</option>',
                        esc_attr($option['value']),
                        esc_attr($option['voices']),
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
     * @since 5.0.0
     *
     * @return string[] Array of options (value, label).
     **/
    public function getOptions()
    {
        $languages = ApiClient::getLanguages();

        if (! is_array($languages)) {
            $languages = [];
        }

        $options = array_map(function ($language) {
            return [
                'value'  => $language['id'],
                'label'  => $language['name'],
                'voices' => wp_json_encode($language['default_voices']),
            ];
        }, $languages);

        return $options;
    }

    /**
     * Set the language code every time the language ID changes.
     *
     * @since 5.0.0
     *
     * @return void.
     **/
    public function setLanguageCode()
    {
        $languageId = (int)get_option(self::OPTION_NAME_ID);

        if (! $languageId) {
            return;
        }

        $languages = ApiClient::getLanguages();

        if (! is_array($languages)) {
            return;
        }

        foreach ($languages as $item) {
            if (
                ! empty($item['id'])
                && $item['id'] === $languageId
                && ! empty($item['code'])
            ) {
                update_option(self::OPTION_NAME_CODE, $item['code']);
                break;
            }
        }
    }
}
