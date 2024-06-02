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
use Beyondwords\Wordpress\Component\Settings\Fields\SettingsUpdated\SettingsUpdated;
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
        // (new SettingsUpdated())->init();

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
        $sync = false;

        $options = SettingsUtils::getSyncedOptions('auth');

        foreach ($options as $name => $args) {
            $t = get_transient('beyondwords/sync/' . $name);
            if ($t !== false) {
                $sync = true;
                delete_transient('beyondwords/sync/' . $name);
            }
        }

        if (! $sync) {
            return;
        }

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

        if ($validConnection) {
            update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

            if ($project['language']) {
                $updated = update_option('beyondwords_project_language', $project['language'], false);
                if ($updated) {
                    add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project language has been synced to WordPress', 'success');
                }
            }
            if ($project['body'] && $project['body']['voice'] && $project['body']['voice']['id']) {
                $updated = update_option('beyondwords_body_voice_id', $project['body']['voice']['id'], false);
                if ($updated) {
                    add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project body.voice.id has been synced to WordPress', 'success');
                }
            }
            if ($project['title'] && $project['title']['voice'] && $project['title']['voice']['id']) {
                $updated = update_option('beyondwords_title_voice_id', $project['title']['voice']['id'], false);
                if ($updated) {
                    add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project title.voice.id has been synced to WordPress', 'success');
                }
            }
        } else {
            $errors = get_transient('beyondwords_settings_errors', []);

            $errors['Settings/ValidApiConnection'] = __(
                'Please check and re-enter your BeyondWords API key and project ID. They appear to be invalid.',
                'speechkit'
            );

            set_transient('beyondwords_settings_errors', $errors);

            return false;
        }

        $updated = [];

        // Sync REST API -> WordPress
        $playerSettings = $this->apiClient->getPlayerSettings();

        if ($playerSettings && is_array($playerSettings)) {
            $options = SettingsUtils::getSyncedOptions('player');

            foreach ($options as $name => $args) {
                // wp_die(wp_json_encode($playerSettings));

                if (array_key_exists('path', $args) && !empty($playerSettings[$args['path']])) {
                    $updated = update_option($name, $playerSettings[$args['path']], false);

                    if ($updated) {
                        add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> REST API project language has been synced to WordPress', 'success');
                    }
                }
            }
        } else {
            add_settings_error('beyondwords_settings', 'beyondwords_settings', '<span class="dashicons dashicons-rest-api"></span> Unable to reach BeyondWords REST API to access the player settings.', 'error');
        }
    }
}
