<?php
/**
 * BeyondWords settings page.
 *
 * Owns the admin menu entry, the tabbed settings form, the REST endpoint
 * the editor scripts read, and the admin notices that surround them.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Settings {

	const PAGE_SLUG            = 'beyondwords';
	const REVIEW_NOTICE_OFFSET = '-14 days';

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_options_page' ), 1 );
		add_action( 'admin_notices', array( self::class, 'maybe_print_missing_creds_warning' ), 100 );
		add_action( 'admin_notices', array( self::class, 'print_settings_errors' ), 200 );
		add_action( 'admin_notices', array( self::class, 'maybe_print_review_notice' ) );
		add_action( 'load-settings_page_' . self::PAGE_SLUG, array( self::class, 'maybe_validate_api_creds' ) );

		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );

		add_filter( 'plugin_action_links_speechkit/speechkit.php', array( self::class, 'add_plugin_action_link' ) );
	}

	/**
	 * Register the BeyondWords settings page under Settings.
	 */
	public static function add_options_page(): void {
		add_options_page(
			__( 'BeyondWords Settings', 'speechkit' ),
			__( 'BeyondWords', 'speechkit' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_admin_page' )
		);
	}

	/**
	 * Validate API credentials whenever the Authentication tab loads.
	 *
	 * Triggered on the page-specific load hook so we don't pay the API cost
	 * on unrelated admin screens.
	 */
	public static function maybe_validate_api_creds(): void {
		if ( Tabs::TAB_AUTHENTICATION === Tabs::get_active_tab() ) {
			Utils::validate_api_connection();
		}
	}

	/**
	 * Render the settings page (the tabbed form).
	 */
	public static function render_admin_page(): void {
		$tabs       = Tabs::get_visible_tabs();
		$active_tab = Tabs::get_active_tab();
		$active     = Tabs::get_active_page_and_group();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BeyondWords Settings', 'speechkit' ); ?></h1>

			<form
				id="beyondwords-plugin-settings"
				action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>"
				method="post"
			>
				<nav class="nav-tab-wrapper">
					<ul>
						<?php foreach ( $tabs as $slug => $label ) : ?>
							<li>
								<a
									class="nav-tab <?php echo $slug === $active_tab ? 'nav-tab-active' : ''; ?>"
									href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $slug ) ) ); ?>"
								>
									<?php echo esc_html( $label ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<hr class="wp-header-end">

				<?php
				settings_fields( $active['group'] );
				do_settings_sections( $active['page'] );
				submit_button( __( 'Save changes', 'speechkit' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add a "Settings" link to the plugin row.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public static function add_plugin_action_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'speechkit' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Show a banner directing publishers to the settings page until creds
	 * are entered.
	 */
	public static function maybe_print_missing_creds_warning(): void {
		if ( Utils::has_api_creds() ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<strong>
					<?php
					printf(
						/* translators: %s is the "plugin settings" link */
						esc_html__( 'To use BeyondWords, please update the %s.', 'speechkit' ),
						sprintf(
							'<a href="%s">%s</a>',
							esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ),
							esc_html__( 'plugin settings', 'speechkit' )
						)
					);
					?>
				</strong>
			</p>
			<p><?php esc_html_e( 'Don’t have a BeyondWords account yet?', 'speechkit' ); ?></p>
			<p>
				<a
					class="button button-secondary"
					href="<?php echo esc_url( sprintf( '%s/auth/signup', \BeyondWords\Core\Environment::get_dashboard_url() ) ); ?>"
					target="_blank"
				>
					<?php esc_html_e( 'Sign up free', 'speechkit' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Show the once-only "leave us a review" notice on the settings page,
	 * 14+ days after activation.
	 */
	public static function maybe_print_review_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_' . self::PAGE_SLUG !== $screen->id ) {
			return;
		}

		$dismissed = get_option( 'beyondwords_notice_review_dismissed', '' );
		if ( ! empty( $dismissed ) ) {
			return;
		}

		$activated_at = strtotime( (string) get_option( 'beyondwords_date_activated', '2025-03-01' ) );
		if ( false === $activated_at || $activated_at >= strtotime( self::REVIEW_NOTICE_OFFSET ) ) {
			return;
		}
		?>
		<div id="beyondwords_notice_review" class="notice notice-info is-dismissible">
			<p>
				<strong>
					<?php
					printf(
						/* translators: %s is the link to the WordPress plugin repo */
						esc_html__( 'Happy with our work? Help us spread the word with a rating on the %s.', 'speechkit' ),
						sprintf(
							'<a href="%s">%s</a>',
							'https://wordpress.org/support/plugin/speechkit/reviews/',
							esc_html__( 'WordPress Plugin Repo', 'speechkit' )
						)
					);
					?>
				</strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Drain queued settings errors into a notice.
	 *
	 * Errors are queued via `Utils::add_settings_error_message()`, then
	 * popped here and rendered as a single `notice notice-error`.
	 */
	public static function print_settings_errors(): void {
		$errors = wp_cache_get( 'beyondwords_settings_errors', 'beyondwords' );
		wp_cache_delete( 'beyondwords_settings_errors', 'beyondwords' );

		if ( ! is_array( $errors ) || empty( $errors ) ) {
			return;
		}

		$allowed = array(
			'a'      => array( 'href' => array(), 'target' => array() ),
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
			'br'     => array(),
			'code'   => array(),
		);
		?>
		<div class="notice notice-error">
			<ul class="ul-disc">
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo wp_kses( $error, $allowed ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Register REST routes consumed by the editor scripts.
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'beyondwords/v1',
			'/settings',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_settings_response' ),
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
			)
		);

		register_rest_route(
			'beyondwords/v1',
			'/settings/notices/review/dismiss',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'rest_dismiss_review_notice' ),
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	/**
	 * Settings payload for editor scripts.
	 *
	 * Never include the API key in this response — editor scripts run in the
	 * browser and the key must stay server-side.
	 */
	public static function rest_settings_response(): \WP_REST_Response {
		global $wp_version;

		return new \WP_REST_Response(
			array(
				'apiKey'            => (string) get_option( Fields::OPTION_API_KEY, '' ),
				'integrationMethod' => Fields::get_integration_method(),
				'pluginVersion'     => BEYONDWORDS__PLUGIN_VERSION,
				'projectId'         => (string) get_option( Fields::OPTION_PROJECT_ID, '' ),
				'preselect'         => Preselect::get(),
				'restUrl'           => get_rest_url(),
				'wpVersion'         => $wp_version,
			)
		);
	}

	/**
	 * Mark the review notice as dismissed.
	 */
	public static function rest_dismiss_review_notice(): \WP_REST_Response {
		$ok = update_option( 'beyondwords_notice_review_dismissed', gmdate( \DateTime::ATOM ) );

		return new \WP_REST_Response(
			array( 'success' => $ok ),
			$ok ? 200 : 500
		);
	}

}
