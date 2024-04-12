<?php

declare(strict_types=1);

/**
 * BeyondWords settings.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings;

use Beyondwords\Wordpress\Component\Settings\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Languages\Languages;
use Beyondwords\Wordpress\Component\Settings\Preselect\Preselect;
use Beyondwords\Wordpress\Component\Settings\PrependExcerpt\PrependExcerpt;
use Beyondwords\Wordpress\Component\Settings\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Component\Settings\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Component\Settings\PlayerVersion\PlayerVersion;
use Beyondwords\Wordpress\Component\Settings\ProjectId\ProjectId;
use Beyondwords\Wordpress\Component\Settings\SettingsUpdated\SettingsUpdated;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * Settings setup
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 3.0.0
 */
class Settings
{
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
     * Init
     */
    public function init()
    {
        (new ApiKey())->init();
        (new ProjectId())->init();
        (new Preselect())->init();
        (new PrependExcerpt())->init();
        (new PlayerVersion($this->apiClient))->init();
        (new PlayerUI())->init();
        (new PlayerStyle($this->apiClient))->init();
        (new Languages($this->apiClient))->init();
        (new SettingsUpdated())->init();

        add_action('admin_menu', array($this, 'addOptionsPage'));
        add_action('admin_init', array($this, 'addSettingsSections'));
        add_action('admin_notices', array($this, 'printPluginAdminNotices'));
        add_action('rest_api_init', array($this, 'restApiInit'));

        add_filter('plugin_action_links_speechkit/speechkit.php', array($this, 'addSettingsLinkToPluginPage'));

        add_action('updated_option', array($this, 'updatedOption'), 99);
        add_action('added_option', array($this, 'addedOption'), 99);
    }

    /**
     * Add items to the WordPress admin menu.
     *
     * @since  3.0.0
     *
     * @return void
     */
    public function addOptionsPage()
    {
        // Settings > BeyondWords
        add_options_page(
            __('BeyondWords Settings', 'speechkit'),
            __('BeyondWords', 'speechkit'),
            'manage_options',
            'beyondwords',
            array($this, 'createAdminInterface')
        );
    }

    /**
     * Add Settings sections.
     *
     * =====
     * Basic
     * =====
     * 'api_key'    => '',
     * 'project_id' => '',
     *
     * ==============
     * Player
     * ==============
     * 'version' => '0',
     *
     * ==============
     * Generate audio
     * ==============
     * 'preselect' => array('post' => '1', 'page' => '1'),
     *
     * ========
     * Advanced
     * ========
     * 'merge_excerpt' => false,
     *
     * @since  3.0.0
     */
    public function addSettingsSections()
    {
        // Add Settings Section: Basic
        add_settings_section(
            'basic',
            __('Basic settings', 'speechkit'),
            array($this, 'basicSectionCallback'),
            'beyondwords_basic'
        );

        if (SettingsUtils::hasApiSettings()) {
            // Add Settings Section: Player
            add_settings_section(
                'player',
                __('Player settings', 'speechkit'),
                array($this, 'playerSectionCallback'),
                'beyondwords_player'
            );

            // Add Settings Section: Content
            add_settings_section(
                'content',
                __('Content settings', 'speechkit'),
                array($this, 'contentSectionCallback'),
                'beyondwords_content'
            );

            // Add Settings Section: Generate audio
            add_settings_section(
                'generate-audio',
                __('‘Generate audio’ settings', 'speechkit'),
                array($this, 'generateAudioSectionCallback'),
                'beyondwords_generate-audio'
            );
        }
    }

