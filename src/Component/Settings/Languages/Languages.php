<?php

declare(strict_types=1);

/**
 * Setting: Languages
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Languages;

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
        if (SettingsUtils::hasApiSettings()) {
            add_action('admin_init', array($this, 'registerSetting'));
            add_action('admin_init', array($this, 'addSettingsField'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        }
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
        if (! SettingsUtils::hasApiSettings()) {
            return;
        }

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
            __('Languages', 'speechkit'),
            array($this, 'render'),
            'beyondwords',
            'content'
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
                printf(
                    /* translators: %s is replaced with the link to the BeyondWords dashboard */
                    esc_html__('The default voice for audio is determined by the project settings in your %s.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                    sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url(Environment::getDashboardUrl()),
                        esc_html__('BeyondWords dashboard', 'speechkit')
                    )
                );
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

    /**
     * Register the component scripts.
     *
     * @since  4.0.0
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public function enqueueScripts($hook)
    {
        // Only enqueue for Post screens
        if ($hook === 'settings_page_beyondwords' && SettingsUtils::hasApiSettings()) {
            // Tom Select CSS
            wp_enqueue_style(
                'tom-select',
                'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css',
                false,
                BEYONDWORDS__PLUGIN_VERSION
            );

            // Tom Select JS
            wp_enqueue_script(
                'tom-select',
                'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js',
                [],
                '2.2.2',
                true
            );

            // Settings page
            wp_register_script(
                'beyondwords-settings--languages',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Settings/Languages/settings.js',
                ['jquery', 'tom-select'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );

            wp_enqueue_script('beyondwords-settings--languages');
        }
    }
}
