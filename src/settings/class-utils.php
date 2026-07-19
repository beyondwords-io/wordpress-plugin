<?php
/**
 * BeyondWords settings utilities.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings utilities.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Utils {

	/**
	 * Built-in post types BeyondWords never considers, regardless of `supports`.
	 *
	 * @var string[]
	 */
	const SKIP_POST_TYPES = [
		'attachment',
		'custom_css',
		'customize_changeset',
		'nav_menu_item',
		'oembed_cache',
		'revision',
		'user_request',
		'wp_block',
		'wp_font_face',
		'wp_font_family',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
	];

	/**
	 * Required `supports` features for a post type to be eligible.
	 *
	 * BeyondWords needs a title and an editor (body) to generate content,
	 * and custom fields to store post-level audio metadata.
	 *
	 * @var string[]
	 */
	const REQUIRED_FEATURES = [ 'title', 'editor', 'custom-fields' ];

	/**
	 * How long a connection check is trusted before re-validating.
	 */
	const CONNECTION_CHECK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Transient that throttles connection re-validation.
	 *
	 * Holds a fingerprint of the last-checked credentials, so changing the API
	 * key or project ID busts the throttle and forces an immediate re-check.
	 */
	const CONNECTION_CHECK_TRANSIENT = 'beyondwords_api_connection_checked';

	/**
	 * Get the post types eligible for BeyondWords audio generation.
	 *
	 * @return string[]
	 */
	public static function get_compatible_post_types(): array {
		// Reindex before passing to the filter — callers expect a 0-indexed list,
		// not the associative `[name => name]` shape `get_post_types()` returns.
		$post_types = array_values( array_diff( get_post_types(), self::SKIP_POST_TYPES ) );

		/**
		 * Filters the post types BeyondWords considers compatible.
		 *
		 * Types lacking the required `supports` features are still dropped.
		 *
		 * @since 3.3.3 Introduced as `beyondwords_post_types`.
		 * @since 4.3.0 Renamed to `beyondwords_settings_post_types`.
		 *
		 * @param string[] $post_types Candidate post type names.
		 */
		$post_types = apply_filters( 'beyondwords_settings_post_types', $post_types );

		$post_types = array_filter( $post_types, [ self::class, 'post_type_supports_required_features' ] );

		return array_values( $post_types );
	}

	/**
	 * Whether a post type supports every feature BeyondWords requires.
	 *
	 * Unregistered types pass through — they were added by the
	 * `beyondwords_settings_post_types` filter, which we treat as authoritative.
	 *
	 * @param string $post_type Post type slug.
	 */
	public static function post_type_supports_required_features( string $post_type ): bool {
		if ( ! post_type_exists( $post_type ) ) {
			return true;
		}

		foreach ( self::REQUIRED_FEATURES as $feature ) {
			if ( ! post_type_supports( $post_type, $feature ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether both API key and project ID are set.
	 */
	public static function has_api_creds(): bool {
		$project_id = trim( (string) get_option( 'beyondwords_project_id' ) );
		$api_key    = trim( (string) get_option( 'beyondwords_api_key' ) );

		return '' !== $project_id && '' !== $api_key;
	}

	/**
	 * Whether the saved credentials produced a valid REST API connection.
	 *
	 * The flag is set the last time validation succeeded — it does not
	 * prove the credentials are still valid right now.
	 */
	public static function has_valid_api_connection(): bool {
		return (bool) get_option( 'beyondwords_valid_api_connection' );
	}

	/**
	 * Validate the BeyondWords REST API connection and persist the result.
	 *
	 * Throttled per credential fingerprint; only a definitive auth failure
	 * (401/403) clears the stored flag, so an API blip can't hide the other tabs.
	 */
	public static function validate_api_connection(): bool {
		$project_id = get_option( 'beyondwords_project_id' );
		$api_key    = get_option( 'beyondwords_api_key' );

		if ( ! $project_id || ! $api_key ) {
			delete_option( 'beyondwords_valid_api_connection' );
			return false;
		}

		$fingerprint = md5( (string) $project_id . '|' . (string) $api_key );

		// Within the throttle window, trust the last result for these credentials.
		if ( get_transient( self::CONNECTION_CHECK_TRANSIENT ) === $fingerprint ) {
			return self::has_valid_api_connection();
		}

		$url      = sprintf( '%s/projects/%d', \BeyondWords\Core\Urls::get_api_url(), $project_id );
		$response = \BeyondWords\Api\Client::call_api( 'GET', $url );

		// Record the attempt whatever the outcome — a down API is throttled too.
		set_transient( self::CONNECTION_CHECK_TRANSIENT, $fingerprint, self::CONNECTION_CHECK_TTL );

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === (int) $response_code ) {
			update_option( 'beyondwords_valid_api_connection', gmdate( \DateTime::ATOM ), false );
			return true;
		}

		// 403 is a definitive auth failure (call_api() already handles 401); any
		// other response is treated as transient and leaves the flag untouched.
		if ( 403 === (int) $response_code ) {
			delete_option( 'beyondwords_valid_api_connection' );
		}

		$debug = sprintf(
			'<code>%s</code>: <code>%s</code>',
			$response_code,
			wp_remote_retrieve_body( $response )
		);

		self::add_settings_error_message(
			sprintf(
				/* translators: %s is replaced with the BeyondWords REST API response debug data */
				__( 'We were unable to validate your BeyondWords REST API connection.<br />Please check your project ID and API key, save changes, and contact us for support if this message remains.<br /><br />BeyondWords REST API Response:<br />%s', 'speechkit' ),
				$debug
			),
			'Settings/ValidApiConnection'
		);

		return false;
	}

	/**
	 * Queue a settings error message for later rendering.
	 *
	 * Uses a short-lived transient rather than the object cache so the message
	 * survives the Settings API's post-save redirect on any host.
	 *
	 * @param string $message  Error message (HTML allowed; rendered through `wp_kses`).
	 * @param string $error_id Optional stable ID; auto-generated when blank.
	 */
	public static function add_settings_error_message( string $message, string $error_id = '' ): void {
		$errors = get_transient( 'beyondwords_settings_errors' );

		if ( ! is_array( $errors ) ) {
			$errors = [];
		}

		if ( '' === $error_id ) {
			$error_id = bin2hex( random_bytes( 8 ) );
		}

		$errors[ $error_id ] = $message;

		set_transient( 'beyondwords_settings_errors', $errors, 30 );
	}
}
