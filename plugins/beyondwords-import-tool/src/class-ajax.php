<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler for batch-processing import records.
 *
 * @since 1.0.0
 */
class Ajax {
	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'wp_ajax_beyondwords_import_batch', [ self::class, 'process_batch' ] );
	}

	/**
	 * Process a batch of import records.
	 *
	 * @since 1.0.0
	 */
	public static function process_batch() {
		check_ajax_referer( 'beyondwords_import_batch', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'speechkit' ) ] );
		}

		$import_data = Transients::get_import_data();

		if ( ! $import_data ) {
			wp_send_json_error( [ 'message' => __( 'Import data not found. Please start over.', 'speechkit' ) ] );
		}

		$offset     = isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0;
		$batch_size = isset( $_POST['batch_size'] ) ? min( 100, max( 1, intval( $_POST['batch_size'] ) ) ) : 50;

		$batch  = array_slice( $import_data, $offset, $batch_size );
		$failed = $offset === 0 ? [] : Transients::get_failed();

		foreach ( $batch as $record ) {
			if ( ! self::import_record( $record ) ) {
				$failed[] = $record;
			}
		}

		Transients::set_failed( $failed );

		$processed = $offset + count( $batch );
		$complete  = $processed >= count( $import_data );

		$response = [
			'processed' => $processed,
			'complete'  => $complete,
		];

		if ( $complete ) {
			Transients::delete_import_data();
			Transients::delete_failed();

			$response['failed']        = $failed;
			$response['failed_count']  = count( $failed );
			$response['success_count'] = count( $import_data ) - count( $failed );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Import a single record by resolving the post and writing meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record The import record.
	 *
	 * @return bool True on success, false on failure.
	 */
	private static function import_record( array $record ): bool {
		$post_id = Helpers::get_post_id_for_record( $record );

		if ( $post_id === false || ! get_post( $post_id ) ) {
			return false;
		}

		PostMeta::update_for_record( $post_id, $record );

		return PostMeta::verify_for_record( $post_id, $record );
	}
}

Ajax::init();
