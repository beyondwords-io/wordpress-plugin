<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > General
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\General;

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * "General" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class General
{
    /**
     * API client.
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 4.8.0
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

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
        add_action('admin_enqueue_scripts', array($this, 'syncCheck'), 1, 1);
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'credentials',
            __('Credentials', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_general',
            // [
            //     'before_section' => '<div id="general" data-tab="general">' . $this->dashboardLink(),
            //     'after_section' => '</div>',
            // ]
        );
    }

    /**
     * Section callback
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function sectionCallback()
    {
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
     * @since 3.0.0
     * @since 4.8.0 Moved from Settings/Settings to Settings/Tabs/General.
     *
     * @return string
     */
    public function dashboardLink()
    {
        $projectId = get_option('beyondwords_project_id');

        if ($projectId) :
            ob_start();
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
            return ob_get_clean();
        endif;

        return '';
    }

    public function syncCheck($hook)
    {
        if ($hook === 'settings_page_beyondwords') {
            $this->syncFromRestApi();
        }
    }

    /**
     * Sync with BeyondWords REST API.
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function syncFromRestApi()
    {
        // Assume invalid connection
        delete_option('beyondwords_valid_api_connection');

        // Sync REST API -> WordPress
        $project = $this->apiClient->getProject();

        $validConnection = (
            is_array($project)
            && array_key_exists('id', $project)
            && strval($project['id']) === get_option('beyondwords_project_id')
        );

        $updated = [];

        if (! $validConnection) {
            $errors = get_transient('beyondwords_settings_errors', []);

            $errors['Settings/ValidApiConnection'] = __(
                'Please check and re-enter your BeyondWords API key and project ID. They appear to be invalid.',
                'speechkit'
            );

            set_transient('beyondwords_settings_errors', $errors);

            return false;
        }

        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        if (false === get_option('beyondwords_project_language') && $project['language']) {
            $updated = update_option('beyondwords_project_language', $project['language'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.language has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_project_body_voice_id') && $project['body']['voice']['id']) {
            $updated = update_option('beyondwords_project_body_voice_id', $project['body']['voice']['id'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.body has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_project_title_voice_id') && $project['title']['voice']['id']) {
            $updated = update_option('beyondwords_project_title_voice_id', $project['title']['voice']['id'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project.title has been synced to WordPress', 'success');
            }
        }

        // Sync Player Settings from REST API -> WordPress
        $playerSettings = $this->apiClient->getPlayerSettings();

        if (! $playerSettings || ! is_array($playerSettings)) {
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Unable to reach BeyondWords REST API to access the player settings.', 'error');

            return;
        }

        if (false === get_option('beyondwords_player_style') && $playerSettings['player_style']) {
            $updated = update_option('beyondwords_player_style', $playerSettings['player_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.player_style has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_theme') && $playerSettings['theme']) {
            $updated = update_option('beyondwords_player_theme', $playerSettings['theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_dark_theme') && $playerSettings['dark_theme']) {
            $updated = update_option('beyondwords_player_dark_theme', $playerSettings['dark_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.dark_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_light_theme') && $playerSettings['light_theme']) {
            $updated = update_option('beyondwords_player_light_theme', $playerSettings['light_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.light_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_video_theme') && $playerSettings['video_theme']) {
            $updated = update_option('beyondwords_player_video_theme', $playerSettings['video_theme'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.video_theme has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_call_to_action') && $playerSettings['call_to_action']) {
            $updated = update_option('beyondwords_player_call_to_action', $playerSettings['call_to_action'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.call_to_action has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_widget_style') && $playerSettings['widget_style']) {
            $updated = update_option('beyondwords_player_widget_style', $playerSettings['widget_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.widget_style has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_widget_position') && $playerSettings['widget_position']) {
            $updated = update_option('beyondwords_player_widget_position', $playerSettings['widget_position'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.widget_position has been synced to WordPress', 'success');
            }
        }

        if (false === get_option('beyondwords_player_skip_button_style') && $playerSettings['skip_button_style']) {
            $updated = update_option('beyondwords_player_skip_button_style', $playerSettings['skip_button_style'], false);
            if ($updated) {
                add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API player.skip_button_style has been synced to WordPress', 'success');
            }
        }
    }
}
