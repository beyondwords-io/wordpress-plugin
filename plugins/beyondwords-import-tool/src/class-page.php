<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page registration and rendering for the import tool.
 *
 * @since 1.0.0
 */
class Page {
	/**
	 * Shared menu slug for BeyondWords tools.
	 */
	const MENU_SLUG = 'beyondwords-tools';

	/**
	 * Whether the import confirmation nonce was verified for this request.
	 *
	 * @var bool
	 */
	private static $import_confirmed = false;

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_menu', [ self::class, 'register_management_page' ] );
		add_action( 'admin_init', [ self::class, 'handle_confirm' ] );
		add_action( 'beyondwords_tools_page_content', [ self::class, 'render_tool_section' ], 20 );
	}

	/**
	 * Add the management page under Tools (shared with other BeyondWords tools).
	 *
	 * @since 1.0.0
	 */
	public static function register_management_page() {
		global $submenu;

		// Only add the menu page if it doesn't already exist.
		$exists = false;

		if ( ! empty( $submenu['tools.php'] ) ) {
			foreach ( $submenu['tools.php'] as $item ) {
				if ( $item[2] === self::MENU_SLUG ) {
					$exists = true;
					break;
				}
			}
		}

		if ( ! $exists ) {
			add_management_page(
				__( 'BeyondWords', 'speechkit' ),
				__( 'BeyondWords', 'speechkit' ),
				'manage_options',
				self::MENU_SLUG,
				[ self::class, 'render_page' ]
			);
		}
	}

	/**
	 * Render the shared tools page wrapper.
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BeyondWords', 'speechkit' ); ?></h1>
			<?php do_action( 'beyondwords_tools_page_content' ); ?>
		</div>
		<?php
	}

	/**
	 * Render this tool's section on the shared page.
	 *
	 * @since 1.0.0
	 */
	public static function render_tool_section() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- step is a display toggle, not an action.
		$step        = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1;
		$import_data = Transients::get_import_data();

		?>
		<div class="beyondwords-tool-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Import Tool', 'speechkit' ); ?></h2>
			<?php
			if ( $step === 2 && $import_data ) {
				self::render_preview( $import_data );
			} elseif ( $step === 3 && $import_data && self::$import_confirmed ) {
				self::render_progress( $import_data );
			} else {
				self::render_upload_form();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Handle the import confirmation nonce.
	 *
	 * @since 1.0.0
	 */
	public static function handle_confirm() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			isset( $_POST['beyondwords_import_confirm_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['beyondwords_import_confirm_nonce'] ) ), 'beyondwords_import_confirm' )
		) {
			self::$import_confirmed = true;
		}
	}

	/**
	 * Render the file upload form.
	 *
	 * @since 1.0.0
	 */
	private static function render_upload_form() {
		?>
		<p><?php esc_html_e( 'Import BeyondWords data from a JSON file.', 'speechkit' ); ?></p>
		<ul class="description" style="list-style: disc inside;">
			<li><?php echo wp_kses_post( __( 'This tool allows you to bulk import BeyondWords audio assignments from a JSON file supplied by BeyondWords.', 'speechkit' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'It will update the relevant WordPress posts with the necessary meta fields to link them to your BeyondWords projects and content.', 'speechkit' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'The JSON file will contain an array of objects with the following fields: <code>source_id</code>, <code>source_url</code>, <code>project_id</code>, <code>content_id</code>.', 'speechkit' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'Audio that was created with WordPress plugin versions later than <code>v4.0</code> (released July 2023) should have a numeric WordPress Post ID for the <code>source_id</code>, so assigning audio to those posts should go smoothly.', 'speechkit' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'For audio that was created with plugin versions earlier than <code>v4.0</code> we will attempt to locate the post using the <code>source_url</code> field.', 'speechkit' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'If we are unable to locate the post using either <code>source_id</code> or <code>source_url</code>, audio assignment will be skipped.', 'speechkit' ) ); ?></li>
		</ul>

		<p class="description" style="font-weight: bold;">
			<?php echo wp_kses_post( __( 'You will have the opportunity to review the changes before they are applied. Please do so carefully.', 'speechkit' ) ); ?>
		</p>

		<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>" method="post" enctype="multipart/form-data" style="margin-top: 15px;">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="import_file"><?php esc_html_e( 'JSON File', 'speechkit' ); ?></label>
					</th>
					<td>
						<input type="file" name="import_file" id="import_file" accept=".json,application/json" required />
						<p class="description"><?php esc_html_e( 'Select a JSON file to import.', 'speechkit' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="beyondwords_import_submit" class="button button-primary" value="<?php esc_attr_e( 'Upload and Validate', 'speechkit' ); ?>" />
			</p>
			<?php wp_nonce_field( 'beyondwords_import_upload', 'beyondwords_import_nonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Render the preview page with CodeMirror.
	 *
	 * @since 1.0.0
	 *
	 * @param array $import_data The parsed and validated import data.
	 */
	private static function render_preview( $import_data ) {
		Assets::enqueue_code_mirror();

		$preview         = Helpers::generate_preview_code( $import_data );
		$total_records   = count( $import_data );
		$skipped_records = $preview['skipped'];
		$skipped_count   = count( $skipped_records );
		$importable      = $total_records - $skipped_count;

		?>
		<p><?php printf( esc_html__( 'Processing %d records...', 'speechkit' ), intval( $total_records ) ); ?></p>
		<p><?php printf( esc_html__( 'Found %d records to import (%d post meta operations).', 'speechkit' ), intval( $importable ), intval( $importable ) * 3 ); ?></p>

		<?php if ( $skipped_count > 0 ) : ?>
			<div class="notice notice-warning inline" style="margin: 10px 0;">
				<p>
					<?php
					printf(
						esc_html__( '%d record(s) will be skipped because a matching WordPress post could not be found:', 'speechkit' ),
						intval( $skipped_count )
					);
					?>
				</p>
				<table class="widefat striped" style="margin: 10px 0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source ID', 'speechkit' ); ?></th>
							<th><?php esc_html_e( 'Source URL', 'speechkit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $skipped_records as $record ) : ?>
							<tr>
								<td><?php echo esc_html( $record['source_id'] ); ?></td>
								<td><?php echo esc_html( $record['source_url'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e( 'The following update_post_meta() calls will be executed:', 'speechkit' ); ?></p>

		<textarea id="beyondwords-import-preview" readonly style="width: 100%; min-height: 300px;"><?php echo esc_textarea( $preview['code'] ); ?></textarea>

		<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG . '&step=3' ) ); ?>" method="post" style="margin-top: 20px;">
			<p class="submit">
				<input type="submit" name="beyondwords_import_confirm" class="button button-primary" value="<?php esc_attr_e( 'Confirm and Execute', 'speechkit' ); ?>" />
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'speechkit' ); ?></a>
			</p>
			<?php wp_nonce_field( 'beyondwords_import_confirm', 'beyondwords_import_confirm_nonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Render the progress page with batch processing.
	 *
	 * @since 1.0.0
	 *
	 * @param array $import_data The parsed and validated import data.
	 */
	private static function render_progress( $import_data ) {
		$total_records = count( $import_data );

		?>
		<div id="beyondwords-import-progress-container">
			<p><?php esc_html_e( 'Importing data...', 'speechkit' ); ?></p>
			<div id="beyondwords-import-progress-wrapper" style="width: 100%; background: #ccc; border-radius: 4px; margin: 20px 0;">
				<div id="beyondwords-import-progress-bar" style="width: 0%; height: 30px; background: #0073aa; border-radius: 4px; transition: width 0.3s;"></div>
			</div>
			<p id="beyondwords-import-status"><?php printf( esc_html__( 'Processing 0 of %d records...', 'speechkit' ), intval( $total_records ) ); ?></p>
		</div>
		<div id="beyondwords-import-complete" style="display: none;">
			<div class="notice notice-success inline">
				<p><?php esc_html_e( 'Import completed!', 'speechkit' ); ?></p>
			</div>
			<p id="beyondwords-import-summary"></p>
			<div id="beyondwords-import-failed-report" style="display: none; margin-top: 15px;">
				<div class="notice notice-warning inline">
					<p id="beyondwords-import-failed-summary"></p>
				</div>
				<p class="description"><?php esc_html_e( 'Please copy the list of failed records below and send it to BeyondWords support so we can help resolve the issue.', 'speechkit' ); ?></p>
				<div style="margin: 10px 0;">
					<button type="button" class="button button-primary beyondwords-copy-failed" aria-label="<?php esc_attr_e( 'Copy failed records to clipboard', 'speechkit' ); ?>">
						<?php esc_html_e( 'Copy failed records to clipboard', 'speechkit' ); ?>
					</button>
					<span class="beyondwords-copy-success" aria-hidden="true" style="display: none; margin-left: 8px; color: #00a32a; line-height: 28px;"><?php esc_html_e( 'Copied!', 'speechkit' ); ?></span>
				</div>
				<table class="widefat striped" style="margin-top: 10px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source ID', 'speechkit' ); ?></th>
							<th><?php esc_html_e( 'Source URL', 'speechkit' ); ?></th>
							<th><?php esc_html_e( 'Project ID', 'speechkit' ); ?></th>
							<th><?php esc_html_e( 'Content ID', 'speechkit' ); ?></th>
						</tr>
					</thead>
					<tbody id="beyondwords-import-failed-rows"></tbody>
				</table>
				<div style="margin-top: 10px;">
					<button type="button" class="button button-primary beyondwords-copy-failed" aria-label="<?php esc_attr_e( 'Copy failed records to clipboard', 'speechkit' ); ?>">
						<?php esc_html_e( 'Copy failed records to clipboard', 'speechkit' ); ?>
					</button>
					<span class="beyondwords-copy-success" aria-hidden="true" style="display: none; margin-left: 8px; color: #00a32a; line-height: 28px;"><?php esc_html_e( 'Copied!', 'speechkit' ); ?></span>
				</div>
			</div>
			<p><a href="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>" class="button"><?php esc_html_e( 'Import Another File', 'speechkit' ); ?></a></p>
		</div>
		<div id="beyondwords-import-error" style="display: none;">
			<div class="notice notice-error inline">
				<p id="beyondwords-import-error-message"></p>
			</div>
			<p><a href="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>" class="button"><?php esc_html_e( 'Try Again', 'speechkit' ); ?></a></p>
		</div>

		<?php
		Assets::enqueue_batch_script( $total_records );
	}

}

Page::init();
