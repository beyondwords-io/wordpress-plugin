<?php

declare(strict_types=1);

/**
 * Setting: BodyVoice
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Voice;

use Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice;

/**
 * BodyVoice setup
 *
 * @since 4.8.0
 */
class BodyVoice extends Voice
{
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
            'beyondwords_project_body_voice_id',
        );

        add_settings_field(
            'beyondwords-body-voice',
            __('Body voice', 'speechkit'),
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
        $current = get_option('beyondwords_project_body_voice_id');
        $options = $this->getOptions();
        ?>
        <div class="beyondwords-setting__body-voice">
            <select
                name="beyondwords_project_body_voice_id"
                class="beyondwords_project_voice"
                style="width: 300px;"
            >
                <?php
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option['value']),
                        selected($option['value'], $current ?? ''),
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
                'Choose the default voice for your article body sections.',
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
