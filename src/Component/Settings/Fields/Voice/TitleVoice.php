<?php

declare(strict_types=1);

/**
 * Setting: Default language
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Voice;

use Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice;

/**
 * TitleVoice setup
 *
 * @since 4.8.0
 */
class TitleVoice extends Voice
{
    /**
     * Option name.
     */
    public const OPTION_NAME = 'beyondwords_project_title_voice_id';

    /**
     * Init.
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('update_option_' . self::OPTION_NAME, function () {
            add_filter('beyondwords_sync_to_dashboard', function ($fields) {
                $fields[] = self::OPTION_NAME;
                return $fields;
            });
        });
    }

    /**
     * Add setting.
     *
     * @since 4.5.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_voices_settings',
            'beyondwords_project_title_voice_id',
        );

        add_settings_field(
            'beyondwords-title-voice',
            __('Title voice', 'speechkit'),
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
        $current = get_option('beyondwords_project_title_voice_id');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting__title-voice">
            <select
                name="beyondwords_project_title_voice_id"
                class="beyondwords_project_voice"
                style="width: 300px;"
            >
                <?php
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" data-language-code="%s" %s>%s</option>',
                        esc_attr($option['value']),
                        esc_attr($option['language_code']),
                        selected($option['value'], $current),
                        esc_html($option['label'])
                    );
                }
                ?>
            </select>
            <img src="/wp-admin/images/spinner.gif" class="beyondwords-settings__loader" style="display:none;" />
        </div>
        <p class="description">
            <?php
            esc_html_e(
                'Choose the default voice for your article title sections.',
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
