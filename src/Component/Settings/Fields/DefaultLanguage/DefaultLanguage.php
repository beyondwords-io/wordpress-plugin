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
     * API client, required to check whether video is enabled or not.
     */
    private $apiClient;

    /**
     * Constructor
     */
    public function __construct()
    // public function __construct($apiClient)
    {
        // $this->apiClient = $apiClient;
    }

    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSettingsField'));
    }

    /**
     * Add settings field.
     *
     * @since 4.5.0
     *
     * @return void
     */
    public function addSettingsField()
    {
        if (! SettingsUtils::hasApiSettings()) {
            return;
        }

        register_setting(
            'beyondwords',
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
        $currentLanguage = get_option('beyondwords_default_languages');
        $languages = $this->getLanguages();
        ?>
        <div class="beyondwords-setting--player--player-style">
            <select name="beyondwords_player_style">
                <?php
                foreach ($languages as $language) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($language['value']),
                        selected($language['value'], $currentLanguage),
                        esc_html($language['label'])
                    );
                }
                ?>
            </select>
        </div>
        <?php
    }

    /**
     * Get all languages for the current project.
     *
     * @since 4.8.0
     *
     * @return string[] Associative array of languages.
     **/
    public function getLanguages()
    {
        $languages = [
            [
                'value' => 'to-follow',
                'label' => 'To follow...',
            ]
        ];

        return $languages;
    }
}
