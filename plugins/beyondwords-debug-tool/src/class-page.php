<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Debug;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page registration and rendering for the debug tool.
 *
 * @since 1.0.0
 */
class Page {
	/**
	 * Shared menu slug for BeyondWords tools.
	 */
	const MENU_SLUG = 'beyondwords-tools';

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_menu', [ self::class, 'register_management_page' ] );
		add_action( 'beyondwords_tools_page_content', [ self::class, 'render_tool_section' ], 30 );
	}

	/**
	 * Add the management page under Tools (shared with other BeyondWords tools).
	 *
	 * @since 1.0.0
	 */
	public static function register_management_page() {
		global $submenu;

		// Only add the menu page if it doesn't already exist.
		// Note: this shared page registration is duplicated in
		// beyondwords-import-tool/src/class-page.php — keep both in sync.
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
		$is_enabled = Settings::is_debug_enabled();
		$log_file   = LogFile::get_log_file_path();
		$file_check = LogFile::check_log_file_writable();

		?>
		<div class="beyondwords-tool-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Debug Tool', 'speechkit' ); ?></h2>
			<p><?php esc_html_e( 'Log BeyondWords REST API requests and responses for debugging purposes.', 'speechkit' ); ?></p>

			<div class="notice notice-warning inline" style="margin: 15px 0;">
				<p>
					<strong><?php esc_html_e( 'Important:', 'speechkit' ); ?></strong>
					<?php esc_html_e( 'The log file is stored in a publicly accessible location. We mask API keys, but the log may contain other sensitive information. You are responsible for deleting the log file when debugging is complete.', 'speechkit' ); ?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'beyondwords_debug_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug REST API Requests', 'speechkit' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>" value="1" <?php checked( $is_enabled ); ?> />
								<?php esc_html_e( 'Enable logging of BeyondWords API requests and responses', 'speechkit' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, all requests to and responses from the BeyondWords API will be logged.', 'speechkit' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php if ( $is_enabled ) : ?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'API URL Being Monitored', 'speechkit' ); ?></th>
							<td>
								<code><?php echo esc_html( Logger::get_api_url() ); ?></code>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Log File Location', 'speechkit' ); ?></th>
							<td>
								<code><?php echo esc_html( $log_file ); ?></code>
								<?php if ( $file_check['writable'] ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;"></span>
									<span style="color: green;"><?php esc_html_e( 'Writable', 'speechkit' ); ?></span>
								<?php else : ?>
									<div class="notice notice-error inline" style="margin: 10px 0;">
										<p><?php echo esc_html( $file_check['message'] ); ?></p>
									</div>
								<?php endif; ?>
								<?php if ( file_exists( $log_file ) ) : ?>
									<p style="margin-top: 10px;">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ), 'beyondwords_download_log', 'beyondwords_download_log' ) ); ?>" class="button button-secondary">
											<?php esc_html_e( 'Download', 'speechkit' ); ?>
										</a>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save Debug Settings', 'speechkit' ) ); ?>
			</form>
		</div>
		<?php
	}
}

Page::init();
