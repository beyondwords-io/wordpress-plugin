<?php
/**
 * Plugin update routines: version-gated migrations run on every page load.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Version-gated migrations.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Updater {

	/**
	 * Run any pending migrations and update the recorded plugin version.
	 *
	 * The exact-version bail — not the `version_compare` gates — keeps the common
	 * path cheap: a pre-release like `7.0.0-beta.1` compares `< 7.0.0` forever.
	 */
	public static function run(): void {
		$version = get_option( 'beyondwords_version', '1.0.0' );

		if ( BEYONDWORDS__PLUGIN_VERSION === $version ) {
			return;
		}

		if ( version_compare( $version, '3.0.0', '<' ) ) {
			self::migrate_settings();
		}

		if ( version_compare( $version, '3.7.0', '<' ) ) {
			self::rename_plugin_settings();
		}

		if ( version_compare( $version, '7.0.0', '<' ) ) {
			self::migrate_preselect_format();
			self::delete_deprecated_options();
			self::migrate_disabled_to_embed_none();
		}

		// Record the activation timestamp the first time we run.
		add_option( 'beyondwords_date_activated', gmdate( \DateTime::ATOM ), '', false );

		// Always update the plugin version so FTP uploads and similar bypasses still register.
		update_option( 'beyondwords_version', BEYONDWORDS__PLUGIN_VERSION );
	}

	/**
	 * Convert the legacy preselect option to the 7.0.0 mode-based format.
	 *
	 * Reuses the tolerant readers on `Preselect`, so it is idempotent — which
	 * keeps the re-runs pre-release builds trigger (`< 7.0.0` forever) safe.
	 *
	 * @since 7.0.0
	 */
	public static function migrate_preselect_format(): void {
		$preselect = get_option( 'beyondwords_preselect' );

		if ( ! is_array( $preselect ) ) {
			return;
		}

		$migrated = [];

		foreach ( $preselect as $post_type => $value ) {
			$post_type = (string) $post_type;
			$single    = [ $post_type => $value ];

			$mode = \BeyondWords\Settings\Preselect::get_mode( $post_type, $single );

			if ( \BeyondWords\Settings\Preselect::MODE_ALL === $mode ) {
				$migrated[ $post_type ] = [ 'mode' => \BeyondWords\Settings\Preselect::MODE_ALL ];
			} elseif ( \BeyondWords\Settings\Preselect::MODE_TERMS === $mode ) {
				$terms = \BeyondWords\Settings\Preselect::get_selected_terms( $post_type, $single );

				if ( ! empty( $terms ) ) {
					$migrated[ $post_type ] = [
						'mode'  => \BeyondWords\Settings\Preselect::MODE_TERMS,
						'terms' => $terms,
					];
				}
			}
			// MODE_OFF (empty / unrecognised) → dropped.
		}

		update_option( 'beyondwords_preselect', $migrated, false );
	}

	/**
	 * v7.0.0: remove every option marked deprecated in `Utils::get_options()`.
	 *
	 * Multisite-aware because legacy installs may have stored these as site options.
	 *
	 * @since 7.0.0
	 */
	public static function delete_deprecated_options(): void {
		foreach ( Utils::get_options( 'deprecated' ) as $option ) {
			if ( is_multisite() ) {
				delete_site_option( $option );
			} else {
				delete_option( $option );
			}
		}
	}

	/**
	 * v7.0.0: migrate the legacy `beyondwords_disabled` opt-out to Embed "None".
	 *
	 * Carries the flag forward so previously-hidden players stay hidden; never
	 * overwrites an existing Embed value, and batches to keep memory bounded.
	 *
	 * @since 7.0.0
	 */
	public static function migrate_disabled_to_embed_none(): void {
		$batch = 100;

		do {
			$post_ids = get_posts(
				[
					'post_type'   => 'any',
					'post_status' => 'any',
					'numberposts' => $batch,
					'fields'      => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'  => [
						[
							'key'   => 'beyondwords_disabled',
							'value' => '1',
						],
					],
				]
			);

			foreach ( $post_ids as $post_id ) {
				if ( '' === (string) get_post_meta( $post_id, 'beyondwords_embed', true ) ) {
					update_post_meta(
						$post_id,
						'beyondwords_embed',
						\BeyondWords\Editor\Components\SettingsFields::EMBED_NONE
					);
				}

				delete_post_meta( $post_id, 'beyondwords_disabled' );
			}

			$found = count( $post_ids );
		} while ( $found === $batch );
	}

	/**
	 * v3.0.0: migrate `speechkit_settings.*` array values to top-level options.
	 */
	public static function migrate_settings(): void {
		$old_settings = get_option( 'speechkit_settings', [] );

		if ( ! is_array( $old_settings ) || empty( $old_settings ) ) {
			return;
		}

		$settings_map = [
			'speechkit_api_key'       => 'speechkit_api_key',
			'speechkit_id'            => 'speechkit_project_id',
			'speechkit_merge_excerpt' => 'speechkit_prepend_excerpt',
		];

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
	 * Build a v3 preselect array from the v2 post-type + category settings.
	 *
	 * @return array<string,mixed>|false `false` when the legacy `speechkit_settings` option is missing.
	 */
	public static function construct_preselect_setting(): array|false {
		$old_settings = get_option( 'speechkit_settings', [] );

		if ( ! is_array( $old_settings ) || empty( $old_settings ) ) {
			return false;
		}

		$preselect = [];

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
					$preselect[ $post_type ] = [
						'category' => $old_settings['speechkit_selected_categories'],
					];
				}
			}
		}

		return $preselect;
	}

	/**
	 * v3.7.0: copy `speechkit_*` option keys to `beyondwords_*`.
	 *
	 * Originals are left in place so plugin downgrades remain safe.
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
