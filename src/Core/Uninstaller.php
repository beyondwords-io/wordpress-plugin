<?php

declare(strict_types=1);

/**
 * BeyondWords uninstaller.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 */

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * BeyondWords uninstaller.
 *
 * @since 3.7.0
 */
class Uninstaller
{
    /**
     * Clean up (delete) all BeyondWords plugin options.
     *
     * @since 3.7.0
     */
    public static function cleanupPluginOptions()
    {
        $options = [
            // v4.0.0
            'beyondwords_languages',
            'beyondwords_player_ui',
            'beyondwords_player_style',
            'beyondwords_player_version',
            'beyondwords_settings_updated',
            'beyondwords_valid_api_connection',
            // v3.7.0 beyondwords_*
            'beyondwords_version',
            'beyondwords_api_key',
            'beyondwords_project_id',
            'beyondwords_preselect',
            'beyondwords_prepend_excerpt',
            // v3.0.0 speechkit_*
            'speechkit_version',
            'speechkit_api_key',
            'speechkit_project_id',
            'speechkit_preselect',
            'speechkit_prepend_excerpt',
            // deprecated < v3.0
            'speechkit_settings',
            'speechkit_enable',
            'speechkit_id',
            'speechkit_select_post_types',
            'speechkit_selected_categories',
            'speechkit_enable_telemetry',
            'speechkit_rollbar_access_token',
            'speechkit_rollbar_error_notice',
            'speechkit_merge_excerpt',
            'speechkit_enable_marfeel_comp',
            'speechkit_wordpress_cron',
        ];

        $total = 0;

        foreach ($options as $option) {
            if (is_multisite()) {
                $deleted = delete_site_option($option);
            } else {
                $deleted = delete_option($option);
            }

            if ($deleted) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Clean up (delete) all BeyondWords custom fields.
     *
     * @since 3.7.0
     */
    public static function cleanupCustomFields()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'postmeta';

        $fields = CoreUtils::getPostMetaKeys('all');

        $total = 0;

        /*
        * Delete the custom fields one at a time to help prevent very slow
        * individual MySQL DELETE queries on sites with 1,000s of posts.
        */
        foreach ($fields as $field) {
            $query = $wpdb->prepare('SELECT `meta_id` FROM %1s WHERE `meta_key` = "%2s";', [$tableName, $field]);

            $meta_ids = $wpdb->get_col($query); // phpcs:ignore

            if (! count($meta_ids)) {
                continue;
            }

            $query = "DELETE FROM `{$tableName}` WHERE `meta_id` IN ( " . implode(',', $meta_ids) . ' );';

            $count = $wpdb->query($query); // phpcs:ignore

            if ($count) {
                $total += $count;
            }
        }

        return $total;
    }
}
