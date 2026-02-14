<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Debug;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log file operations for the debug tool.
 *
 * @since 1.0.0
 */
class LogFile {
	/**
	 * Log file path relative to wp-content/uploads.
	 */
	const LOG_FILE_RELATIVE = 'beyondwords/rest-api.log';

	/**
	 * Maximum log file size in bytes (50 MB).
	 */
	const MAX_LOG_SIZE = 50 * 1024 * 1024;

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_init', [ self::class, 'handle_log_download' ] );
	}

	/**
	 * Get the full path to the log file.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_log_file_path() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/' . self::LOG_FILE_RELATIVE;
	}

	/**
	 * Check if the log file is writable, attempting to create it if necessary.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array with 'writable' (bool) and 'message' (string) keys.
	 */
	public static function check_log_file_writable() {
		$log_file = self::get_log_file_path();
		$log_dir  = dirname( $log_file );

		// Try to create the directory if it doesn't exist.
		if ( ! is_dir( $log_dir ) ) {
			if ( ! wp_mkdir_p( $log_dir ) ) {
				return [
					'writable' => false,
					'message'  => sprintf(
						// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %s is a directory path.
						__( 'Could not create directory: %s. Please create it manually with write permissions.', 'speechkit' ),
						$log_dir
					),
				];
			}

			self::protect_log_directory( $log_dir );
		}

		// Try to create the log file if it doesn't exist.
		if ( ! file_exists( $log_file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Writing to wp-content/uploads.
			$created = @file_put_contents( $log_file, '' );
			if ( $created === false ) {
				return [
					'writable' => false,
					'message'  => sprintf(
						// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %s is a file path.
						__( 'Could not create log file: %s. Please create it manually with write permissions.', 'speechkit' ),
						$log_file
					),
				];
			}
		}

		// Check if the file is writable.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable -- Checking wp-content/uploads writability.
		if ( ! is_writable( $log_file ) ) {
			return [
				'writable' => false,
					'message'  => sprintf(
					// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %s is a file path.
					__( 'Log file is not writable: %s. Please ensure PHP has write permissions.', 'speechkit' ),
					$log_file
				),
			];
		}

		return [
			'writable' => true,
			'message'  => '',
		];
	}

	/**
	 * Write a message to the log file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The message to write.
	 */
	public static function write_to_log( $message ) {
		$log_file   = self::get_log_file_path();
		$file_check = self::check_log_file_writable();

		if ( ! $file_check['writable'] ) {
			return;
		}

		// Rotate if the log file exceeds the size limit.
		if ( file_exists( $log_file ) && filesize( $log_file ) > self::MAX_LOG_SIZE ) {
			self::rotate_log( $log_file );
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Appending to log file in wp-content/uploads.
		file_put_contents( $log_file, $message . "\n", FILE_APPEND | LOCK_EX );
	}

	/**
	 * Handle log file download request.
	 *
	 * @since 1.0.0
	 */
	public static function handle_log_download() {
		if ( ! isset( $_GET['beyondwords_download_log'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die() escapes output.
			wp_die( __( 'You do not have permission to download this file.', 'speechkit' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce value is only compared, not stored.
		if ( ! wp_verify_nonce( $_GET['beyondwords_download_log'], 'beyondwords_download_log' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die() escapes output.
			wp_die( __( 'Security check failed.', 'speechkit' ) );
		}

		$log_file = self::get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die() escapes output.
			wp_die( __( 'Log file does not exist.', 'speechkit' ) );
		}

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="beyondwords-rest-api.log"' );
		header( 'Content-Length: ' . filesize( $log_file ) );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$handle = fopen( $log_file, 'rb' );
		if ( false === $handle ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die() escapes output.
			wp_die( __( 'Failed to open log file for reading.', 'speechkit' ) );
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, 8192 ); // 8 KB chunks to limit memory usage.
			if ( false === $buffer ) {
				break;
			}
			echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw log content for download.

			// Stop sending data if the client connection has been aborted.
			if ( connection_aborted() ) {
				break;
			}
		}

		fclose( $handle );
		exit;
	}
	/**
	 * Protect the log directory from public access.
	 *
	 * Creates .htaccess (Apache) and index.php files to prevent directory
	 * listing and direct file access.
	 *
	 * @since 1.1.0
	 *
	 * @param string $dir The directory path to protect.
	 */
	private static function protect_log_directory( $dir ) {
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Writing security file to wp-content/uploads.
			@file_put_contents( $htaccess, "Deny from all\n" );
		}

		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Writing security file to wp-content/uploads.
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Rotate the log file by renaming it with a timestamp suffix.
	 *
	 * Keeps only the current and one previous log file.
	 *
	 * @since 1.1.0
	 *
	 * @param string $log_file The log file path.
	 */
	private static function rotate_log( $log_file ) {
		$rotated = $log_file . '.' . gmdate( 'Y-m-d-H-i-s' ) . '.old';

		// Remove any existing rotated file to keep only one backup.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Ignore if glob fails.
		$existing = @glob( $log_file . '.*.old' );
		if ( $existing ) {
			foreach ( $existing as $old_file ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Cleaning up rotated log files.
				@unlink( $old_file );
			}
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename -- Rotating log file.
		@rename( $log_file, $rotated );
	}

	/**
	 * Delete the log file and any rotated copies.
	 *
	 * Called during plugin deactivation to clean up sensitive data.
	 *
	 * @since 1.1.0
	 */
	public static function delete_log_file() {
		$log_file = self::get_log_file_path();

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Cleaning up log file on deactivation.
			@unlink( $log_file );
		}

		// Remove rotated copies.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Ignore if glob fails.
		$rotated = @glob( $log_file . '.*.old' );
		if ( $rotated ) {
			foreach ( $rotated as $old_file ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Cleaning up rotated log files on deactivation.
				@unlink( $old_file );
			}
		}
	}
}

LogFile::init();
