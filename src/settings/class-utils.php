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
	const SKIP_POST_TYPES = array(
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
	);

	/**
	 * Required `supports` features for a post type to be eligible.
	 *
	 * BeyondWords needs a title and an editor (body) to generate content,
	 * and custom fields to store post-level audio metadata.
	 *
	 * @var string[]
	 */
	const REQUIRED_FEATURES = array( 'title', 'editor', 'custom-fields' );

	/**
	 * Get the post types eligible for BeyondWords audio generation.
	 *
	 * Filterable via `beyondwords_settings_post_types` so a site can opt
	 * additional types in or out.
	 *
	 * @return string[]
	 */
	public static function get_compatible_post_types(): array {
		$post_types = array_diff( get_post_types(), self::SKIP_POST_TYPES );

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

		$post_types = array_filter( $post_types, array( self::class, 'post_type_supports_required_features' ) );

		return array_values( $post_types );
	}

	/**
	 * Whether a post type supports every feature BeyondWords requires.
	 *
	 * @param string $post_type Post type slug.
	 */
	public static function post_type_supports_required_features( string $post_type ): bool {
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
	 * Stores the successful timestamp in `beyondwords_valid_api_connection`
	 * so subsequent admin page loads can short-circuit without an API call.
	 */
	public static function validate_api_connection(): bool {
		delete_transient( 'beyondwords_validate_api_connection' );
		delete_option( 'beyondwords_valid_api_connection' );

		$project_id = get_option( 'beyondwords_project_id' );
		$api_key    = get_option( 'beyondwords_api_key' );

		if ( ! $project_id || ! $api_key ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d', \BeyondWords\Core\Environment::get_api_url(), $project_id );
		$response = \BeyondWords\Core\ApiClient::call_api( new \BeyondWords\Core\Request( 'GET', $url ) );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			update_option( 'beyondwords_valid_api_connection', gmdate( \DateTime::ATOM ), false );
			return true;
		}

		$debug = sprintf(
			'<code>%s</code>: <code>%s</code>',
			wp_remote_retrieve_response_code( $response ),
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
	 * Errors are stored in the object cache so they survive the redirect that
	 * follows a settings save without relying on PHP session state.
	 *
	 * @param string $message  Error message (HTML allowed; rendered through `wp_kses`).
	 * @param string $error_id Optional stable ID; auto-generated when blank.
	 */
	public static function add_settings_error_message( string $message, string $error_id = '' ): void {
		$errors = wp_cache_get( 'beyondwords_settings_errors', 'beyondwords' );

		if ( empty( $errors ) ) {
			$errors = array();
		}

		if ( '' === $error_id ) {
			$error_id = bin2hex( random_bytes( 8 ) );
		}

		$errors[ $error_id ] = $message;

		wp_cache_set( 'beyondwords_settings_errors', $errors, 'beyondwords' );
	}
}
