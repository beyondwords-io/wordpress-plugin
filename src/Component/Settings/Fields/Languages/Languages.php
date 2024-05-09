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

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * Languages setup
 *
 * @since 4.0.0
 */
class Languages
{
    public const DEFAULT_LANGUAGES = [];

    /**
     * API Client.
     *
     * @since 3.0.0
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 3.0.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'registerSetting'));
        add_action('admin_init', array($this, 'addSettingsField'));
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function registerSetting()
    {
        // if (! SettingsUtils::hasApiSettings()) {
        //     return;
        // }

        register_setting(
            'beyondwords',
            'beyondwords_languages',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );
    }

    /**
     * Init setting.
     *
     * @since  4.0.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        add_settings_field(
            'beyondwords-languages',
            __('Multiple languages', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
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
        $allLanguages = $this->apiClient->getLanguages();

        if (! is_array($allLanguages)) {
            $allLanguages = [];
        }

        $selectedLanguages = get_option('beyondwords_languages', Languages::DEFAULT_LANGUAGES);

        if (! is_array($selectedLanguages)) {
            $selectedLanguages = Languages::DEFAULT_LANGUAGES;
        }

        ?>
        <div class="beyondwords-setting--languages">
            <select
                id="beyondwords_languages"
                name="beyondwords_languages[]"
                placeholder="Add a language"
                multiple
                style="width: 500px;"
            >
                <?php foreach ($allLanguages as $language) : ?>
                    <option
                        value="<?php echo esc_attr($language['id']); ?>"
                        <?php selected(in_array($language['id'], $selectedLanguages), true); ?>
                    >
                        <?php echo esc_attr($language['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
            <?php
                esc_html_e('The default voice for audio is set in the “Voices” tab.', 'speechkit');
            ?>
            </p>
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
