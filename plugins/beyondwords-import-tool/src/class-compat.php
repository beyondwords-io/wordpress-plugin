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
	 * Memoized return of get_allowed_post_types().
	 *
	 * @var string[]|null
	 */
	private static $allowed_post_types = null;

	/**
	 * Post types the main plugin registers BeyondWords post meta for.
	 *
	 * Importing to any other type writes meta that `Sync::register_meta()`
	 * never registered, so it gets no sanitize callback and stays invisible to
	 * the block editor.
	 *
	 * Memoized because every record in a batch consults it, and the underlying
	 * `get_compatible_post_types()` walks `get_post_types()` and fires a filter
	 * on each call.
	 *
	 * @since 1.1.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types() {
		if ( self::$allowed_post_types !== null ) {
			return self::$allowed_post_types;
		}

		if ( is_callable( [ '\BeyondWords\Settings\Utils', 'get_compatible_post_types' ] ) ) {
			$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

			if ( is_array( $post_types ) ) {
				self::$allowed_post_types = $post_types;

				return self::$allowed_post_types;
			}
		}

		self::$allowed_post_types = get_post_types_by_support( 'custom-fields' );

		return self::$allowed_post_types;
	}
}
