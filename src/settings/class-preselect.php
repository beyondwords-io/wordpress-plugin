<?php
/**
 * Preselect setting.
 *
 * Lets the publisher pick which post types have "Generate audio" ticked by
 * default in the post editor — either for every post of that type, or only
 * for posts assigned one of a chosen set of hierarchical taxonomy terms.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 * @since 7.0.0 Reinstated term-gating for all hierarchical taxonomies, stored
 *              in a mode-based format.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Preselect setting.
 *
 * Stored as a map keyed by post type, each value an array with a `mode`:
 *
 *     [
 *       'post' => [ 'mode' => 'all' ],
 *       'page' => [
 *         'mode'  => 'terms',
 *         'terms' => [ 'category' => [ 12, 34 ], 'genre' => [ 56 ] ],
 *       ],
 *     ]
 *
 * - A post type absent from the map is never preselected.
 * - `mode => 'all'`   preselects every post of that type.
 * - `mode => 'terms'` preselects only posts that have at least one of the
 *   listed term IDs (exact match, OR across taxonomies/terms).
 *
 * The pre-7.0.0 shapes (`'1'` for whole post type, `[ taxonomy => [ ids ] ]`
 * for term-gating) are read defensively here and converted to this format by
 * the migration in `Updater::run()`.
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
	 * Tolerant of the pre-7.0.0 shapes so behaviour is correct even before the
	 * migration has run: `'1'` reads as `all`, a non-empty taxonomy array reads
	 * as `terms`.
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
	 * - `all`   → always true.
	 * - `terms` → true when the post has at least one listed term in a
	 *             currently-registered taxonomy (exact match, OR semantics).
	 * - `off`   → false.
	 *
	 * Tolerant of taxonomies that have since been unregistered or detached from
	 * the post type — they are skipped, never fatal.
	 *
	 * @param \WP_Post|int $post Post object or ID.
	 */
	public static function should_preselect_for_post( $post ): bool {
		$post_type = get_post_type( $post );

		if ( ! $post_type ) {
			return false;
		}

		// Use get(), whose explicit DEFAULT_VALUE fallback applies in every
		// context (admin, REST block-editor saves, cron) — not just where the
		// setting is registered. This keeps the server's generate decision in
		// step with the editor's default-bearing display, since the block
		// editor no longer writes the meta itself (it derives the toggle).
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
	 * Merge-preserve: only post types and taxonomies actually rendered this
	 * request are taken from the submission. Config for post types that are not
	 * currently compatible, and terms for taxonomies that are not currently
	 * registered, are preserved from the stored value — so toggling a CPT or
	 * taxonomy plugin off and saving settings never wipes the configuration.
	 *
	 * @param mixed $value Raw submitted value.
	 *
	 * @return array<string,mixed>
	 */
	public static function sanitize( $value ): array {
		// Use the RAW stored option as the merge base — not get(), which falls
		// back to DEFAULT_VALUE and would otherwise leak `post => all` into a
		// fresh save where 'post' isn't even a compatible post type.
		$raw      = get_option( self::OPTION_NAME );
		$existing = is_array( $raw ) ? $raw : [];

		if ( ! is_array( $value ) ) {
			return $existing;
		}

		// Start from the stored value to preserve post types not rendered now.
		$clean = $existing;

		foreach ( Utils::get_compatible_post_types() as $post_type ) {
			$submitted = ( isset( $value[ $post_type ] ) && is_array( $value[ $post_type ] ) ) ? $value[ $post_type ] : [];

			// The post-type checkbox ("all") wins over any ticked terms — same
			// precedence the v6 UI implied, made explicit here.
			if ( ! empty( $submitted['all'] ) ) {
				$clean[ $post_type ] = [ 'mode' => self::MODE_ALL ];
				continue;
			}

			$terms = self::sanitize_terms( $post_type, $submitted, $existing );

			if ( ! empty( $terms ) ) {
				$clean[ $post_type ] = [
					'mode'  => self::MODE_TERMS,
					'terms' => $terms,
				];
			} else {
				unset( $clean[ $post_type ] );
			}
		}

		return $clean;
	}

	/**
	 * Sanitise the term map for one post type, merge-preserving terms for
	 * taxonomies not rendered in this request.
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
	 * Render the control for one post type (v6 layout): a single checkbox to
	 * preselect the whole type, followed by an indented hierarchical term tree
	 * to instead preselect only posts that have one of the ticked terms.
	 *
	 * Ticking the post-type checkbox stores `mode => 'all'`; ticking terms
	 * (with the checkbox off) stores `mode => 'terms'`; nothing ticked is off.
	 *
	 * @param \WP_Post_Type       $post_type_object Post type object.
	 * @param array<string,mixed> $preselect        Stored option.
	 */
	private static function render_post_type( $post_type_object, array $preselect ): void {
		$post_type      = $post_type_object->name;
		$mode           = self::get_mode( $post_type, $preselect );
		$selected_terms = self::get_selected_terms( $post_type, $preselect );
		$base           = self::OPTION_NAME . '[' . $post_type . ']';
		?>
		<div class="beyondwords-setting__preselect--post-type">
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( $base . '[all]' ); ?>"
					value="1"
					<?php checked( self::MODE_ALL === $mode ); ?>
				/>
				<?php echo esc_html( $post_type_object->label ); ?>
			</label>
			<?php self::render_taxonomy_terms( $post_type_object, $selected_terms ); ?>
		</div>
		<?php
	}

	/**
	 * Render the hierarchical taxonomy term trees for a post type, indented
	 * beneath the post-type checkbox.
	 *
	 * @param \WP_Post_Type       $post_type_object Post type object.
	 * @param array<string,int[]> $selected_terms   Selected term IDs by taxonomy.
	 */
	private static function render_taxonomy_terms( $post_type_object, array $selected_terms ): void {
		$taxonomies = self::get_hierarchical_taxonomies( $post_type_object->name );

		if ( empty( $taxonomies ) ) {
			return;
		}
		?>
		<div class="beyondwords-setting__preselect--taxonomy" style="margin: 0.5rem 0;">
			<?php foreach ( $taxonomies as $taxonomy ) : ?>
				<h4 style="margin: 0.5rem 0 0.5rem 1.5rem;"><?php echo esc_html( $taxonomy->label ); ?></h4>
				<?php
				$ids  = $selected_terms[ $taxonomy->name ] ?? [];
				$name = self::OPTION_NAME . '[' . $post_type_object->name . '][terms][' . $taxonomy->name . '][]';
				self::render_term_tree( $taxonomy, $name, $ids );
				?>
			<?php endforeach; ?>
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
