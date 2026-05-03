<?php
/**
 * BeyondWords column on the posts list screen.
 *
 * @package BeyondWords\AdminPosts
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\AdminPosts;

defined( 'ABSPATH' ) || exit;

/**
 * Custom "BeyondWords" column for compatible post-type list tables, with
 * sortable header support.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Column {

	const ALLOWED_HTML = [
		'span' => [
			'class' => [],
		],
	];

	const OUTPUT_YES          = '<span class="dashicons dashicons-yes"></span> ';
	const OUTPUT_NO           = '—';
	const OUTPUT_DISABLED     = ' <span class="beyondwords--disabled">Disabled</span>';
	const OUTPUT_ERROR_PREFIX = '<span class="dashicons dashicons-warning"></span> ';

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action(
			'wp_loaded',
			static function (): void {
				$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

				if ( ! is_array( $post_types ) ) {
					return;
				}

				foreach ( $post_types as $post_type ) {
					add_filter( "manage_{$post_type}_posts_columns", [ self::class, 'render_columns_head' ] );
					add_action( "manage_{$post_type}_posts_custom_column", [ self::class, 'render_columns_content' ], 10, 2 );
					add_filter( "manage_edit-{$post_type}_sortable_columns", [ self::class, 'make_column_sortable' ] );
				}
			}
		);

		if ( \BeyondWords\Core\Utils::is_edit_screen() ) {
			add_action( 'pre_get_posts', [ self::class, 'set_sort_query' ] );
		}
	}

	/**
	 * Append the BeyondWords column to the columns header array.
	 *
	 * @param array<string,string> $columns Existing column headers.
	 *
	 * @return array<string,string>
	 */
	public static function render_columns_head( $columns ) {
		return array_merge(
			$columns,
			[ 'beyondwords' => __( 'BeyondWords', 'speechkit' ) ]
		);
	}

	/**
	 * Render the cell contents for the BeyondWords column.
	 *
	 * Output is one of: an error message (with warning dashicon), a green tick
	 * if audio exists, or an em dash if not. A "Disabled" badge is appended
	 * when the post has BeyondWords playback disabled.
	 *
	 * @param string $column_name Column slug being rendered.
	 * @param int    $post_id     Post ID.
	 */
	public static function render_columns_content( $column_name, $post_id ): void {
		if ( 'beyondwords' !== $column_name ) {
			return;
		}

		if ( empty( \BeyondWords\Settings\Utils::get_compatible_post_types() ) ) {
			return;
		}

		$error_message = \BeyondWords\Post\Meta::get_error_message( $post_id );
		$has_content   = \BeyondWords\Post\Meta::has_content( $post_id );
		$disabled      = \BeyondWords\Post\Meta::get_disabled( $post_id );

		if ( ! empty( $error_message ) ) {
			echo wp_kses( self::OUTPUT_ERROR_PREFIX . $error_message, self::ALLOWED_HTML );
		} elseif ( $has_content ) {
			echo wp_kses( self::OUTPUT_YES, self::ALLOWED_HTML );
		} else {
			echo wp_kses( self::OUTPUT_NO, self::ALLOWED_HTML );
		}

		if ( ! empty( $disabled ) ) {
			echo wp_kses( self::OUTPUT_DISABLED, self::ALLOWED_HTML );
		}
	}

	/**
	 * Mark the BeyondWords column sortable.
	 *
	 * @param array<string,string> $sortable_columns Existing sortable columns.
	 *
	 * @return array<string,string>
	 */
	public static function make_column_sortable( $sortable_columns ) {
		$sortable_columns['beyondwords'] = 'beyondwords';
		return $sortable_columns;
	}

	/**
	 * Apply the BeyondWords meta-query sort to the main query when the user
	 * clicks the BeyondWords column header.
	 *
	 * @param \WP_Query $query Query object, modified in place.
	 *
	 * @return \WP_Query
	 */
	public static function set_sort_query( $query ) {
		if ( 'beyondwords' === $query->get( 'orderby' ) && $query->is_main_query() ) {
			$query->set( 'meta_query', self::get_sort_query_args() );
			$query->set( 'orderby', 'meta_value_num date' );
		}

		return $query;
	}

	/**
	 * Meta query that orders posts with audio metadata before posts without.
	 *
	 * Two clauses with `relation: OR` so posts that have *never* had audio
	 * still appear in the list rather than being filtered out.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_sort_query_args(): array {
		return [
			'relation' => 'OR',
			[
				'key'     => 'beyondwords_generate_audio',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'beyondwords_generate_audio',
				'compare' => 'EXISTS',
			],
		];
	}
}
