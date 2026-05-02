<?php
/**
 * BeyondWords Site Health.
 *
 * Adds a "BeyondWords" section to the WordPress Site Health debugging info.
 *
 * @package BeyondWords\SiteHealth
 * @since   3.7.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\SiteHealth;

defined( 'ABSPATH' ) || exit;

/**
 * Site Health debug panel for BeyondWords.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class SiteHealth {

	/**
	 * BeyondWords filter hooks that are still active.
	 *
	 * Used to surface "Registered filters" in Site Health so support can see
	 * which extension points the site is using.
	 *
	 * @var string[]
	 */
	const FILTERS = [
		'beyondwords_content_params',
		'beyondwords_player_script_onload',
		'beyondwords_player_html',
		'beyondwords_player_sdk_params',
		'beyondwords_settings_player_styles',
		'beyondwords_settings_post_types',
		'beyondwords_settings_post_statuses',
	];

	/**
	 * BeyondWords filter hooks that are deprecated but may still be in use.
	 *
	 * Used to flag legacy hooks in Site Health so we can chase them down.
	 *
	 * @var string[]
	 */
	const DEPRECATED_FILTERS = [
		'beyondwords_amp_player_html',
		'beyondwords_body_params',
		'beyondwords_content',
		'beyondwords_content_id',
		'beyondwords_js_player_html',
		'beyondwords_js_player_params',
		'beyondwords_player_styles',
		'beyondwords_post_audio_enabled_blocks',
		'beyondwords_post_metadata',
		'beyondwords_post_player_enabled',
		'beyondwords_post_statuses',
		'beyondwords_post_types',
		'beyondwords_project_id',
		'sk_player_after',
		'sk_player_before',
		'sk_the_content',
		'speechkit_amp_player_html',
		'speechkit_content',
		'speechkit_js_player_html',
		'speechkit_js_player_params',
		'speechkit_post_player_enabled',
		'speechkit_post_statuses',
		'speechkit_post_types',
	];

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_filter( 'debug_information', [ self::class, 'debug_information' ] );
	}

	/**
	 * Build the BeyondWords section of `debug_information`.
	 *
	 * @param array $info Site Health debug array.
	 *
	 * @return array
	 */
	public static function debug_information( $info ) {
		$info['beyondwords']['label'] = __( 'BeyondWords - Text-to-Speech', 'speechkit' );

		self::add_plugin_version( $info );
		self::add_rest_api_connection( $info );

		$info['beyondwords']['fields']['compatible-post-types'] = [
			'label' => __( 'Compatible post types', 'speechkit' ),
			'value' => implode( ', ', \BeyondWords\Settings\Utils::get_compatible_post_types() ),
		];

		$info['beyondwords']['fields']['integration-method'] = [
			'label' => __( 'Integration method', 'speechkit' ),
			'value' => \BeyondWords\Settings\Fields::get_integration_method(),
		];

		$info['beyondwords']['fields']['beyondwords_api_key'] = [
			'label' => __( 'API Key', 'speechkit' ),
			'value' => self::mask_string( get_option( 'beyondwords_api_key' ) ),
		];

		$info['beyondwords']['fields']['beyondwords_project_id'] = [
			'label' => __( 'Project ID', 'speechkit' ),
			'value' => get_option( 'beyondwords_project_id' ),
		];

		self::add_preferences( $info );
		self::add_filters( $info );
		self::add_notice_settings( $info );

		self::add_constant( $info, 'BEYONDWORDS_AUTOREGENERATE' );

		return $info;
	}

	/**
	 * Add the per-site preferences (Preferences tab) to the debug array.
	 *
	 * @param array $info Debug array, modified in place.
	 */
	public static function add_preferences( array &$info ): void {
		$info['beyondwords']['fields']['beyondwords_prepend_excerpt'] = [
			'label' => __( 'Include excerpt', 'speechkit' ),
			'value' => get_option( 'beyondwords_prepend_excerpt' ) ? __( 'Yes', 'speechkit' ) : __( 'No', 'speechkit' ),
			'debug' => get_option( 'beyondwords_prepend_excerpt' ) ? 'yes' : 'no',
		];

		$info['beyondwords']['fields']['beyondwords_player_ui'] = [
			'label' => __( 'Player UI', 'speechkit' ),
			'value' => get_option( 'beyondwords_player_ui' ),
		];

		$info['beyondwords']['fields']['beyondwords_preselect'] = [
			'label' => __( 'Preselect ‘Generate audio’', 'speechkit' ),
			'value' => (string) wp_json_encode( get_option( 'beyondwords_preselect' ), JSON_PRETTY_PRINT ),
		];
	}

	/**
	 * Add plugin version (with file/db version mismatch detection) to the debug array.
	 *
	 * @param array $info Debug array, modified in place.
	 */
	public static function add_plugin_version( array &$info ): void {
		$const_version = defined( 'BEYONDWORDS__PLUGIN_VERSION' ) ? BEYONDWORDS__PLUGIN_VERSION : '';
		$db_version    = get_option( 'beyondwords_version' );

		if ( $const_version && $const_version === $db_version ) {
			$info['beyondwords']['fields']['plugin-version'] = [
				'label' => __( 'Plugin version', 'speechkit' ),
				'value' => BEYONDWORDS__PLUGIN_VERSION,
			];
			return;
		}

		$info['beyondwords']['fields']['plugin-version'] = [
			'label' => __( 'Plugin version', 'speechkit' ),
			'value' => sprintf(
				/* translators: 1: Current plugin version, 2: Database plugin version */
				__( 'Version mismatch: file: %1$s / db: %2$s', 'speechkit' ),
				$const_version,
				$db_version
			),
		];
	}

	/**
	 * Add REST API connectivity info to the debug array.
	 *
	 * @param array $info Debug array, modified in place.
	 */
	public static function add_rest_api_connection( array &$info ): void {
		$api_url = \BeyondWords\Core\Environment::get_api_url();

		$info['beyondwords']['fields']['api-url'] = [
			'label' => __( 'REST API URL', 'speechkit' ),
			'value' => $api_url,
		];

		$response = wp_remote_request(
			$api_url,
			[
				'blocking' => true,
				'body'     => '',
				'method'   => 'GET',
			]
		);

		if ( ! is_wp_error( $response ) ) {
			$info['beyondwords']['fields']['api-communication'] = [
				'label' => __( 'Communication with REST API', 'speechkit' ),
				'value' => __( 'BeyondWords API is reachable', 'speechkit' ),
				'debug' => 'true',
			];
			return;
		}

		$info['beyondwords']['fields']['api-communication'] = [
			'label' => __( 'Communication with REST API', 'speechkit' ),
			'value' => sprintf(
				/* translators: 1: The IP address the REST API resolves to. 2: The error returned by the lookup. */
				__( 'Unable to reach BeyondWords API at %1$s: %2$s', 'speechkit' ),
				gethostbyname( $api_url ),
				$response->get_error_message()
			),
			'debug' => $response->get_error_message(),
		];
	}

	/**
	 * Add registered + deprecated filter hook info to the debug array.
	 *
	 * @param array $info Debug array, modified in place.
	 */
	public static function add_filters( array &$info ): void {
		$registered = array_values( array_filter( self::FILTERS, 'has_filter' ) );

		$info['beyondwords']['fields']['registered-filters'] = [
			'label' => __( 'Registered filters', 'speechkit' ),
			'value' => empty( $registered ) ? __( 'None', 'speechkit' ) : implode( ', ', $registered ),
			'debug' => empty( $registered ) ? 'none' : implode( ', ', $registered ),
		];

		$registered = array_values( array_filter( self::DEPRECATED_FILTERS, 'has_filter' ) );

		$info['beyondwords']['fields']['registered-deprecated-filters'] = [
			'label' => __( 'Registered deprecated filters', 'speechkit' ),
			'value' => empty( $registered ) ? __( 'None', 'speechkit' ) : implode( ', ', $registered ),
			'debug' => empty( $registered ) ? 'none' : implode( ', ', $registered ),
		];
	}

	/**
	 * Add notice/activation timestamps to the debug array.
	 *
	 * @param array $info Debug array, modified in place.
	 */
	public static function add_notice_settings( array &$info ): void {
		$info['beyondwords']['fields']['beyondwords_date_activated'] = [
			'label' => __( 'Date Activated', 'speechkit' ),
			'value' => get_option( 'beyondwords_date_activated', '' ),
		];

		$info['beyondwords']['fields']['beyondwords_notice_review_dismissed'] = [
			'label' => __( 'Review Notice Dismissed', 'speechkit' ),
			'value' => get_option( 'beyondwords_notice_review_dismissed', '' ),
		];
	}

	/**
	 * Add a single constant's value to the debug array.
	 *
	 * @param array  $info Debug array, modified in place.
	 * @param string $name Constant name.
	 */
	public static function add_constant( array &$info, string $name ): void {
		$value = __( 'Undefined', 'speechkit' );

		if ( defined( $name ) ) {
			$value = constant( $name );

			if ( is_bool( $value ) ) {
				$value = true === $value ? 'True' : 'False';
			}
		}

		$info['beyondwords']['fields'][ $name ] = [
			'label' => $name,
			'value' => $value,
			'debug' => $value,
		];
	}

	/**
	 * Mask a sensitive string for display in Site Health.
	 *
	 * Replaces all but the last `$count` characters with `$char` so the API key
	 * is recognisable but unreadable.
	 *
	 * @param string|false $value Value to mask. `false` (e.g. missing option) renders empty.
	 * @param int          $count Number of trailing characters to preserve.
	 * @param string       $char  Character used to mask.
	 */
	public static function mask_string( string|false $value, int $count = 4, string $char = 'X' ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		if ( strlen( $value ) < 8 ) {
			return str_repeat( $char, strlen( $value ) );
		}

		return str_repeat( $char, strlen( $value ) - $count ) . substr( $value, -$count );
	}
}
