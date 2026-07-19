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
 * Helpers shared across the settings classes — post-type compatibility
 * checks, API credential checks, and the BeyondWords REST connection check.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Utils {

	/**
	 * Built-in post types that BeyondWords never considers, regardless of
	 * their `supports` array.
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
	 * How long a connection check is trusted before the settings page
	 * re-validates. Throttles the API call to once per window per credential
	 * set, keeping it off the admin render hot path.
	 */
	const CONNECTION_CHECK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Transient that throttles connection re-validation. Its value is a
	 * fingerprint of the credentials last checked, so changing the API key or
	 * project ID busts the throttle and forces an immediate re-check.
	 */
	const CONNECTION_CHECK_TRANSIENT = 'beyondwords_api_connection_checked';

	/**
	 * Get the post types eligible for BeyondWords audio generation.
	 *
	 * Filterable via `beyondwords_settings_post_types` so a site can opt
	 * additional types in or out.
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
		 * Even if a type is added via this filter, it is dropped if it lacks
		 * the required `supports` features (title, editor, custom-fields).
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
	 * Unregistered post types pass through — they're slugs added by the
	 * `beyondwords_settings_post_types` filter and we treat the filter as
	 * authoritative. Registered types are checked against `REQUIRED_FEATURES`.
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
	 * The last successful check is recorded as a timestamp in the
	 * `beyondwords_valid_api_connection` option, which gates the visibility of
	 * the Integration and Preferences tabs (see `Tabs::get_visible_tabs()`).
	 *
	 * This runs on every settings-page load, so the network call is
	 * short-circuited to keep it off the admin render hot path:
	 *
	 * 1. A throttle transient ({@see self::CONNECTION_CHECK_TRANSIENT}), keyed to
	 *    a fingerprint of the current credentials, limits re-validation to once
	 *    per {@see self::CONNECTION_CHECK_TTL}. Changing the API key or project ID
	 *    changes the fingerprint, so saving new credentials re-validates
	 *    immediately rather than waiting out the throttle window.
	 * 2. The request uses the client's short default timeout
	 *    ({@see \BeyondWords\Api\Client::DEFAULT_REQUEST_TIMEOUT}) so a slow API
	 *    can't block the page render.
	 *
	 * The stored flag is only cleared on a definitive auth failure (401/403) —
	 * `Client::call_api()` clears it on 401 and we mirror that for 403 here. A
	 * transient failure (timeout, DNS error, 5xx, `WP_Error`) leaves the last
	 * known-good flag intact so an API blip can't hide the other settings tabs.
	 */
	public static function validate_api_connection(): bool {
		$project_id = get_option( 'beyondwords_project_id' );
		$api_key    = get_option( 'beyondwords_api_key' );

		if ( ! $project_id || ! $api_key ) {
			// No credentials means no connection — reflect that in the flag.
			delete_option( 'beyondwords_valid_api_connection' );
			return false;
		}

		$fingerprint = md5( (string) $project_id . '|' . (string) $api_key );

		// Throttle: within the window, trust the last recorded result for these
		// exact credentials and skip the network call entirely.
		if ( get_transient( self::CONNECTION_CHECK_TRANSIENT ) === $fingerprint ) {
			return self::has_valid_api_connection();
		}

		$url      = sprintf( '%s/projects/%d', \BeyondWords\Core\Urls::get_api_url(), $project_id );
		$response = \BeyondWords\Api\Client::call_api( 'GET', $url );

		// Record the attempt whatever the outcome, so repeated page loads don't
		// re-issue the request — a down API is throttled just like a healthy one.
		set_transient( self::CONNECTION_CHECK_TRANSIENT, $fingerprint, self::CONNECTION_CHECK_TTL );

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === (int) $response_code ) {
			update_option( 'beyondwords_valid_api_connection', gmdate( \DateTime::ATOM ), false );
			return true;
		}

		// 403 is a definitive auth failure (revoked key or wrong project), so
		// clear the flag; call_api() has already done so for 401. Every other
		// response is treated as transient and leaves the flag untouched.
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
	 * Stored in a short-lived transient — not the object cache — so the message
	 * survives the `wp_redirect()` the Settings API performs after a save. The
	 * default WordPress object cache is request-scoped, so `wp_cache_*` is empty
	 * by the time the redirected page load renders the notice on the majority of
	 * hosts (those without a persistent Redis/Memcached drop-in). A transient
	 * falls back to the options table on those hosts, so the message is never
	 * lost. The 30-second TTL matches WordPress core's own `settings_errors`
	 * transient and is ample for a single redirect.
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
