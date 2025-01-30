<?php

declare(strict_types=1);

/**
 * Setting: BodyVoice
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Voice;

use Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice;
use Beyondwords\Wordpress\Component\Settings\Sync;

/**
 * BodyVoice
 *
 * @since 5.0.0
 */
class BodyVoice extends Voice
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_project_body_voice_id';

    /**
     * Init.
     *
     * @since 5.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('pre_update_option_' . self::OPTION_NAME, function ($value) {
            Sync::syncOptionToDashboard(self::OPTION_NAME);
            return $value;
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
            self::OPTION_NAME,
            [
                'sanitize_callback' => 'absint',
            ]
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
     * @since 5.0.0
     *
     * @return void
     **/
    public function render()
    {
        $current = get_option(self::OPTION_NAME);
        $options = $this->getOptions();
        // phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        ?>
        <div class="beyondwords-setting__body-voice">
            <select
                id="<?php echo esc_attr(self::OPTION_NAME) ?>"
                name="<?php echo esc_attr(self::OPTION_NAME) ?>"
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
                'Choose the default voice for your article body sections.',
                'speechkit'
            );
            ?>
        </p>
        <?php
        // phpcs:enable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
    }
}
