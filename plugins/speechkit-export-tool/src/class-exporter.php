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
		if (
			! isset( $_POST['export_speechkit_data_nonce'] ) ||
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce value is only compared, not stored.
			! wp_verify_nonce( $_POST['export_speechkit_data_nonce'], 'export' )
		) {
			return;
		}

		global $table_prefix;

		mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

		$mysqli = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

		$meta_keys_sql = self::get_meta_keys_sql( $mysqli );
		$prefix        = $mysqli->real_escape_string( $table_prefix );

		$data = [
			0 => self::CSV_HEADERS,
		];

		// Query 50 of the OLDEST posts with SpeechKit data.
		$result = $mysqli->query(
			"SELECT ID, post_date FROM {$prefix}posts
			INNER JOIN {$prefix}postmeta
			ON ({$prefix}posts.ID = {$prefix}postmeta.post_id)
			WHERE (
				{$prefix}postmeta.meta_key IN ({$meta_keys_sql})
				AND LENGTH({$prefix}postmeta.meta_value) > 0
			)
			GROUP BY {$prefix}posts.ID
			ORDER BY {$prefix}posts.post_date ASC
			LIMIT 0,50;"
		);

		while ( $obj = $result->fetch_object() ) {
			if ( ! array_key_exists( $obj->ID, $data ) ) {
				$data[ $obj->ID ] = self::get_csv_row( $obj->ID );
			}
		}

		// Query 50 of the NEWEST posts with SpeechKit data.
		$result = $mysqli->query(
			"SELECT ID, post_date FROM {$prefix}posts
			INNER JOIN {$prefix}postmeta
			ON ({$prefix}posts.ID = {$prefix}postmeta.post_id)
			WHERE (
				{$prefix}postmeta.meta_key IN ({$meta_keys_sql})
				AND LENGTH({$prefix}postmeta.meta_value) > 0
			)
			GROUP BY {$prefix}posts.ID
			ORDER BY {$prefix}posts.post_date DESC
			LIMIT 0,50;"
		);

		while ( $obj = $result->fetch_object() ) {
			if ( ! array_key_exists( $obj->ID, $data ) ) {
				$data[ $obj->ID ] = self::get_csv_row( $obj->ID );
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

		$filename = 'speechkit-' . gmdate( 'd-m-Y-H-i', time() );

		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: must-revalidate' );
		header( 'Content-type: application/vnd.ms-excel' );
		header( 'Content-disposition: attachment; filename=' . $filename . '.csv' );

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
			$row[] = get_post_meta( $post_id, $meta_key, true );
		}

		return $row;
	}

	/**
	 * Build an SQL-safe IN clause from the meta keys.
	 *
	 * @since 1.0.0
	 *
	 * @param \mysqli $mysqli The database connection.
	 *
	 * @return string
	 */
	private static function get_meta_keys_sql( $mysqli ) {
		$escaped = array_map(
			fn($key) => "'" . $mysqli->real_escape_string( $key ) . "'",
			self::META_KEYS
		);

		return implode( ',', $escaped );
	}
}
