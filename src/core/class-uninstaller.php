<?php
/**
 * Uninstall cleanup.
 *
 * Invoked from `uninstall.php` when WordPress removes the plugin. Deletes
 * every option, transient, and post-meta key recorded in `CoreUtils`.
 *
 * @package BeyondWords\Core
 * @since   3.7.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Database cleanup helpers used during plugin uninstall.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Uninstaller {

	/**
	 * Delete every BeyondWords transient.
	 *
	 * @return int Rows deleted.
	 */
	public static function cleanup_plugin_transients(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_beyondwords_%'" );

		return (int) $count;
	}

	/**
	 * Delete every BeyondWords plugin option (current + deprecated).
	 *
	 * Iterates the list from `CoreUtils::get_options( 'all' )` so this stays
	 * in sync with new keys without uninstall code changes.
	 *
	 * @return int Options deleted.
	 */
	public static function cleanup_plugin_options(): int {
		$options = CoreUtils::get_options( 'all' );
		$total   = 0;

		foreach ( $options as $option ) {
			$deleted = is_multisite() ? delete_site_option( $option ) : delete_option( $option );

			if ( $deleted ) {
				++$total;
			}
		}

		return $total;
	}

	/**
	 * Delete every BeyondWords post-meta value.
	 *
	 * Done one meta_id at a time to keep individual queries fast on sites with
	 * many posts — the alternative `DELETE … WHERE meta_key IN (…)` can lock
	 * the postmeta table for unacceptably long.
	 *
	 * @return int Meta rows deleted.
	 */
	public static function cleanup_custom_fields(): int {
		global $wpdb;

		$fields = CoreUtils::get_post_meta_keys( 'all' );
		$total  = 0;

		foreach ( $fields as $field ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT `meta_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s;", $field ) );

			if ( empty( $meta_ids ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$count = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE `meta_id` IN ( " . implode( ',', array_map( 'intval', $meta_ids ) ) . ' );' );

			if ( $count ) {
				$total += (int) $count;
			}
		}

		return $total;
	}
}
