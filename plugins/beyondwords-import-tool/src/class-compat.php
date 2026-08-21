<?php

declare( strict_types = 1 );

namespace Beyondwords\Wordpress\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Every read of the main BeyondWords plugin's API, in one place.
 *
 * This plugin ships as its own ZIP and can be activated without the main
 * plugin, so each lookup degrades to a standalone equivalent.
 *
 * @since 1.1.0
 */
class Compat {
	/**
	 * Fallback integration method when the main plugin is inactive.
	 *
	 * Mirrors `BeyondWords\Settings\Fields::INTEGRATION_REST_API`.
	 */
	const INTEGRATION_REST_API = 'rest-api';

	/**
	 * Post types the main plugin registers BeyondWords post meta for.
	 *
	 * Importing to any other type writes meta that `Sync::register_meta()`
	 * never registered, so it gets no sanitize callback and stays invisible to
	 * the block editor.
	 *
	 * @since 1.1.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types() {
		if ( is_callable( [ '\BeyondWords\Settings\Utils', 'get_compatible_post_types' ] ) ) {
			$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

			if ( is_array( $post_types ) ) {
				return $post_types;
			}
		}

		return get_post_types_by_support( 'custom-fields' );
	}

	/**
	 * Integration method to record against an imported post.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public static function get_integration_method() {
		if ( defined( '\BeyondWords\Settings\Fields::INTEGRATION_REST_API' ) ) {
			return \BeyondWords\Settings\Fields::INTEGRATION_REST_API;
		}

		return self::INTEGRATION_REST_API;
	}
}
