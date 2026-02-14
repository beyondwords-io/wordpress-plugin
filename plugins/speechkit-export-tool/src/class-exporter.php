<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Export;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV export logic for the export tool.
 *
 * @since 1.0.0
 */
class Exporter {
	/**
	 * CSV column headers matching the SpeechKit meta keys.
	 */
	const CSV_HEADERS = [
		'post_id',
		'post_type',
		'post_status',
		'publish_post_to_speechkit',
		'speechkit_project_id',
		'speechkit_podcast_id',
		'speechkit_disabled',
		'speechkit_retries',
		'speechkit_error',
		'speechkit_info',
		'speechkit_response',
		'speechkit_status',
		'_speechkit_disable_generate_audio',
		'_speechkit_link',
		'_speechkit_text',
	];

	/**
	 * SpeechKit meta keys to query and export.
	 */
	const META_KEYS = [
		'publish_post_to_speechkit',
		'speechkit_project_id',
		'speechkit_podcast_id',
		'speechkit_disabled',
		'speechkit_retries',
		'speechkit_error',
		'speechkit_info',
		'speechkit_response',
		'speechkit_status',
		'_speechkit_disable_generate_audio',
		'_speechkit_link',
		'_speechkit_text',
	];

	/**
	 * Handle the export form submission and generate the CSV download.
	 *
	 * @since 1.0.0
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if (
			! isset( $_POST['export_speechkit_data_nonce'] ) ||
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce value is only compared, not stored.
			! wp_verify_nonce( $_POST['export_speechkit_data_nonce'], 'export' )
		) {
			return;
		}

		$data = [
			0 => self::CSV_HEADERS,
		];

		// Query 50 of the OLDEST posts with SpeechKit data.
		$oldest = self::query_posts_with_meta( 'ASC' );

		if ( $oldest ) {
			foreach ( $oldest as $obj ) {
				if ( ! array_key_exists( $obj->ID, $data ) ) {
					$data[ $obj->ID ] = self::get_csv_row( $obj->ID );
				}
			}
		}

		// Query 50 of the NEWEST posts with SpeechKit data.
		$newest = self::query_posts_with_meta( 'DESC' );

		if ( $newest ) {
			foreach ( $newest as $obj ) {
				if ( ! array_key_exists( $obj->ID, $data ) ) {
					$data[ $obj->ID ] = self::get_csv_row( $obj->ID );
				}
			}
		}

		// Write to memory (unless buffer exceeds 2mb when it will write to /tmp).
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Using php://temp stream, not filesystem.
		$fp = fopen( 'php://temp', 'w+' );

		foreach ( $data as $fields ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://temp stream, not filesystem.
			fputcsv( $fp, $fields, ',', '"', "\0" );
		}

		rewind( $fp );
		$csv_contents = stream_get_contents( $fp );
		fclose( $fp );

		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: must-revalidate' );
		header( 'Content-type: application/vnd.ms-excel' );
		header( 'Content-disposition: attachment; filename="beyondwords-export.csv"' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw CSV output for file download.
		echo $csv_contents;
		exit;
	}

	/**
	 * Generate the data for a single CSV row.
	 *
	 * @since 1.0.2
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	private static function get_csv_row( $post_id ) {
		$row = [
			$post_id,
			get_post_type( $post_id ),
			get_post_status( $post_id ),
		];

		foreach ( self::META_KEYS as $meta_key ) {
			$row[] = self::sanitize_csv_value( get_post_meta( $post_id, $meta_key, true ) );
		}

		return $row;
	}

	/**
	 * Query posts that have SpeechKit meta data.
	 *
	 * @since 1.0.3
	 *
	 * @param string $order 'ASC' or 'DESC' sort order by post_date.
	 *
	 * @return array|object|null Database query results.
	 */
	private static function query_posts_with_meta( $order ) {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( self::META_KEYS ), '%s' ) );

		// Build query via concatenation to satisfy PHPCS (no interpolated variables inside prepare()).
		$sql = 'SELECT ID FROM ' . $wpdb->posts
			. ' INNER JOIN ' . $wpdb->postmeta
			. ' ON (' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id)'
			. ' WHERE (' . $wpdb->postmeta . '.meta_key IN (' . $placeholders . ')'
			. ' AND LENGTH(' . $wpdb->postmeta . '.meta_value) > 0)'
			. ' GROUP BY ' . $wpdb->posts . '.ID'
			. ' ORDER BY ' . $wpdb->posts . '.post_date ' . ( 'ASC' === $order ? 'ASC' : 'DESC' )
			. ' LIMIT 50';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off export query, not cached.
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is built from $wpdb table properties and %s placeholders only.
			$wpdb->prepare( $sql, ...self::META_KEYS )
		);
	}

	/**
	 * Sanitize a value for safe CSV output.
	 *
	 * Prevents formula injection by prefixing dangerous leading characters
	 * with a single quote, which neutralises them in spreadsheet applications.
	 *
	 * @since 1.0.3
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return mixed The sanitized value.
	 */
	private static function sanitize_csv_value( $value ) {
		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		if ( in_array( $value[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
