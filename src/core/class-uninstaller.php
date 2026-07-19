<?php
/**
 * Uninstall cleanup: deletes every BeyondWords option, transient and post-meta row.
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
	 * Run the full uninstall cleanup for every site on the install.
	 *
	 * `uninstall.php` runs once, in the main site's context, yet every value is
	 * stored per-site — so on multisite each site must be visited in turn.
	 *
	 * @since 7.0.0
	 *
	 * @return void
	 */
	public static function run(): void {
		if ( ! is_multisite() ) {
			self::cleanup_site();
			return;
		}

		// `number => 0` lifts the default 100-site cap.
		$site_ids = get_sites(
			[
				'fields' => 'ids',
				'number' => 0,
			]
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			self::cleanup_site();
			restore_current_blog();
		}
	}

	/**
	 * Delete every BeyondWords value for the current site.
	 *
	 * @since 7.0.0
	 *
	 * @return void
	 */
	private static function cleanup_site(): void {
		self::cleanup_plugin_transients();
		self::cleanup_plugin_options();
		self::cleanup_custom_fields();
	}

	/**
	 * Delete every BeyondWords transient.
	 *
	 * @return int Rows deleted.
	 */
	public static function cleanup_plugin_transients(): int {
		global $wpdb;

		// Transients are stored as `_transient_<key>` + `_transient_timeout_<key>`
		// option pairs; sweep both prefixes or the timeout rows are orphaned.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->query(
			"DELETE FROM $wpdb->options
			WHERE `option_name` LIKE '_transient_beyondwords_%'
			OR `option_name` LIKE '_transient_timeout_beyondwords_%'"
		);

		return (int) $count;
	}

	/**
	 * Delete every BeyondWords plugin option (current + deprecated).
	 *
	 * @return int Options deleted.
	 */
	public static function cleanup_plugin_options(): int {
		$options = Utils::get_options( 'all' );
		$total   = 0;

		foreach ( $options as $option ) {
			// Options are stored per-site via `update_option()`, so `delete_option()`
			// is the correct call on both single-site and multisite.
			if ( delete_option( $option ) ) {
				++$total;
			}

			// Defensive: a legacy install may have stored a matching network option.
			if ( is_multisite() ) {
				delete_site_option( $option );
			}
		}

		return $total;
	}

	/**
	 * Delete every BeyondWords post-meta value.
	 *
	 * Deletes by meta_id in per-key batches — a single `DELETE … WHERE meta_key
	 * IN (…)` can lock the postmeta table for unacceptably long.
	 *
	 * @return int Meta rows deleted.
	 */
	public static function cleanup_custom_fields(): int {
		global $wpdb;

		$fields = Utils::get_post_meta_keys( 'all' );
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
