<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Export;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page registration and rendering for the export tool.
 *
 * @since 1.0.0
 */
class Page {
	/**
	 * Menu slug for the export tool.
	 */
	const MENU_SLUG = 'speechkit-export-tool';

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_menu', [ self::class, 'register_management_page' ] );
	}

	/**
	 * Add the management page under Tools.
	 *
	 * @since 1.0.0
	 */
	public static function register_management_page() {
		$hook = add_management_page(
			__( 'Export SpeechKit Data', 'speechkit' ),
			__( 'Export SpeechKit Data', 'speechkit' ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'render_page' ]
		);

		add_action( "load-$hook", [ Exporter::class, 'handle_export' ] );
	}

	/**
	 * Render the export tool page.
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {
		?>
		<div class="wrap nosubsub">
			<h1><?php esc_html_e( 'Export SpeechKit Data', 'speechkit' ); ?></h1>
			<p><?php esc_html_e( 'This tool helps site owners export a sample of their SpeechKit data.', 'speechkit' ); ?></p>
			<p><?php esc_html_e( 'The exported file should be sent to the SpeechKit team to enable them to replicate your SpeechKit data.', 'speechkit' ); ?></p>
			<p><?php esc_html_e( 'It is safe for you to send this file using email, because no sensitive information is present in the file.', 'speechkit' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>" method="post" class="speechkit-export-data-tool">
				<div class="wp-privacy-request-form-field">
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Download Export File', 'speechkit' ); ?>" />
					</p>
				</div>
				<?php wp_nonce_field( 'export', 'export_speechkit_data_nonce' ); ?>
			</form>
		</div>
		<?php
	}
}

Page::init();
