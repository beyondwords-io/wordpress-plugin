<?php
/**
 * Plugin update routines.
 *
 * Runs on every page load; cheap when nothing's changed (the version compare
 * short-circuits). Each migration is gated on a version bump so it executes
 * exactly once per upgrade path.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Version-gated migrations.
 */
class Updater {

	/**
	 * Run any pending migrations and update the recorded plugin version.
	 *
	 * Always runs — version checks inside skip the migrations on no-op upgrades.
	 */
	public static function run(): void {
		$version = get_option( 'beyondwords_version', '1.0.0' );

		if ( version_compare( $version, '3.0.0', '<' ) ) {
			self::migrate_settings();
		}

		if ( version_compare( $version, '3.7.0', '<' ) ) {
			self::rename_plugin_settings();
		}

		if ( version_compare( $version, '7.0.0', '<' ) ) {
			self::flatten_preselect();
		}

		// Record the activation timestamp the first time we run.
		add_option( 'beyondwords_date_activated', gmdate( \DateTime::ATOM ), '', false );

		// Always update the plugin version so FTP uploads and similar bypasses still register.
		update_option( 'beyondwords_version', BEYONDWORDS__PLUGIN_VERSION );
	}

	/**
	 * Flatten the legacy preselect format.
	 *
	 * Pre-7.0.0, `beyondwords_preselect` could store taxonomy term arrays per
	 * post type (e.g. `['post' => ['category' => ['1','2']]]`). 7.0.0
	 * simplifies to `['post' => '1']` — a flag per post type.
	 *
	 * Per spec: a post type stays selected if its old value was the literal
	 * string `'1'` OR a non-empty array of taxonomy terms.
	 *
	 * @since 7.0.0
	 */
	public static function flatten_preselect(): void {
		$preselect = get_option( 'beyondwords_preselect' );

		if ( ! is_array( $preselect ) ) {
			return;
		}

		$flattened = array();

		foreach ( $preselect as $post_type => $value ) {
			if ( '1' === $value ) {
				$flattened[ (string) $post_type ] = '1';
				continue;
			}

			if ( is_array( $value ) && ! empty( $value ) ) {
				$flattened[ (string) $post_type ] = '1';
			}
		}

		update_option( 'beyondwords_preselect', $flattened, false );
	}

	/**
	 * v3.0.0: migrate `speechkit_settings.*` array values to top-level options.
	 *
	 * Skipped when `speechkit_settings` is empty (already migrated or never set).
	 */
	public static function migrate_settings(): void {
		$old_settings = get_option( 'speechkit_settings', array() );

		if ( ! is_array( $old_settings ) || empty( $old_settings ) ) {
			return;
		}

		$settings_map = array(
			'speechkit_api_key'       => 'speechkit_api_key',
			'speechkit_id'            => 'speechkit_project_id',
			'speechkit_merge_excerpt' => 'speechkit_prepend_excerpt',
		);

		foreach ( $settings_map as $old_key => $new_key ) {
			if ( array_key_exists( $old_key, $old_settings ) && ! get_option( $new_key ) ) {
				add_option( $new_key, $old_settings[ $old_key ] );
			}
		}

		if ( false === get_option( 'speechkit_preselect' ) ) {
			add_option( 'speechkit_preselect', self::construct_preselect_setting() );
		}
	}

	/**
	 * Build a v3 preselect array from the v2 `speechkit_select_post_types` +
	 * `speechkit_selected_categories` fields.
	 *
	 * @return array<string,mixed>|false `false` when the legacy `speechkit_settings` option is missing.
	 */
	public static function construct_preselect_setting(): array|false {
		$old_settings = get_option( 'speechkit_settings', array() );

		if ( ! is_array( $old_settings ) || empty( $old_settings ) ) {
			return false;
		}

		$preselect = array();

		if (
			array_key_exists( 'speechkit_select_post_types', $old_settings )
			&& ! empty( $old_settings['speechkit_select_post_types'] )
		) {
			$preselect = array_fill_keys( $old_settings['speechkit_select_post_types'], '1' );
		}

		if (
			array_key_exists( 'speechkit_selected_categories', $old_settings )
			&& ! empty( $old_settings['speechkit_selected_categories'] )
		) {
			$taxonomy = get_taxonomy( 'category' );

			if ( $taxonomy && is_array( $taxonomy->object_type ) ) {
				foreach ( $taxonomy->object_type as $post_type ) {
					$preselect[ $post_type ] = array(
						'category' => $old_settings['speechkit_selected_categories'],
					);
				}
			}
		}

		return $preselect;
	}

	/**
	 * v3.7.0: copy `speechkit_*` option keys to `beyondwords_*` while leaving
	 * the originals in place so plugin downgrades remain safe.
	 */
	public static function rename_plugin_settings(): void {
		$api_key         = get_option( 'speechkit_api_key' );
		$project_id      = get_option( 'speechkit_project_id' );
		$prepend_excerpt = get_option( 'speechkit_prepend_excerpt' );
		$preselect       = get_option( 'speechkit_preselect' );

		if ( $api_key ) {
			update_option( 'beyondwords_api_key', $api_key, false );
		}

		if ( $project_id ) {
			update_option( 'beyondwords_project_id', $project_id, false );
		}

		if ( $prepend_excerpt ) {
			update_option( 'beyondwords_prepend_excerpt', $prepend_excerpt, false );
		}

		if ( $preselect ) {
			update_option( 'beyondwords_preselect', $preselect, false );
		}
	}
}