    /**
     * "Basic section" callback
     *
     * @since 3.0.0
     *
     * @return void
     **/
    public function basicSectionCallback()
    {
        delete_transient('beyondwords_settings_errors');
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'The details we need to authenticate your BeyondWords account. For more options, head to your BeyondWords dashboard.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * "Player section" callback
     *
     * @since 4.0.0
     *
     * @return void
     **/
    public function playerSectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Upgrade to the latest player version for the newest features.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * "Content" section callback
     *
     * @since 3.0.0
     *
     * @return void
     **/
    public function contentSectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'By default, BeyondWords will process your titles and body content into audio.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * ‘Generate audio’ section callback.
     *
     * @since 3.0.0
     *
     * @return void
     **/
    public function generateAudioSectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'The ‘Generate audio’ checkbox in the BeyondWords sidebar will be automatically checked for selected post types. The default setting can be manually overridden.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <?php
            esc_html_e(
                'Uncheck a post type to view its Categories. You can then set defaults at a category level. Make sure to check all relevant boxes.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <em>
                <?php
                esc_html_e(
                    'The default WordPress ‘Categories’ taxonomy is currently the only taxonomy supported.',
                    'speechkit'
                );
                ?>
            </em>
        </p>
        <?php
    }

    public function addSettingsLinkToPluginPage($links)
    {
        $links[] = '<a href="' .
            esc_url(admin_url('options-general.php?page=beyondwords')) .
            '">' . __('Settings', 'speechkit') . '</a>';
        return $links;
    }

    /**
     * @since 4.7.0
     */
    public function getTabs()
    {
        $tabs = array(
            'basic'          => 'Basic',
            'player'         => 'Player',
            'content'        => 'Content',
            'generate-audio' => 'Generate audio',
        );

        if (! SettingsUtils::hasApiSettings()) {
            $tabs = array_splice($tabs, 0, 1);
        }

        return $tabs;
    }

    /**
     * @since 4.7.0
     */
    public function getCurrentTab($tabs)
    {
        $defaultTab = array_key_first($tabs);

        if (
            ! isset($_POST['beyondwords_settings_nonce']) ||
            ! wp_verify_nonce(
                sanitize_text_field($_POST['beyondwords_settings_nonce']),
                'beyondwords_settings'
            )
        ) {
            return $defaultTab;
        }

        if (isset($_GET['tab'])) {
            $tab = sanitize_text_field($_GET['tab']);
        } else {
            $tab = $defaultTab;
        }

        if (!empty($tab) && array_key_exists($tab, $tabs)) {
            $currentTab = $tabs[$tab];
        } else {
            $currentTab = $defaultTab;
        }

        return $currentTab;
    }

    /**
     * @since 3.0.0
     * @since 4.7.0 Added tabs.
     */
    public function createAdminInterface()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('BeyondWords Settings', 'speechkit'); ?></h1>
            <?php
            $tabs = $this->getTabs();
            $currentTab = $this->getCurrentTab($tabs);
            ?>
            <form
                id="beyondwords-plugin-settings"
                action="<?php echo esc_url(admin_url('options.php')); ?>"
                method="post"
            >
                <nav class="nav-tab-wrapper">
                    <?php
                    foreach ($tabs as $tab => $name) {
                        // CSS class for a current tab
                        $current = $tab === $currentTab ? ' nav-tab-active' : '';
                        // URL
                        $url = add_query_arg(array( 'page' => 'beyondwords', 'tab' => $tab ), '');
                        ?>
                        <a class="nav-tab<?php esc_attr_e($current); ?>" href="<?php echo esc_url($url); ?>">
                            <?php esc_html_e($name); ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
                <hr class="wp-header-end">
                <?php
                if ($currentTab === 'basic') {
                    $this->dashboardLink();
                }

                if ($currentTab === 'player') {
                    $this->playerLocationNotice();
                    $this->playerRevertedNotice();
                }

                settings_fields("beyondwords_{$currentTab}_settings");
                do_settings_sections("beyondwords_{$currentTab}");

                wp_nonce_field('beyondwords_settings', 'beyondwords_settings_nonce');

                if (SettingsUtils::hasApiSettings()) {
                    submit_button('Save Settings');
                } else {
                    submit_button('Continue setup');
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * @since 4.7.0
     */
    public function dashboardLink()
    {
        $projectId = get_option('beyondwords_project_id');

        if ($projectId) :
            ?>
            <p>
                <a
                    class="button button-secondary"
                    href="<?php echo esc_url(Environment::getDashboardUrl()); ?>"
                    target="_blank"
                >
                    <?php esc_html_e('BeyondWords dashboard', 'speechkit'); ?>
                </a>
            </p>
            <?php
        endif;
    }

    /**
     * @since 4.7.0
     */
    public function playerRevertedNotice()
    {
        $playerReverted = get_transient('beyondwords_player_reverted');

        if ($playerReverted) :
            ?>
            <div id="beyondwords-player-reverted-notice" class="notice notice-error notice-inline">
                <p>
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php
                    printf(
                        /* translators: %s is replaced with a "let us know" link */
                        esc_html__('It looks like you tried the "Latest" player and switched back to the "Legacy" player. If you experienced any issues switching player please %s so we can help.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                        sprintf(
                            '<a href="mailto:support@beyondwords.io?subject=%s">%s</a>',
                            esc_attr__('WordPress support: Latest player', 'speechkit'),
                            esc_html__('let us know', 'speechkit')
                        )
                    );
                    ?>
                </p>
            </div>
            <?php
        endif;
    }

    /**
     * @since 4.7.0
     */
    public function playerLocationNotice()
    {
        if (SettingsUtils::hasApiSettings()) :
            ?>
            <div id="beyondwords-player-location-notice" class="notice notice-info">
                <p>
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e(
                        'The player will appear before the first part of <code>the_content()</code> by default. You can change the location via the WordPress Editor.', // phpcs:ignore Generic.Files.LineLength.TooLong
                        'speechkit'
                    );
                    ?>
                </p>
            </div>
            <?php
        endif;
    }

    /**
     * Print Admin Notices.
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function printPluginAdminNotices()
    {
        $hasApiSettings = SettingsUtils::hasApiSettings();
        $settingsErrors = get_transient('beyondwords_settings_errors');

        if (is_array($settingsErrors) && count($settingsErrors)) :
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>
                        <?php
                        printf(
                            /* translators: %s is replaced with a "plugin settings" link */
                            esc_html__('To use BeyondWords, please update the %s.', 'speechkit'),
                            sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(admin_url('options-general.php?page=beyondwords')),
                                esc_html__('plugin settings', 'speechkit')
                            )
                        );
                        ?>
                    </strong>
                </p>
                <ul class="ul-disc">
                    <?php
                    foreach ($settingsErrors as $error) {
                        printf(
                            '<li>%s</li>',
                            // Only allow links with href and target attributes
                            wp_kses(
                                $error,
                                array(
                                    'a' => array(
                                        'href'   => array(),
                                        'target' => array(),
                                    ),
                                    'b' => array(),
                                    'strong' => array(),
                                    'i' => array(),
                                    'em' => array(),
                                )
                            )
                        );
                    }
                    ?>
                </ul>
            </div>

            <?php
        elseif (false === $hasApiSettings) :
            ?>
            <div class="notice notice-info">
                <p>
                    <strong>
                        <?php
                        printf(
                            /* translators: %s is replaced with a "plugin settings" link */
                            esc_html__('To use BeyondWords, please update the %s.', 'speechkit'),
                            sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(admin_url('options-general.php?page=beyondwords')),
                                esc_html__('plugin settings', 'speechkit')
                            )
                        );
                        ?>
                    </strong>
                </p>
                <p>
                    <?php esc_html_e('Don’t have a BeyondWords account yet?', 'speechkit'); ?>
                </p>
                <p>
                    <a
                        class="button button-secondary"
                        href="<?php echo esc_url(sprintf('%s/auth/signup', Environment::getDashboardUrl())); ?>"
                        target="_blank"
                    >
                        <?php esc_html_e('Sign up free', 'speechkit'); ?>
                    </a>
                </p>
            </div>
            <?php
        endif;
    }

    /**
     * Register WP REST API route
     *
     * @return void
     */
    public function restApiInit()
    {
        // settings endpoint
        register_rest_route('beyondwords/v1', '/settings', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'restApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * WP REST API response (required for the Gutenberg editor).
     *
     * DO NOT expose ALL settings e.g. be sure to never expose the API key.
     *
     * @since 3.0.0
     * @since 3.4.0 Add pluginVersion and wpVersion.
     *
     * @return \WP_REST_Response
     */
    public function restApiResponse()
    {
        global $wp_version;

        return new \WP_REST_Response([
            'apiKey'        => get_option('beyondwords_api_key', ''),
            'pluginVersion' => BEYONDWORDS__PLUGIN_VERSION,
            'projectId'     => get_option('beyondwords_project_id', ''),
            'preselect'     => get_option('beyondwords_preselect', Preselect::DEFAULT_PRESELECT),
            'languages'     => get_option('beyondwords_languages', Languages::DEFAULT_LANGUAGES),
            'wpVersion'     => $wp_version,
        ]);
    }

    /**
     * Check API creds are valid whenever any setting is added.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function addedOption($optionName)
    {
        if ($optionName === 'beyondwords_settings_updated') {
            $this->checkApiCreds();
        }
    }

    /**
     * Check API creds are valid whenever the settings are updated.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function updatedOption($optionName)
    {
        if ($optionName === 'beyondwords_settings_updated') {
            $this->checkApiCreds();
        }
    }

    /**
     * Check to see if user has valid BeyondWords API credentials by performing
     * a GET request using the API Key and Project ID.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function checkApiCreds()
    {
        // Assume invalid connection
        delete_option('beyondwords_valid_api_connection');

        $apiKey    = get_option('beyondwords_api_key');
        $projectId = get_option('beyondwords_project_id');

        if (empty($apiKey) || empty($projectId)) {
            return;
        }

        $project = $this->apiClient->getProject();

        $validConnection = (
            is_array($project)
            && array_key_exists('id', $project)
            && strval($project['id']) === $projectId
        );

        if ($validConnection) {
            // Store date of last check
            update_option('beyondwords_valid_api_connection', gmdate(DATE_ISO8601));
        } else {
            $errors = get_transient('beyondwords_settings_errors', []);

            $errors['Settings/ValidApiConnection'] = __(
                'Please check and re-enter your BeyondWords API key and project ID. They appear to be invalid.',
                'speechkit'
            );

            set_transient('beyondwords_settings_errors', $errors);
        }
    }
}
