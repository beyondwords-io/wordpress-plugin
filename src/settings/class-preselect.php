<?php
/**
 * Preselect setting.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 * @since 7.0.0 Reinstated term-gating, stored in a mode-based format.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Preselect setting, stored as a per-post-type map with a `mode` key.
 *
 * `all` preselects every post; `terms` only posts with a listed term ID;
 * absent means off. Pre-7.0.0 shapes are migrated by `Updater::run()`.
 *
 * @since 7.0.0
 */
class Preselect {

	const OPTION_NAME = 'beyondwords_preselect';

	const MODE_OFF   = 'off';
	const MODE_ALL   = 'all';
	const MODE_TERMS = 'terms';

	const DEFAULT_VALUE = [
		'post' => [ 'mode' => self::MODE_ALL ],
	];

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the preselect progressive-disclosure script on the settings page.
	 *
	 * @since 7.0.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ): void {
		if ( 'settings_page_' . Settings::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'beyondwords-settings--preselect',
			BEYONDWORDS__PLUGIN_URI . 'src/settings/preselect.js',
			[],
			BEYONDWORDS__PLUGIN_VERSION,
			true
		);
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
	 * Read the current preselect map.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$preselect = get_option( self::OPTION_NAME, self::DEFAULT_VALUE );
		return is_array( $preselect ) ? $preselect : [];
	}

	/**
	 * Resolve the preselect mode for a post type.
	 *
	 * Tolerant of pre-7.0.0 shapes so behaviour is correct before the migration
	 * runs: `'1'` reads as `all`, a non-empty taxonomy array as `terms`.
	 *
	 * @param string                   $post_type Post type slug.
	 * @param array<string,mixed>|null $preselect Pre-loaded option, to avoid an extra `get_option()`.
	 *
	 * @return string One of `off`, `all`, `terms`.
	 */
	public static function get_mode( string $post_type, ?array $preselect = null ): string {
		if ( null === $preselect ) {
			$preselect = self::get();
		}

		if ( ! array_key_exists( $post_type, $preselect ) ) {
			return self::MODE_OFF;
		}

		$value = $preselect[ $post_type ];

		// Legacy whole-post-type flag.
		if ( '1' === $value || 1 === $value || true === $value ) {
			return self::MODE_ALL;
		}

		if ( is_array( $value ) ) {
			if ( isset( $value['mode'] ) ) {
				return in_array( $value['mode'], [ self::MODE_ALL, self::MODE_TERMS ], true )
					? $value['mode']
					: self::MODE_OFF;
			}

			// Legacy term-gated shape: [ taxonomy => [ term ids ] ].
			return empty( $value ) ? self::MODE_OFF : self::MODE_TERMS;
		}

		return self::MODE_OFF;
	}

	/**
	 * The selected term IDs for a post type, keyed by taxonomy.
	 *
	 * Reads both the new (`terms` key) and legacy (bare taxonomy array) shapes.
	 * Returns `[]` for any mode other than `terms`.
	 *
	 * @param string                   $post_type Post type slug.
	 * @param array<string,mixed>|null $preselect Pre-loaded option.
	 *
	 * @return array<string,int[]> Map of taxonomy slug to term IDs.
	 */
	public static function get_selected_terms( string $post_type, ?array $preselect = null ): array {
		if ( null === $preselect ) {
			$preselect = self::get();
		}

		if ( ! isset( $preselect[ $post_type ] ) || ! is_array( $preselect[ $post_type ] ) ) {
			return [];
		}

		$value = $preselect[ $post_type ];

		if ( isset( $value['mode'] ) ) {
			if ( self::MODE_TERMS !== $value['mode'] || ! isset( $value['terms'] ) || ! is_array( $value['terms'] ) ) {
				return [];
			}
			$raw = $value['terms'];
		} else {
			// Legacy [ taxonomy => [ term ids ] ].
			$raw = $value;
		}

		$terms = [];

		foreach ( $raw as $taxonomy => $ids ) {
			if ( ! is_array( $ids ) ) {
				continue;
			}

			$clean = array_values( array_filter( array_map( 'intval', $ids ) ) );

			if ( ! empty( $clean ) ) {
				$terms[ (string) $taxonomy ] = $clean;
			}
		}

		return $terms;
	}

	/**
	 * Whether "Generate audio" should be preselected for a given post.
	 *
	 * In `terms` mode a post matches when it has at least one listed term (OR
	 * across taxonomies); unregistered/detached taxonomies are skipped, never fatal.
	 *
	 * @param \WP_Post|int $post Post object or ID.
	 */
	public static function should_preselect_for_post( $post ): bool {
		$post_type = get_post_type( $post );

		if ( ! $post_type ) {
			return false;
		}

		// get()'s DEFAULT_VALUE fallback applies in every context (REST, cron),
		// keeping the server's decision in step with the editor's derived toggle.
		$preselect = self::get();

		$mode = self::get_mode( $post_type, $preselect );

		if ( self::MODE_ALL === $mode ) {
			return true;
		}

		if ( self::MODE_TERMS !== $mode ) {
			return false;
		}

		$selected = self::get_selected_terms( $post_type, $preselect );

		if ( empty( $selected ) ) {
			return false;
		}

		$post_type_object_taxonomies = get_object_taxonomies( $post_type );

		foreach ( $selected as $taxonomy => $term_ids ) {
			if ( ! taxonomy_exists( $taxonomy ) || ! in_array( $taxonomy, $post_type_object_taxonomies, true ) ) {
				continue;
			}

			$post_terms = get_the_terms( $post, $taxonomy );

			if ( empty( $post_terms ) || is_wp_error( $post_terms ) ) {
				continue;
			}

			$post_term_ids = array_map( 'intval', wp_list_pluck( $post_terms, 'term_id' ) );

			if ( array_intersect( $term_ids, $post_term_ids ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitise the submitted preselect map.
	 *
	 * Merge-preserve: only post types/taxonomies rendered this request are read
	 * from the submission, so a save never wipes a deactivated plugin's config.
	 *
	 * @param mixed $value Raw submitted value.
	 *
	 * @return array<string,mixed>
	 */
	public static function sanitize( $value ): array {
		// Merge from the RAW stored option — get()'s DEFAULT_VALUE fallback
		// would leak `post => all` into a fresh save.
		$raw      = get_option( self::OPTION_NAME );
		$existing = is_array( $raw ) ? $raw : [];

		if ( ! is_array( $value ) ) {
			return $existing;
		}

		$clean = $existing;

		foreach ( Utils::get_compatible_post_types() as $post_type ) {
			$submitted = ( isset( $value[ $post_type ] ) && is_array( $value[ $post_type ] ) ) ? $value[ $post_type ] : [];

			if ( empty( $submitted['enabled'] ) ) {
				unset( $clean[ $post_type ] );
				continue;
			}

			$has_taxonomies = ! empty( self::get_hierarchical_taxonomy_names( $post_type ) );

			// "All" wins over any ticked terms; with no hierarchical taxonomies
			// the whole-post-type option is the only choice.
			if ( ! $has_taxonomies || ! empty( $submitted['all'] ) ) {
				$clean[ $post_type ] = [ 'mode' => self::MODE_ALL ];
				continue;
			}

			$clean[ $post_type ] = [
				'mode'  => self::MODE_TERMS,
				'terms' => self::sanitize_terms( $post_type, $submitted, $existing ),
			];
		}

		return $clean;
	}

	/**
	 * Sanitise one post type's term map, merge-preserving unrendered taxonomies.
	 *
	 * @param string              $post_type Post type slug.
	 * @param array<string,mixed> $submitted Submitted value for this post type.
	 * @param array<string,mixed> $existing  Full stored option (for preservation).
	 *
	 * @return array<string,int[]>
	 */
	private static function sanitize_terms( string $post_type, array $submitted, array $existing ): array {
		$rendered_taxonomies = self::get_hierarchical_taxonomy_names( $post_type );
		$submitted_terms     = ( isset( $submitted['terms'] ) && is_array( $submitted['terms'] ) ) ? $submitted['terms'] : [];

		$terms = [];

		// Preserve stored terms for taxonomies that weren't rendered this request.
		foreach ( self::get_selected_terms( $post_type, $existing ) as $taxonomy => $ids ) {
			if ( ! in_array( $taxonomy, $rendered_taxonomies, true ) ) {
				$terms[ $taxonomy ] = $ids;
			}
		}

		// Take rendered taxonomies' terms from the submission (authoritative).
		foreach ( $rendered_taxonomies as $taxonomy ) {
			if ( ! isset( $submitted_terms[ $taxonomy ] ) || ! is_array( $submitted_terms[ $taxonomy ] ) ) {
				continue;
			}

			$ids = array_values( array_filter( array_map( 'intval', $submitted_terms[ $taxonomy ] ) ) );

			if ( ! empty( $ids ) ) {
				$terms[ $taxonomy ] = $ids;
			}
		}

		return $terms;
	}

	/**
	 * Render the per-post-type controls.
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
			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object ) {
				continue;
			}

			self::render_post_type( $post_type_object, $preselect );
		}
	}

	/**
	 * Render the control for one post type.
	 *
	 * Three nested levels (enable → "All" → term trees), progressively revealed
	 * by `preselect.js`; "All" wins over any ticked terms on save.
	 *
	 * @param \WP_Post_Type       $post_type_object Post type object.
	 * @param array<string,mixed> $preselect        Stored option.
	 */
	private static function render_post_type( $post_type_object, array $preselect ): void {
		$post_type      = $post_type_object->name;
		$mode           = self::get_mode( $post_type, $preselect );
		$selected_terms = self::get_selected_terms( $post_type, $preselect );
		$base           = self::OPTION_NAME . '[' . $post_type . ']';
		$taxonomies     = self::get_hierarchical_taxonomies( $post_type );

		$enabled    = ( self::MODE_OFF !== $mode );
		$is_all     = ( self::MODE_TERMS !== $mode ); // 'all' (or the default for a fresh enable).
		$show_terms = ( $enabled && ! $is_all );
		?>
		<div class="beyondwords-setting__preselect--post-type" data-post-type="<?php echo esc_attr( $post_type ); ?>" style="margin-bottom: 0.5rem;">
			<label>
				<input
					type="checkbox"
					class="beyondwords-setting__preselect--enabled"
					name="<?php echo esc_attr( $base . '[enabled]' ); ?>"
					value="1"
					<?php checked( $enabled ); ?>
				/>
				<?php echo esc_html( $post_type_object->label ); ?>
			</label>

			<?php if ( ! empty( $taxonomies ) ) : ?>
				<div
					class="beyondwords-setting__preselect--options"
					style="margin: 0.25rem 0 0 1.5rem;<?php echo $enabled ? '' : ' display: none;'; ?>"
				>
					<label>
						<input
							type="checkbox"
							class="beyondwords-setting__preselect--all"
							name="<?php echo esc_attr( $base . '[all]' ); ?>"
							value="1"
							<?php checked( $is_all ); ?>
						/>
						<?php esc_html_e( 'All', 'speechkit' ); ?>
					</label>
					<div
						class="beyondwords-setting__preselect--taxonomies"
						style="<?php echo $show_terms ? '' : 'display: none;'; ?>"
					>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<h4 style="margin: 0.5rem 0 0.5rem 1.5rem;"><?php echo esc_html( $taxonomy->label ); ?></h4>
							<?php
							$ids  = $selected_terms[ $taxonomy->name ] ?? [];
							$name = $base . '[terms][' . $taxonomy->name . '][]';
							self::render_term_tree( $taxonomy, $name, $ids );
							?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a taxonomy's terms as a nested checkbox tree.
	 *
	 * Fetches every term once and assembles the hierarchy in PHP, rather than
	 * querying per parent.
	 *
	 * @param \WP_Taxonomy $taxonomy     Taxonomy object.
	 * @param string       $name         Checkbox `name` attribute.
	 * @param int[]        $selected_ids Selected term IDs.
	 */
	private static function render_term_tree( $taxonomy, string $name, array $selected_ids ): void {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
			]
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		$by_parent = [];
		foreach ( $terms as $term ) {
			$by_parent[ (int) $term->parent ][] = $term;
		}

		self::render_term_branch( $by_parent, 0, $name, $selected_ids );
	}

	/**
	 * Recursively render one branch of a term tree.
	 *
	 * @param array<int,\WP_Term[]> $by_parent    Terms grouped by parent ID.
	 * @param int                   $parent_id    Parent term ID (0 for top level).
	 * @param string                $name         Checkbox `name` attribute.
	 * @param int[]                 $selected_ids Selected term IDs.
	 */
	private static function render_term_branch( array $by_parent, int $parent_id, string $name, array $selected_ids ): void {
		if ( empty( $by_parent[ $parent_id ] ) ) {
			return;
		}
		?>
		<ul class="beyondwords-setting__preselect--term-list" style="margin: 0; padding: 0; list-style: none;">
			<?php foreach ( $by_parent[ $parent_id ] as $term ) : ?>
				<li class="beyondwords-setting__preselect--term" style="margin: 0.5rem 0 0 1.5rem;">
					<label>
						<input
							type="checkbox"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( (string) $term->term_id ); ?>"
							<?php checked( in_array( (int) $term->term_id, $selected_ids, true ) ); ?>
						/>
						<?php echo esc_html( $term->name ); ?>
					</label>
					<?php self::render_term_branch( $by_parent, (int) $term->term_id, $name, $selected_ids ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Hierarchical taxonomy objects shown in the editor for a post type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return \WP_Taxonomy[]
	 */
	private static function get_hierarchical_taxonomies( string $post_type ): array {
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		return array_values(
			array_filter(
				$taxonomies,
				static function ( $taxonomy ) {
					return $taxonomy->hierarchical && $taxonomy->show_ui;
				}
			)
		);
	}

	/**
	 * Names of the hierarchical taxonomies for a post type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string[]
	 */
	private static function get_hierarchical_taxonomy_names( string $post_type ): array {
		return array_values( wp_list_pluck( self::get_hierarchical_taxonomies( $post_type ), 'name' ) );
	}
}
