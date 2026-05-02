<?php
/**
 * Preselect setting.
 *
 * Lets the publisher pick which post types have "Generate audio" ticked
 * by default in the post editor.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Preselect setting.
 *
 * Stored as `array<string,string>` keyed by post type, value `'1'`.
 * In v6.x and earlier, the value could also be an array of taxonomy term
 * IDs — that branch is collapsed to `'1'` by the v7.0.0 migration in
 * `Updater::run()`.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Preselect {

	const OPTION_NAME = 'beyondwords_preselect';

	const DEFAULT_VALUE = [
		'post' => '1',
	];

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register' ] );
	}

	/**
	 * Register the option and settings field.
	 */
	public static function register(): void {
		register_setting(
			Tabs::SETTINGS_GROUP_PREFERENCES,
			self::OPTION_NAME,
			[
				'type'              => 'object',
				'default'           => self::DEFAULT_VALUE,
				'sanitize_callback' => [ self::class, 'sanitize' ],
			]
		);

		add_settings_field(
			'beyondwords-preselect',
			__( 'Preselect ‘Generate audio’', 'speechkit' ),
			[ self::class, 'render' ],
			Tabs::PAGE_PREFERENCES,
			Tabs::SECTION_PREFERENCES
		);
	}

	/**
	 * Sanitise the submitted preselect map.
	 *
	 * Accepts only known compatible post types and coerces every kept value
	 * to the string `'1'`.
	 *
	 * @param mixed $value Raw submitted value.
	 *
	 * @return array<string,string>
	 */
	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$compatible = Utils::get_compatible_post_types();
		$clean      = [];

		foreach ( $value as $post_type => $flag ) {
			$post_type = (string) $post_type;
			if ( in_array( $post_type, $compatible, true ) && ! empty( $flag ) ) {
				$clean[ $post_type ] = '1';
			}
		}

		return $clean;
	}

	/**
	 * Render the checkboxes.
	 */
	public static function render(): void {
		$post_types = Utils::get_compatible_post_types();

		if ( empty( $post_types ) ) {
			?>
			<p class="description">
				<?php esc_html_e( 'No compatible post types found. BeyondWords requires post types that support a title, an editor, and custom fields.', 'speechkit' ); ?>
			</p>
			<?php
			return;
		}

		$preselect = self::get();

		foreach ( $post_types as $post_type ) {
			$object = get_post_type_object( $post_type );
			if ( ! $object ) {
				continue;
			}
			?>
			<div class="beyondwords-setting__preselect--post-type">
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( self::OPTION_NAME . '[' . $object->name . ']' ); ?>"
						value="1"
						<?php checked( self::is_post_type_selected( $object->name, $preselect ) ); ?>
					/>
					<?php echo esc_html( $object->label ); ?>
				</label>
			</div>
			<?php
		}
	}

	/**
	 * Read the current preselect map.
	 *
	 * Defensive against legacy data — anything that is not the simple
	 * `post_type => '1'` form is treated as not selected. Migration to the
	 * new format runs in `Updater::run()`.
	 *
	 * @return array<string,string>
	 */
	public static function get(): array {
		$preselect = get_option( self::OPTION_NAME, self::DEFAULT_VALUE );
		return is_array( $preselect ) ? $preselect : [];
	}

	/**
	 * Whether a post type is currently preselected.
	 *
	 * @param string                $post_type Post type slug.
	 * @param array<string,mixed>|null $preselect Pre-loaded option, to avoid an extra `get_option()`.
	 */
	public static function is_post_type_selected( string $post_type, ?array $preselect = null ): bool {
		if ( null === $preselect ) {
			$preselect = self::get();
		}

		if ( ! array_key_exists( $post_type, $preselect ) ) {
			return false;
		}

		$value = $preselect[ $post_type ];

		// New format: '1'.
		if ( '1' === $value ) {
			return true;
		}

		// Legacy: a non-empty array of taxonomy terms — treated as selected
		// pending the v7.0.0 migration that flattens these to '1'.
		if ( is_array( $value ) && ! empty( $value ) ) {
			return true;
		}

		return false;
	}
}
