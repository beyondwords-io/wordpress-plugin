<?php
/**
 * BeyondWords settings fields.
 *
 * Consolidates the simple text/select fields used by the settings tabs.
 * Compound fields (e.g. preselect) live in their own class.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings fields.
 *
 * One static method per field, each registering the option, sanitiser and
 * the renderer for `do_settings_fields`. Option keys, defaults and the
 * value enums for the integration method and player UI are exposed as
 * class constants so the rest of the plugin can read them without going
 * through the settings page.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Fields {

	const OPTION_API_KEY            = 'beyondwords_api_key';
	const OPTION_PROJECT_ID         = 'beyondwords_project_id';
	const OPTION_INTEGRATION_METHOD = 'beyondwords_integration_method';
	const OPTION_PREPEND_EXCERPT    = 'beyondwords_prepend_excerpt';
	const OPTION_PLAYER_UI          = 'beyondwords_player_ui';

	const INTEGRATION_REST_API    = 'rest-api';
	const INTEGRATION_CLIENT_SIDE = 'client-side';

	const PLAYER_UI_ENABLED  = 'enabled';
	const PLAYER_UI_HEADLESS = 'headless';
	const PLAYER_UI_DISABLED = 'disabled';

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register_authentication_fields' ) );
		add_action( 'admin_init', array( self::class, 'register_integration_fields' ) );
		add_action( 'admin_init', array( self::class, 'register_preferences_fields' ) );
	}

	/**
	 * Register API key + project ID for the Authentication tab.
	 */
	public static function register_authentication_fields(): void {
		register_setting(
			Tabs::SETTINGS_GROUP_AUTHENTICATION,
			self::OPTION_API_KEY,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_api_key' ),
			)
		);

		register_setting(
			Tabs::SETTINGS_GROUP_AUTHENTICATION,
			self::OPTION_PROJECT_ID,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_project_id' ),
			)
		);

		add_settings_field(
			'beyondwords-api-key',
			__( 'API key', 'speechkit' ),
			array( self::class, 'render_api_key' ),
			Tabs::PAGE_AUTHENTICATION,
			Tabs::SECTION_AUTHENTICATION
		);

		add_settings_field(
			'beyondwords-project-id',
			__( 'Project ID', 'speechkit' ),
			array( self::class, 'render_project_id' ),
			Tabs::PAGE_AUTHENTICATION,
			Tabs::SECTION_AUTHENTICATION
		);
	}

	/**
	 * Register the integration method field for the Integration tab.
	 */
	public static function register_integration_fields(): void {
		register_setting(
			Tabs::SETTINGS_GROUP_INTEGRATION,
			self::OPTION_INTEGRATION_METHOD,
			array(
				'type'              => 'string',
				'default'           => self::INTEGRATION_REST_API,
				'sanitize_callback' => array( self::class, 'sanitize_integration_method' ),
			)
		);

		add_settings_field(
			'beyondwords-integration-method',
			__( 'Integration method', 'speechkit' ),
			array( self::class, 'render_integration_method' ),
			Tabs::PAGE_INTEGRATION,
			Tabs::SECTION_INTEGRATION
		);
	}

	/**
	 * Register the per-site preferences (preselect, excerpt, player UI).
	 *
	 * Preselect lives in its own class because it has post-type-aware logic
	 * and an enqueued JS asset for the post editor.
	 */
	public static function register_preferences_fields(): void {
		register_setting(
			Tabs::SETTINGS_GROUP_PREFERENCES,
			self::OPTION_PREPEND_EXCERPT,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
		add_filter( 'option_' . self::OPTION_PREPEND_EXCERPT, 'rest_sanitize_boolean' );

		register_setting(
			Tabs::SETTINGS_GROUP_PREFERENCES,
			self::OPTION_PLAYER_UI,
			array(
				'type'              => 'string',
				'default'           => self::PLAYER_UI_ENABLED,
				'sanitize_callback' => array( self::class, 'sanitize_player_ui' ),
			)
		);

		add_settings_field(
			'beyondwords-include-excerpt',
			__( 'Excerpt', 'speechkit' ),
			array( self::class, 'render_prepend_excerpt' ),
			Tabs::PAGE_PREFERENCES,
			Tabs::SECTION_PREFERENCES
		);

		add_settings_field(
			'beyondwords-player-ui',
			__( 'Player UI', 'speechkit' ),
			array( self::class, 'render_player_ui' ),
			Tabs::PAGE_PREFERENCES,
			Tabs::SECTION_PREFERENCES
		);
	}

	/**
	 * Sanitisers
	 * ------------------------------------------------------------------ */

	public static function sanitize_api_key( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			Utils::add_settings_error_message(
				__( 'Please enter the BeyondWords API key. This can be found in your project settings.', 'speechkit' ),
				'Settings/ApiKey'
			);
		}

		return $value;
	}

	public static function sanitize_project_id( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			Utils::add_settings_error_message(
				__( 'Please enter your BeyondWords project ID. This can be found in your project settings.', 'speechkit' ),
				'Settings/ProjectId'
			);
		}

		return $value;
	}

	public static function sanitize_integration_method( $value ): string {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( self::INTEGRATION_REST_API, self::INTEGRATION_CLIENT_SIDE ), true )
			? $value
			: self::INTEGRATION_REST_API;
	}

	public static function sanitize_player_ui( $value ): string {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( self::PLAYER_UI_ENABLED, self::PLAYER_UI_HEADLESS, self::PLAYER_UI_DISABLED ), true )
			? $value
			: self::PLAYER_UI_ENABLED;
	}

	/**
	 * Renderers
	 * ------------------------------------------------------------------ */

	public static function render_api_key(): void {
		$value = (string) get_option( self::OPTION_API_KEY, '' );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
			name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			size="50"
		/>
		<?php
	}

	public static function render_project_id(): void {
		$value = (string) get_option( self::OPTION_PROJECT_ID, '' );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( self::OPTION_PROJECT_ID ); ?>"
			name="<?php echo esc_attr( self::OPTION_PROJECT_ID ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			size="10"
		/>
		<?php
	}

	public static function render_integration_method(): void {
		$current = self::get_integration_method();
		?>
		<div class="beyondwords-setting__integration-method">
			<select name="<?php echo esc_attr( self::OPTION_INTEGRATION_METHOD ); ?>" id="<?php echo esc_attr( self::OPTION_INTEGRATION_METHOD ); ?>">
				<?php foreach ( self::get_integration_method_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<p class="description">
			<?php
			printf(
				/* translators: %s is the link text "Magic Embed" */
				esc_html__( 'REST API is the default integration. Choose %s if your theme/plugins prevent BeyondWords from saving content via the REST API — for example on sites using a page builder.', 'speechkit' ),
				sprintf(
					'<a href="https://github.com/beyondwords-io/player/blob/main/doc/client-side-integration.md" target="_blank" rel="nofollow">%s</a>',
					esc_html__( 'Magic Embed', 'speechkit' )
				)
			);
			?>
		</p>
		<?php
	}

	public static function render_prepend_excerpt(): void {
		$value = (bool) get_option( self::OPTION_PREPEND_EXCERPT, false );
		?>
		<div>
			<label>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION_PREPEND_EXCERPT ); ?>" value="" />
				<input
					type="checkbox"
					id="<?php echo esc_attr( self::OPTION_PREPEND_EXCERPT ); ?>"
					name="<?php echo esc_attr( self::OPTION_PREPEND_EXCERPT ); ?>"
					value="1"
					<?php checked( $value ); ?>
				/>
				<?php esc_html_e( 'Include the post excerpt at the start of generated audio and video.', 'speechkit' ); ?>
			</label>
		</div>
		<?php
	}

	public static function render_player_ui(): void {
		$current = (string) get_option( self::OPTION_PLAYER_UI, self::PLAYER_UI_ENABLED );
		?>
		<div class="beyondwords-setting__player-ui">
			<select name="<?php echo esc_attr( self::OPTION_PLAYER_UI ); ?>" id="<?php echo esc_attr( self::OPTION_PLAYER_UI ); ?>">
				<?php foreach ( self::get_player_ui_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<p class="description">
			<?php
			printf(
				/* translators: %s is the link text "headless mode" */
				esc_html__( 'Enable or disable the player, or set it to %s.', 'speechkit' ),
				sprintf(
					'<a href="https://github.com/beyondwords-io/player/blob/gh-pages/doc/building-your-own-ui.md" target="_blank" rel="nofollow">%s</a>',
					esc_html__( 'headless mode', 'speechkit' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Public accessors
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve the integration method for a post (or the site default).
	 *
	 * @param \WP_Post|false $post Post object, or false to get the site default.
	 */
	public static function get_integration_method( $post = false ): string {
		$method = '';

		if ( $post instanceof \WP_Post ) {
			$method = (string) get_post_meta( $post->ID, self::OPTION_INTEGRATION_METHOD, true );
		}

		if ( '' === $method ) {
			$method = (string) get_option( self::OPTION_INTEGRATION_METHOD, self::INTEGRATION_REST_API );
		}

		return self::sanitize_integration_method( $method );
	}

	/**
	 * Available integration method choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_integration_method_options(): array {
		return array(
			self::INTEGRATION_REST_API    => __( 'REST API', 'speechkit' ),
			self::INTEGRATION_CLIENT_SIDE => __( 'Magic Embed', 'speechkit' ),
		);
	}

	/**
	 * Available player UI choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_player_ui_options(): array {
		return array(
			self::PLAYER_UI_ENABLED  => __( 'Enabled', 'speechkit' ),
			self::PLAYER_UI_HEADLESS => __( 'Headless', 'speechkit' ),
			self::PLAYER_UI_DISABLED => __( 'Disabled', 'speechkit' ),
		);
	}
}
