<?php
/**
 * Uninstall cleanup.
 *
 * Invoked from `uninstall.php` when WordPress removes the plugin. Deletes
 * every option, transient, and post-meta key recorded in `Utils`. On
 * multisite each site is cleaned in turn, since `uninstall.php` only runs
 * once — in the network's main-site context — and every value is stored
 * per-site.
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
	 * WordPress executes `uninstall.php` a single time, in the context of the
	 * network's main site. Every BeyondWords value — options, transients and
	 * post-meta — is stored per-site (`update_option()` and the per-site
	 * `options`/`postmeta` tables); the plugin never writes network/site
	 * options. So on multisite we must visit each site in turn, otherwise only
	 * the main site is cleaned and every subsite keeps its settings, including
	 * the `beyondwords_api_key` secret. Single-site installs are cleaned in one
	 * pass.
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

		// `number => 0` lifts the default 100-site cap so no site is skipped on
		// large networks.
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
	 * Delete every BeyondWords option, transient and post-meta value for the
	 * current site.
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

		// Each transient is stored as a pair of options — `_transient_<key>` and
		// `_transient_timeout_<key>` — so both prefixes must be swept, otherwise
		// the timeout rows are left orphaned. (On external-object-cache hosts,
		// e.g. VIP, there are no option rows to delete; those entries hold a TTL
		// and self-expire.)
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
	 * Iterates the list from `Utils::get_options( 'all' )` so this stays
	 * in sync with new keys without uninstall code changes.
	 *
	 * @return int Options deleted.
	 */
	public static function cleanup_plugin_options(): int {
		$options = Utils::get_options( 'all' );
		$total   = 0;

		foreach ( $options as $option ) {
			// Every option is stored per-site via `update_option()`, so
			// `delete_option()` is the correct call on both single-site and
			// multisite. The previous `delete_site_option()`-only branch swept
			// `wp_sitemeta` rows that were never written and left every real
			// option — including the `beyondwords_api_key` secret — in place on
			// multisite.
			if ( delete_option( $option ) ) {
				++$total;
			}

			// Defensive: a legacy install may have stored a matching network
			// (site) option. This is network-global and idempotent, so it is a
			// harmless no-op when nothing was stored.
			if ( is_multisite() ) {
				delete_site_option( $option );
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
