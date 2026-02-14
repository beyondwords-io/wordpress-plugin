<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the file upload and JSON validation for the import tool.
 *
 * @since 1.0.0
 */
class FileHandler {
	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_init', [ self::class, 'handle_upload' ] );
	}

	/**
	 * Handle the upload form submission.
	 *
	 * Checks nonce and capability before processing.
	 *
	 * @since 1.0.0
	 */
	public static function handle_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['beyondwords_import_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['beyondwords_import_nonce'] ) ), 'beyondwords_import_upload' ) ) {
			return;
		}

		self::process();
	}

	/**
	 * Validate the uploaded file, parse JSON, sanitise records, and store.
	 *
	 * @since 1.0.0
	 */
	private static function process() {
		// Raise memory limit for large file processing (up to 10 MB JSON / 10,000 records).
		wp_raise_memory_limit( 'admin' );

		$file = self::validate_upload();
		if ( ! $file ) {
			return;
		}

		$data = self::parse_json( $file );
		if ( ! $data ) {
			return;
		}

		$import_data = self::validate_records( $data );
		if ( ! $import_data ) {
			return;
		}

		Transients::set_import_data( $import_data );

		self::redirect_to_preview();
	}

	/**
	 * Validate the uploaded file exists, is within size limits, and is JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false The uploaded file array on success, false on failure.
	 */
	private static function validate_upload() {
		// Nonce verified in handle_upload() before this method is called.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_FILES['import_file']['error'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			Notices::add( __( 'Error uploading file. Please try again.', 'speechkit' ), 'error' );
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file = $_FILES['import_file'];

		$max_size = 10 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			Notices::add( __( 'File is too large. Maximum size is 10 MB.', 'speechkit' ), 'error' );
			return false;
		}

		$file_type = wp_check_filetype( $file['name'], [ 'json' => 'application/json' ] );
		if ( $file_type['ext'] !== 'json' ) {
			Notices::add( __( 'Invalid file type. Please upload a JSON file.', 'speechkit' ), 'error' );
			return false;
		}

		// Validate the actual MIME type of the uploaded file, not just the extension.
		$mime_type = '';
		if ( ! empty( $file['tmp_name'] ) && function_exists( 'mime_content_type' ) ) {
			$mime_type = mime_content_type( $file['tmp_name'] );
		} elseif ( ! empty( $file['tmp_name'] ) && function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( $finfo ) {
				$mime_type = finfo_file( $finfo, $file['tmp_name'] );
				finfo_close( $finfo );
			}
		}

		if ( $mime_type ) {
			// Normalize MIME type (strip parameters like charset and compare case-insensitively).
			$normalized_mime_type = strtolower( trim( explode( ';', $mime_type )[0] ) );
			$allowed_mime_types   = [
				'application/json',
				'application/ld+json',
				'text/json',
			];

			if ( ! in_array( $normalized_mime_type, $allowed_mime_types, true ) ) {
				Notices::add( __( 'Invalid file content. Please upload a valid JSON file.', 'speechkit' ), 'error' );
				return false;
			}
		}
		return $file;
	}

	/**
	 * Read and parse the JSON file, validating structure and size.
	 *
	 * @since 1.0.0
	 *
	 * @param array $file The uploaded file array from $_FILES.
	 *
	 * @return array|false The parsed data array on success, false on failure.
	 */
	private static function parse_json( array $file ) {
		$json_content = file_get_contents( $file['tmp_name'] );

		if ( $json_content === false ) {
			Notices::add( __( 'Could not read the uploaded file. Please try again.', 'speechkit' ), 'error' );
			return false;
		}

		$data = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			Notices::add(
				// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %s is a JSON error message.
				sprintf( __( 'Invalid JSON: %s', 'speechkit' ), json_last_error_msg() ),
				'error'
			);
			return false;
		}

		if ( ! is_array( $data ) || empty( $data ) ) {
			Notices::add( __( 'JSON file must contain a non-empty array.', 'speechkit' ), 'error' );
			return false;
		}

		$max_records = 10000;
		if ( count( $data ) > $max_records ) {
			Notices::add(
				// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %s is a formatted number.
				sprintf( __( 'Too many records. Maximum is %s per import.', 'speechkit' ), number_format_i18n( $max_records ) ),
				'error'
			);
			return false;
		}

		return $data;
	}

	/**
	 * Validate required fields in each record and return sanitised data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The parsed JSON data.
	 *
	 * @return array|false The sanitised import data on success, false on failure.
	 */
	private static function validate_records( array $data ) {
		$required_fields = [ 'source_id', 'project_id', 'content_id', 'source_url' ];
		$import_data     = [];
		$errors          = [];

		foreach ( $data as $index => $record ) {
			$row_num    = $index + 1;
			$row_errors = self::check_required_fields( $record, $required_fields, $row_num );

			if ( ! empty( $row_errors ) ) {
				$errors = array_merge( $errors, $row_errors );
				continue;
			}

			$import_data[] = [
				'source_id'  => sanitize_text_field( $record['source_id'] ),
				'project_id' => intval( $record['project_id'] ),
				'content_id' => sanitize_text_field( $record['content_id'] ),
				'source_url' => esc_url_raw( $record['source_url'] ),
			];
		}

		if ( ! empty( $errors ) ) {
			Notices::add(
				__( 'Validation errors:', 'speechkit' ) . '<br>' . implode( '<br>', array_map( 'esc_html', $errors ) ),
				'error'
			);
			return false;
		}

		if ( empty( $import_data ) ) {
			Notices::add( __( 'No valid records found to import.', 'speechkit' ), 'error' );
			return false;
		}

		return $import_data;
	}

	/**
	 * Check that a record contains all required fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record         A single import record.
	 * @param array $required_fields List of required field names.
	 * @param int   $row_num        The 1-based row number for error messages.
	 *
	 * @return array List of error messages, empty if all fields are present.
	 */
	private static function check_required_fields( array $record, array $required_fields, int $row_num ): array {
		$errors = [];

		foreach ( $required_fields as $field ) {
			if ( ! isset( $record[ $field ] ) ) {
				$errors[] = sprintf(
					/* translators: 1: row number, 2: field name */
					__( 'Row %1$d: Missing required field "%2$s".', 'speechkit' ),
					$row_num,
					$field
				);
			}
		}

		return $errors;
	}

	/**
	 * Redirect to the import preview page.
	 *
	 * @since 1.0.0
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	private static function redirect_to_preview() {
		wp_redirect( admin_url( 'tools.php?page=' . Page::MENU_SLUG . '&step=2' ) );
		exit;
	}
}

FileHandler::init();
