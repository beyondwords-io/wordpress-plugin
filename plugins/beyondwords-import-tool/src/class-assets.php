<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues admin JavaScript for the import tool.
 *
 * @since 1.0.0
 */
class Assets {
	/**
	 * Enqueue CodeMirror assets for the preview page.
	 *
	 * @since 1.0.0
	 */
	public static function enqueue_code_mirror() {
		$settings = wp_enqueue_code_editor( [ 'type' => 'text/x-php' ] );

		if ( $settings === false ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			'jQuery(function($) {
				if ($("#beyondwords-import-preview").length) {
					wp.codeEditor.initialize($("#beyondwords-import-preview"), ' . wp_json_encode( $settings ) . ');
				}
			});'
		);
	}

	/**
	 * Enqueue the batch-processing and copy-to-clipboard script.
	 *
	 * @since 1.0.0
	 *
	 * @param int $total_records The total number of records to process.
	 */
	public static function enqueue_batch_script( $total_records ) {
		wp_enqueue_script(
			'beyondwords-import-batch',
			plugins_url( 'assets/js/import-batch.js', __DIR__ ),
			[ 'jquery', 'wp-i18n' ],
			'1.0.0',
			true
		);

		wp_localize_script(
			'beyondwords-import-batch',
			'beyondwordsImportBatch',
			[
				'totalRecords' => intval( $total_records ),
				'batchSize'    => 50,
				'nonce'        => wp_create_nonce( 'beyondwords_import_batch' ),
				'i18n'         => [
					// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %1$d and %2$d are numeric counts.
					'processing'     => __( 'Processing %1$d of %2$d records...', 'speechkit' ),
					// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %1$d and %2$d are numeric counts.
					'successSummary' => __( 'Successfully updated %1$d records (%2$d meta values).', 'speechkit' ),
					// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment -- %d is a numeric count.
					'failedSummary'  => __( '%d record(s) could not be imported because a matching WordPress post could not be found:', 'speechkit' ),
					'ajaxError'      => __( 'An error occurred during import.', 'speechkit' ),
					'networkError'   => __( 'A network error occurred. Please try again.', 'speechkit' ),
					'copiedMessage'  => __( 'Failed records copied to clipboard.', 'speechkit' ),
				],
			]
		);
	}
}
