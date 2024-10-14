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
     * Clean up (delete) all BeyondWords transients.
     *
     * @since 5.0.0
     *
     * @return int The number of transients deleted.
     */
    public static function cleanupPluginTransients()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_beyondwords_%'");

        return $count;
    }

    /**
     * Clean up (delete) all BeyondWords plugin options.
     *
     * @since 3.7.0
     *
     * @return int The number of options deleted.
     */
    public static function cleanupPluginOptions()
    {
        $options = CoreUtils::getOptions('all');

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
     * @since 4.6.1 Use $wpdb->postmeta variable for table name.
     *
     * @return int The number of custom fields deleted.
     */
    public static function cleanupCustomFields()
    {
        global $wpdb;

        $fields = CoreUtils::getPostMetaKeys('all');

        $total = 0;

        /*
        * Delete the custom fields one at a time to help prevent very slow
        * individual MySQL DELETE queries on sites with 1,000s of posts.
        */
        foreach ($fields as $field) {
            $metaIds = $wpdb->get_col($wpdb->prepare("SELECT `meta_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s;", $field)); // phpcs:ignore

            if (! count($metaIds)) {
                continue;
            }

            $count = $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE `meta_id` IN ( " . implode(',', $metaIds) . ' );'); // phpcs:ignore

            if ($count) {
                $total += $count;
            }
        }

        return $total;
    }
}
