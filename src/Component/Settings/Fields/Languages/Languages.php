<?php

declare(strict_types=1);

/**
 * Setting: Languages
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Languages;

use Beyondwords\Wordpress\Core\ApiClient;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Languages
 *
 * @since 4.0.0
 */
class Languages
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_languages';

    /**
     * Default.
     *
     * @since 3.0.0
     */
    public const DEFAULT_LANGUAGES = [];

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_advanced_settings',
            self::OPTION_NAME,
            [
                'default'           => self::DEFAULT_LANGUAGES,
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-languages',
            __('Multiple languages', 'speechkit'),
            array($this, 'render'),
            'beyondwords_advanced',
            'advanced'
        );
    }

    /**
     * Render setting field.
     *
     * @since 4.0.0
     *
     * @return void
     **/
    public function render()
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();

        $allLanguages = ApiClient::getLanguages();

        if (! is_array($allLanguages)) {
            $allLanguages = [];
        }

        $selectedLanguages = get_option(self::OPTION_NAME);

        if (empty($selectedLanguages) || ! is_array($selectedLanguages)) {
            $selectedLanguages = self::DEFAULT_LANGUAGES;
        }

        ?>
        <div class="beyondwords-setting__languages">
            <select
                multiple
                id="<?php echo esc_attr(self::OPTION_NAME); ?>"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[]"
                placeholder="<?php esc_attr_e('Add a language', 'speechkit'); ?>"
                style="width: 500px;"
                autocomplete="off"
            >
                <?php foreach ($allLanguages as $language) :
                    $languageId        = $propertyAccessor->getValue($language, '[id]');
                    $languageName      = $propertyAccessor->getValue($language, '[name]');
                    $bodyId            = $propertyAccessor->getValue($language, '[default_voices][body][id]');
                    $bodySpeakingRate  = $propertyAccessor->getValue($language, '[default_voices][body][speaking_rate]'); // phpcs:ignore Generic.Files.LineLength.TooLong
                    $titleId           = $propertyAccessor->getValue($language, '[default_voices][title][id]');
                    $titleSpeakingRate = $propertyAccessor->getValue($language, '[default_voices][title][speaking_rate]'); // phpcs:ignore Generic.Files.LineLength.TooLong
                    ?>
                    <option
                        value="<?php echo esc_attr($languageId); ?>"
                        data-default-voice-body-id="<?php echo esc_attr($bodyId); ?>"
                        data-default-voice-body-speaking-rate="<?php echo esc_attr($bodySpeakingRate); ?>"
                        data-default-voice-title-id="<?php echo esc_attr($titleId); ?>"
                        data-default-voice-title-speaking-rate="<?php echo esc_attr($titleSpeakingRate); ?>"
                        <?php selected(in_array($languageId, $selectedLanguages), true); ?>
                    >
                        <?php echo esc_attr($languageName); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
            <?php
                esc_html_e('Add languages here to use voices other than the default project voice.', 'speechkit');
            ?>
            </p>
            <p class="description">
            <?php
                esc_html_e('The voices will be available to select on the Post Edit screen.', 'speechkit');
            ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  4.0.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        if (empty($value)) {
            $value = [];
        }

        return $value;
    }
}
