<?php
/**
 * Cross-cutting helpers and the canonical lists of plugin options + post-meta keys.
 *
 * @package BeyondWords\Core
 * @since   3.5.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Core-wide utilities: editor-screen detection and the option/meta-key registries
 * the uninstaller iterates.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class CoreUtils {

	/**
	 * Whether the current admin page is the Gutenberg/Block Editor.
	 *
	 * @link https://wordpress.stackexchange.com/a/324866
	 */
	public static function is_gutenberg_page(): bool {
		// phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.gd_isgutenbergDeprecated -- legacy gutenberg plugin compat
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return true;
		}

		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $current_screen ) {
			return false;
		}

		if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the current admin screen is a post-edit screen (single or list).
	 */
	public static function is_edit_screen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		return 'edit' === $screen->parent_base || 'post' === $screen->base;
	}

	/**
	 * Whether the current request is being served as AMP.
	 *
	 * Tries the official AMP plugin, AMP for WP, and the legacy `is_amp_endpoint()`.
	 */
	public static function is_amp(): bool {
		return (
			( function_exists( '\amp_is_request' ) && \amp_is_request() )
			|| ( function_exists( '\ampforwp_is_amp_endpoint' ) && \ampforwp_is_amp_endpoint() )
			|| ( function_exists( '\is_amp_endpoint' ) && \is_amp_endpoint() )
		);
	}

	/**
	 * BeyondWords post-meta keys.
	 *
	 * The `current` set is registered with REST + sanitisation; the `deprecated`
	 * set is only used by the uninstaller.
	 *
	 * @param string $type One of `current`, `deprecated`, `all`.
	 *
	 * @return string[]
	 *
	 * @throws \Exception When `$type` is unrecognised.
	 */
	public static function get_post_meta_keys( string $type = 'current' ): array {
		$current = [
			'beyondwords_generate_audio',
			'beyondwords_integration_method',
			'beyondwords_project_id',
			'beyondwords_content_id',
			'beyondwords_preview_token',
			'beyondwords_player_content',
			'beyondwords_player_style',
			'beyondwords_language_code',
			'beyondwords_language_id',
			'beyondwords_title_voice_id',
			'beyondwords_body_voice_id',
			'beyondwords_summary_voice_id',
			'beyondwords_error_message',
			'beyondwords_disabled',
			'beyondwords_delete_content',
		];

		$deprecated = [
			'beyondwords_podcast_id',
			'beyondwords_hash',
			'publish_post_to_speechkit',
			'speechkit_hash',
			'speechkit_generate_audio',
			'speechkit_project_id',
			'speechkit_podcast_id',
			'speechkit_error_message',
			'speechkit_disabled',
			'speechkit_access_key',
			'speechkit_error',
			'speechkit_info',
			'speechkit_response',
			'speechkit_retries',
			'speechkit_status',
			'speechkit_updated_at',
			'_speechkit_link',
			'_speechkit_text',
		];

		return match ( $type ) {
			'current'    => $current,
			'deprecated' => $deprecated,
			'all'        => array_merge( $current, $deprecated ),
			default      => throw new \Exception( 'Unexpected $type param for CoreUtils::get_post_meta_keys()' ),
		};
	}

	/**
	 * BeyondWords plugin option keys.
	 *
	 * Removed-in-7.0 settings are grouped into `deprecated` so the uninstaller
	 * still cleans them up for users upgrading from 6.x.
	 *
	 * @param string $type One of `current`, `deprecated`, `all`.
	 *
	 * @return string[]
	 *
	 * @throws \Exception When `$type` is unrecognised.
	 */
	public static function get_options( string $type = 'current' ): array {
		$current = [
			// v7.x.
			'beyondwords_api_key',
			'beyondwords_date_activated',
			'beyondwords_integration_method',
			'beyondwords_notice_review_dismissed',
			'beyondwords_player_ui',
			'beyondwords_prepend_excerpt',
			'beyondwords_preselect',
			'beyondwords_project_id',
			'beyondwords_settings_updated',
			'beyondwords_valid_api_connection',
			'beyondwords_version',
			// Debug tool (extension plugin).
			'beyondwords_debug_rest_api',
			'beyondwords_debug_log_token',
		];

		$deprecated = [
			// Removed in v7.0.0 — settings UI consolidated to three tabs and
			// player/project styling moved to the BeyondWords dashboard.
			'beyondwords_player_call_to_action',
			'beyondwords_player_clickable_sections',
			'beyondwords_player_content',
			'beyondwords_player_highlight_sections',
			'beyondwords_player_skip_button_style',
			'beyondwords_player_style',
			'beyondwords_player_theme',
			'beyondwords_player_theme_dark',
			'beyondwords_player_theme_light',
			'beyondwords_player_theme_video',
			'beyondwords_player_version',
			'beyondwords_player_widget_position',
			'beyondwords_player_widget_style',
			'beyondwords_project_auto_publish_enabled',
			'beyondwords_project_body_voice_id',
			'beyondwords_project_body_voice_speaking_rate',
			'beyondwords_project_language_code',
			'beyondwords_project_language_id',
			'beyondwords_project_title_enabled',
			'beyondwords_project_title_voice_id',
			'beyondwords_project_title_voice_speaking_rate',
			'beyondwords_video_enabled',
			// v4.x.
			'beyondwords_languages',
			// v3.0.0 speechkit_*.
			'speechkit_api_key',
			'speechkit_prepend_excerpt',
			'speechkit_preselect',
			'speechkit_project_id',
			'speechkit_version',
			// Pre-v3.0.
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

		return match ( $type ) {
			'current'    => $current,
			'deprecated' => $deprecated,
			'all'        => array_merge( $current, $deprecated ),
			default      => throw new \Exception( 'Unexpected $type param for CoreUtils::get_options()' ),
		};
	}
}
