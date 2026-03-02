<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transient storage for import data and failed records.
 *
 * All keys are scoped to the current user.
 *
 * @since 1.0.0
 */
class Transients {
	const IMPORT_DATA_PREFIX = 'beyondwords_import_data_';
	const FAILED_PREFIX      = 'beyondwords_import_failed_';

	/**
	 * Get the transient key for import data, scoped to the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function import_data_key() {
		return self::IMPORT_DATA_PREFIX . get_current_user_id();
	}

	/**
	 * Get the transient key for failed records, scoped to the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function failed_key() {
		return self::FAILED_PREFIX . get_current_user_id();
	}

	/**
	 * Get the stored import data.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false
	 */
	public static function get_import_data() {
		return get_transient( self::import_data_key() );
	}

	/**
	 * Store import data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The parsed and validated import records.
	 */
	public static function set_import_data( $data ) {
		set_transient( self::import_data_key(), $data, HOUR_IN_SECONDS );
	}

	/**
	 * Delete stored import data.
	 *
	 * @since 1.0.0
	 */
	public static function delete_import_data() {
		delete_transient( self::import_data_key() );
	}

	/**
	 * Get the stored failed records.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_failed() {
		$failed = get_transient( self::failed_key() );

		if ( ! is_array( $failed ) ) {
			return [];
		}

		return $failed;
	}

	/**
	 * Store failed records.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The failed import records.
	 */
	public static function set_failed( $data ) {
		set_transient( self::failed_key(), $data, HOUR_IN_SECONDS );
	}

	/**
	 * Delete stored failed records.
	 *
	 * @since 1.0.0
	 */
	public static function delete_failed() {
		delete_transient( self::failed_key() );
	}
}
