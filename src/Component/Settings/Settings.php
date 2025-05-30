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

use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;
use Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio;
use Beyondwords\Wordpress\Component\Settings\Tabs\Content\Content;
use Beyondwords\Wordpress\Component\Settings\Tabs\Credentials\Credentials;
use Beyondwords\Wordpress\Component\Settings\Tabs\Player\Player;
use Beyondwords\Wordpress\Component\Settings\Tabs\Pronunciations\Pronunciations;
use Beyondwords\Wordpress\Component\Settings\Tabs\Summarization\Summarization;
use Beyondwords\Wordpress\Component\Settings\Tabs\Voices\Voices;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Component\Settings\Sync;
use Beyondwords\Wordpress\Core\Environment;

/**
 * Settings
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 3.0.0
 */
class Settings
{
    public const REVIEW_NOTICE_TIME_FORMAT = '-14 days';

    /**
     * Init
     *
     * @since 3.0.0 Introduced.
     * @since 5.4.0 Add plugin review notice.
     */
    public function init()
    {
        (new Credentials())->init();
        (new Sync())->init();

        if (SettingsUtils::hasValidApiConnection()) {
            (new Voices())->init();
            (new Content())->init();
            (new Player())->init();
            (new Summarization())->init();
            (new Pronunciations())->init();
        }

        add_action('admin_menu', array($this, 'addOptionsPage'), 1);
        add_action('admin_notices', array($this, 'printMissingApiCredsWarning'), 100);
        add_action('admin_notices', array($this, 'printSettingsErrors'), 200);
        add_action('admin_notices', array($this, 'maybePrintPluginReviewNotice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('load-settings_page_beyondwords', array($this, 'maybeValidateApiCreds'));

        add_action('rest_api_init', array($this, 'restApiInit'));

        add_filter('plugin_action_links_speechkit/speechkit.php', array($this, 'addSettingsLinkToPluginPage'));
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
     * Validate API creds if we are on the credentials tab.
     *
     * @since 5.4.0
     *
     * @return void
     */
    public function maybeValidateApiCreds()
    {
        $activeTab = self::getActiveTab();

        if ($activeTab === 'credentials') {
            SettingsUtils::validateApiConnection();
        }
    }

    /**
     * Prints the admin interface for plugin settings.
     *
     * @since 3.0.0
     * @since 4.7.0 Added tabs.
     *
     * @return void
     */
    public function createAdminInterface()
    {
        $tabs      = self::getTabs();
        $activeTab = self::getActiveTab();
        ?>
        <div class="wrap">
            <h1>
                <?php esc_attr_e('BeyondWords Settings', 'speechkit'); ?>
            </h1>

            <form
                id="beyondwords-plugin-settings"
                action="<?php echo esc_url(admin_url('options.php')); ?>"
                method="post"
            >
                <nav class="nav-tab-wrapper">
                    <ul>
                        <?php
                        foreach ($tabs as $id => $title) {
                            $activeClass = $id === $activeTab ? 'nav-tab-active' : '';

                            $url = add_query_arg([
                                'page' => 'beyondwords',
                                'tab'  => urlencode($id),
                            ]);
                            ?>
                            <li>
                                <a
                                    class="nav-tab <?php echo esc_attr($activeClass); ?>"
                                    href="<?php echo esc_url($url); ?>"
                                >
                                    <?php echo wp_kses_post($title); ?>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </nav>

                <hr class="wp-header-end">

                <?php
                settings_fields("beyondwords_{$activeTab}_settings");
                do_settings_sections("beyondwords_{$activeTab}");

                // Some tabs have no fields to submit
                if (! in_array($activeTab, ['summarization', 'pronunciations'])) {
                    submit_button('Save changes');
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add "Settings" link to plugin page.
     *
     * @since 3.0.0
     * @since 4.7.0 Prepend custom links instead of appending them.
     */
    public function addSettingsLinkToPluginPage($links)
    {
        $settingsLink = '<a href="' .
            esc_url(admin_url('options-general.php?page=beyondwords')) .
            '">' . __('Settings', 'speechkit') . '</a>';

        array_unshift($links, $settingsLink);

        return $links;
    }

    /**
     * Get tabs.
     *
     * @since 4.7.0
     * @since 5.2.0 Make static.
     *
     * @return array Tabs
     */
    public static function getTabs()
    {
        $tabs = array(
            'credentials'    => __('Credentials', 'speechkit'),
            'content'        => __('Content', 'speechkit'),
            'voices'         => __('Voices', 'speechkit'),
            'player'         => __('Player', 'speechkit'),
            'summarization'  => __('Summarization', 'speechkit'),
            'pronunciations' => __('Pronunciations', 'speechkit'),
        );

        if (! SettingsUtils::hasValidApiConnection()) {
            $tabs = array_splice($tabs, 0, 1);
        }

        return $tabs;
    }

    /**
     * Get active tab.
     *
     * @since 4.7.0
     * @since 5.2.0 Make static.
     *
     * @return string Active tab
     */
    public static function getActiveTab()
    {
        $tabs = self::getTabs();

        if (! count($tabs)) {
            return '';
        }

        $defaultTab = array_key_first($tabs);

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['tab'])) {
            $tab = sanitize_text_field(wp_unslash($_GET['tab']));
        } else {
            $tab = $defaultTab;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (!empty($tab) && array_key_exists($tab, $tabs)) {
            $activeTab = $tab;
        } else {
            $activeTab = $defaultTab;
        }

        return $activeTab;
    }

    /**
     * Print missing API creds warning.
     *
     * @since 5.2.0
     *
     * @return void
     */
    public function printMissingApiCredsWarning()
    {
        if (! SettingsUtils::hasApiCreds()) :
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
     * Maybe print plugin review notice.
     *
     * @since 5.4.0
     *
     * @return void
     */
    public function maybePrintPluginReviewNotice()
    {
        $screen = get_current_screen();
        if ($screen && 'settings_page_beyondwords' !== $screen->id) {
            return;
        }

        $dateActivated       = get_option('beyondwords_date_activated', '2025-03-01');
        $dateNoticeDismissed = get_option('beyondwords_notice_review_dismissed', '');

        $showNotice = false;

        if (empty($dateNoticeDismissed)) {
            $dateActivated = strtotime($dateActivated);

            if ($dateActivated < strtotime(self::REVIEW_NOTICE_TIME_FORMAT)) {
                $showNotice = true;
            }
        }

        if ($showNotice) :
            ?>
            <div id="beyondwords_notice_review" class="notice notice-info is-dismissible">
                <p>
                    <strong>
                        <?php
                        printf(
                            /* translators: %s is replaced with a "WordPress Plugin Repo" link */
                            esc_html__('Happy with our work? Help us spread the word with a rating on the %s.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                            sprintf(
                                '<a href="%s">%s</a>',
                                'https://wordpress.org/support/plugin/speechkit/reviews/',
                                esc_html__('WordPress Plugin Repo', 'speechkit')
                            )
                        );
                        ?>
                    </strong>
                </p>
            </div>
            <?php
        endif;
    }

    /**
     * Print settings errors.
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function printSettingsErrors()
    {
        $settingsErrors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        if (is_array($settingsErrors) && count($settingsErrors)) :
            ?>
            <div class="notice notice-error">
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
                                    'br' => array(),
                                    'code' => array(),
                                )
                            )
                        );
                    }
                    ?>
                </ul>
            </div>
            <?php
        endif;
    }

    /**
     * Register WP REST API routes
     *
     * @since 5.4.0 Add REST API route to dismiss review notice.
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

        // settings endpoint
        register_rest_route('beyondwords/v1', '/settings', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'restApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        // dismiss review notice endpoint
        register_rest_route('beyondwords/v1', '/settings/notices/review/dismiss', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'dismissReviewNotice'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
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
            'apiKey'              => get_option('beyondwords_api_key', ''),
            'pluginVersion'       => BEYONDWORDS__PLUGIN_VERSION,
            'projectId'           => get_option('beyondwords_project_id', ''),
            'preselect'           => get_option('beyondwords_preselect', PreselectGenerateAudio::DEFAULT_PRESELECT),
            'projectLanguageCode' => get_option('beyondwords_project_language_code', ''),
            'projectBodyVoiceId'  => get_option('beyondwords_project_body_voice_id', ''),
            'wpVersion'           => $wp_version,
        ]);
    }

    /**
     * Dismiss review notice.
     *
     * @since 5.4.0
     *
     * @return \WP_REST_Response
     */
    public function dismissReviewNotice()
    {
        $success = update_option('beyondwords_notice_review_dismissed', gmdate(\DateTime::ATOM));

        return new \WP_REST_Response(
            [
                'success' => $success
            ],
            $success ? 200 : 500
        );
    }

    /**
     * Register the settings script.
     *
     * @since 5.0.0
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public function enqueueScripts($hook)
    {
        if ($hook === 'settings_page_beyondwords') {
            // jQuery UI JS
            wp_enqueue_script('jquery-ui-core');// enqueue jQuery UI Core
            wp_enqueue_script('jquery-ui-tabs');// enqueue jQuery UI Tabs

            // Plugin settings JS
            wp_register_script(
                'beyondwords-settings',
                BEYONDWORDS__PLUGIN_URI . 'build/settings.js',
                ['jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'underscore', 'tom-select'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );

            // Tom Select JS
            wp_enqueue_script(
                'tom-select',
                'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js', // phpcs:ignore
                [],
                '2.2.2',
                true
            );

            // Plugin settings CSS
            wp_enqueue_style(
                'beyondwords-settings',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Settings/settings.css',
                'forms',
                BEYONDWORDS__PLUGIN_VERSION
            );

            // Tom Select CSS
            wp_enqueue_style(
                'tom-select',
                'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css', // phpcs:ignore
                false,
                BEYONDWORDS__PLUGIN_VERSION
            );

            /**
             * Localize the script to handle ajax requests
             */
            wp_add_inline_script(
                'beyondwords-settings',
                '
                var beyondwordsData = beyondwordsData || {};
                beyondwordsData.nonce = "' . wp_create_nonce('wp_rest') . '";
                beyondwordsData.root = "' . esc_url_raw(rest_url()) . '";
                ',
                'before',
            );

            wp_enqueue_script('beyondwords-settings');
        }
    }
}