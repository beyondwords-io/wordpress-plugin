<?php
/**
 * Environment-driven URLs and overrides.
 *
 * Each accessor returns the override defined in `wp-config.php` if present,
 * falling back to the production constant baked into the class.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Environment URL provider.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Environment {

	const BEYONDWORDS_API_URL        = 'https://api.beyondwords.io/v1';
	const BEYONDWORDS_BACKEND_URL    = '';
	const BEYONDWORDS_JS_SDK_URL     = 'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';
	const BEYONDWORDS_AMP_PLAYER_URL = 'https://audio.beyondwords.io/amp/%d?podcast_id=%s';
	const BEYONDWORDS_AMP_IMG_URL    = 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/assets/logo.svg';
	const BEYONDWORDS_DASHBOARD_URL  = 'https://dash.beyondwords.io';

	/**
	 * BeyondWords REST API base URL.
	 */
	public static function get_api_url(): string {
		if ( defined( 'BEYONDWORDS_API_URL' ) && strlen( BEYONDWORDS_API_URL ) ) {
			return BEYONDWORDS_API_URL;
		}

		return static::BEYONDWORDS_API_URL;
	}

	/**
	 * BeyondWords backend URL (legacy; usually empty).
	 */
	public static function get_backend_url(): string {
		if ( defined( 'BEYONDWORDS_BACKEND_URL' ) && strlen( BEYONDWORDS_BACKEND_URL ) ) {
			return BEYONDWORDS_BACKEND_URL;
		}

		return static::BEYONDWORDS_BACKEND_URL;
	}

	/**
	 * Front-end JS SDK script URL.
	 */
	public static function get_js_sdk_url(): string {
		if ( defined( 'BEYONDWORDS_JS_SDK_URL' ) && strlen( BEYONDWORDS_JS_SDK_URL ) ) {
			return BEYONDWORDS_JS_SDK_URL;
		}

		return static::BEYONDWORDS_JS_SDK_URL;
	}

	/**
	 * AMP iframe player URL pattern (`%d` for project ID, `%s` for content ID).
	 */
	public static function get_amp_player_url(): string {
		if ( defined( 'BEYONDWORDS_AMP_PLAYER_URL' ) && strlen( BEYONDWORDS_AMP_PLAYER_URL ) ) {
			return BEYONDWORDS_AMP_PLAYER_URL;
		}

		return static::BEYONDWORDS_AMP_PLAYER_URL;
	}

	/**
	 * Placeholder image URL for AMP players.
	 */
	public static function get_amp_img_url(): string {
		if ( defined( 'BEYONDWORDS_AMP_IMG_URL' ) && strlen( BEYONDWORDS_AMP_IMG_URL ) ) {
			return BEYONDWORDS_AMP_IMG_URL;
		}

		return static::BEYONDWORDS_AMP_IMG_URL;
	}

	/**
	 * BeyondWords dashboard URL.
	 */
	public static function get_dashboard_url(): string {
		if ( defined( 'BEYONDWORDS_DASHBOARD_URL' ) && strlen( BEYONDWORDS_DASHBOARD_URL ) ) {
			return BEYONDWORDS_DASHBOARD_URL;
		}

		return static::BEYONDWORDS_DASHBOARD_URL;
	}
}
