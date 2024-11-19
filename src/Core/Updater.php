<?php

declare(strict_types=1);

/**
 * BeyondWords updater.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Core;

/**
 * BeyondWords updater.
 *
 * @since 3.7.0
 */
class Updater
{
    /**
     * Run
     *
     * @since 4.0.0
     * @since 5.2.0 Add beyondwords_date_activated option.
     */
    public function run()
    {
        $version = get_option('beyondwords_version', '1.0.0');

        if (version_compare($version, '5.0.0', '<') && get_option('beyondwords_api_key')) {
            set_transient('beyondwords_sync_to_wordpress', ['all'], 30);
        }

        if (version_compare($version, '3.0.0', '<')) {
            $this->migrateSettings();
        }

        if (version_compare($version, '3.7.0', '<')) {
            $this->renamePluginSettings();
        }

        // Record the date activated so we can track how long users have been using the plugin.
        add_option('beyondwords_date_activated', gmdate(\DateTime::ATOM), '', false);

        // Always update the plugin version, to handle e.g. FTP plugin updates
        update_option('beyondwords_version', BEYONDWORDS__PLUGIN_VERSION);
    }

    /**
     * Migrate settings.
     *
     * In v3.0.0 the locations for plugin settings changed. This method migrates settings from the
     * old location to the new. e.g. from speechkit_settings.speechkit_api_key to beyondwords_api_key.
     *
     * @since 3.0.0
     * @since 3.5.0 Refactored, adding $this->constructPreselectSetting().
     *
     * @return void
     */
    public function migrateSettings()
    {
        $oldSettings = get_option('speechkit_settings', []);

        if (! is_array($oldSettings) || empty($oldSettings)) {
            return;
        }

        $settingsMap = [
            'speechkit_api_key'       => 'speechkit_api_key',
            'speechkit_id'            => 'speechkit_project_id',
            'speechkit_merge_excerpt' => 'speechkit_prepend_excerpt',
        ];

        // Simple mapping of 'old key' -> 'new key'
        foreach ($settingsMap as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $oldSettings) && ! get_option($newKey)) {
                add_option($newKey, $oldSettings[$oldKey]);
            }
        }

        if (get_option('speechkit_preselect') === false) {
            $preselectSetting = $this->constructPreselectSetting();

            add_option('speechkit_preselect', $preselectSetting);
        }
    }

    /**
     * Construct 'beyondwords_preselect' setting.
     *
     * The v3 `beyondwords_preselect` setting is constructed from the data contained in the v2
     * `speechkit_select_post_types` and `speechkit_selected_categories` fields.
     *
     * @since 3.5.0
     *
     * @return array `beyondwords_preselect` setting.
     */
    public function constructPreselectSetting()
    {
        $oldSettings = get_option('speechkit_settings', []);

        if (! is_array($oldSettings) || empty($oldSettings)) {
            return false;
        }

        $preselect = [];

        // Build the top level of beyondwords_preselect, for post types
        if (
            array_key_exists('speechkit_select_post_types', $oldSettings) &&
            ! empty($oldSettings['speechkit_select_post_types'])
        ) {
            $preselect = array_fill_keys($oldSettings['speechkit_select_post_types'], '1');
        }

        // Build the taxonomy level of beyondwords_preselect
        if (
            array_key_exists('speechkit_selected_categories', $oldSettings) &&
            ! empty($oldSettings['speechkit_selected_categories'])
        ) {
            // Categories can be assigned to multiple post types
            $taxonomy = get_taxonomy('category');

            if (is_array($taxonomy->object_type)) {
                foreach ($taxonomy->object_type as $postType) {
                    // Post type: e.g. "post"
                    $preselect[$postType] = [
                        // Taxonomy: "category"
                        'category' => $oldSettings['speechkit_selected_categories'],
                    ];
                }
            }
        }

        return $preselect;
    }

    /**
     * Rename plugin settings.
     *
     * In v3.7.0 the plugin settings change from `speechkit_*` to `beyondwords_*`.
     *
     * For now, we will leave `speechkit_*` settings in the db, to support plugin downgrades.
     *
     * @since 3.7.0
     *
     * @return void
     */
    public function renamePluginSettings()
    {
        $apiKey         = get_option('speechkit_api_key');
        $projectId      = get_option('speechkit_project_id');
        $prependExcerpt = get_option('speechkit_prepend_excerpt');
        $preselect      = get_option('speechkit_preselect');

        if ($apiKey) {
            update_option('beyondwords_api_key', $apiKey, false);
        }

        if ($projectId) {
            update_option('beyondwords_project_id', $projectId, false);
        }

        if ($prependExcerpt) {
            update_option('beyondwords_prepend_excerpt', $prependExcerpt, false);
        }

        if ($preselect) {
            update_option('beyondwords_preselect', $preselect, false);
        }
    }
}
