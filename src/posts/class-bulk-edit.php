<?php
/**
 * Bulk Edit handler for the posts list screen.
 *
 * Adds a "BeyondWords" column to the bulk-edit dropdown so multiple posts can
 * have audio generated or deleted in one action.
 *
 * @package BeyondWords\Posts
 * @since   3.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Bulk-edit support for the BeyondWords column.
 */
class BulkEdit {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'bulk_edit_custom_box', array( self::class, 'bulk_edit_custom_box' ), 10, 2 );
		add_action( 'wp_ajax_save_bulk_edit_beyondwords', array( self::class, 'save_bulk_edit' ) );

		add_action(
			'wp_loaded',
			static function (): void {
				$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

				if ( ! is_array( $post_types ) ) {
					return;
				}

				foreach ( $post_types as $post_type ) {
					add_filter( "bulk_actions-edit-{$post_type}", array( self::class, 'bulk_actions_edit' ) );
					add_filter( "handle_bulk_actions-edit-{$post_type}", array( self::class, 'handle_bulk_delete_action' ), 10, 3 );
					add_filter( "handle_bulk_actions-edit-{$post_type}", array( self::class, 'handle_bulk_generate_action' ), 10, 3 );
				}
			}
		);
	}

	/**
	 * Render the bulk-edit fieldset for the BeyondWords column.
	 *
	 * @param string $column_name Column slug being rendered.
	 * @param string $post_type   Current post-type screen.
	 */
	public static function bulk_edit_custom_box( $column_name, $post_type ): void {
		if ( 'beyondwords' !== $column_name ) {
			return;
		}

		if ( ! in_array( $post_type, \BeyondWords\Settings\Utils::get_compatible_post_types(), true ) ) {
			return;
		}

		wp_nonce_field( 'beyondwords_bulk_edit_nonce', 'beyondwords_bulk_edit' );
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<div class="inline-edit-group wp-clearfix">
					<label class="alignleft">
						<span class="title"><?php esc_html_e( 'BeyondWords', 'speechkit' ); ?></span>
						<select name="beyondwords_generate_audio">
							<option value="-1"><?php esc_html_e( '— No change —', 'speechkit' ); ?></option>
							<option value="generate"><?php esc_html_e( 'Generate audio', 'speechkit' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete audio', 'speechkit' ); ?></option>
						</select>
					</label>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * AJAX handler for the inline bulk-edit submission.
	 *
	 * Wired via `wp_ajax_save_bulk_edit_beyondwords`. Verifies the nonce that
	 * was emitted by `bulk_edit_custom_box()` before delegating to the
	 * generate/delete helpers.
	 *
	 * @return int[] IDs of posts that were updated.
	 */
	public static function save_bulk_edit(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if (
			! isset( $_POST['beyondwords_bulk_edit_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['beyondwords_bulk_edit_nonce'] ) ), 'beyondwords_bulk_edit' )
		) {
			wp_nonce_ays( '' );
		}

		if ( ! isset( $_POST['beyondwords_bulk_edit'] ) || ! isset( $_POST['post_ids'] ) || ! is_array( $_POST['post_ids'] ) ) {
			return array();
		}

		$post_ids = array_filter( array_map( 'intval', wp_unslash( $_POST['post_ids'] ) ) );
		$action   = sanitize_text_field( wp_unslash( $_POST['beyondwords_bulk_edit'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		switch ( $action ) {
			case 'generate':
				return self::generate_audio_for_posts( $post_ids );
			case 'delete':
				return self::delete_audio_for_posts( $post_ids );
		}

		return array();
	}

	/**
	 * Mark each post for audio generation, skipping ones that already have content.
	 *
	 * @param int[]|null $post_ids Posts to process.
	 *
	 * @return int[] IDs of posts updated.
	 */
	public static function generate_audio_for_posts( ?array $post_ids ): array {
		if ( ! is_array( $post_ids ) ) {
			return array();
		}

		$updated_post_ids = array();

		foreach ( $post_ids as $post_id ) {
			if ( ! get_post_meta( $post_id, 'beyondwords_content_id', true ) ) {
				update_post_meta( $post_id, 'beyondwords_generate_audio', '1' );
			}
			$updated_post_ids[] = $post_id;
		}

		return $updated_post_ids;
	}

	/**
	 * Delete BeyondWords audio for each post and clear the related post meta.
	 *
	 * @param int[]|null $post_ids Posts to process.
	 *
	 * @return int[] IDs of posts updated.
	 *
	 * @throws \Exception When the BeyondWords API does not return a deletable batch.
	 */
	public static function delete_audio_for_posts( ?array $post_ids ): array {
		if ( ! is_array( $post_ids ) ) {
			return array();
		}

		$response = \BeyondWords\Core\Core::batch_delete_audio_for_posts( $post_ids );

		if ( ! $response ) {
			throw new \Exception(
				esc_html__( 'Error while bulk deleting audio. Please contact support with reference BULK-NO-RESPONSE.', 'speechkit' )
			);
		}

		$keys             = \BeyondWords\Core\CoreUtils::get_post_meta_keys( 'all' );
		$updated_post_ids = array();

		foreach ( $response as $post_id ) {
			foreach ( $keys as $key ) {
				delete_post_meta( $post_id, $key );
			}
			$updated_post_ids[] = $post_id;
		}

		return $updated_post_ids;
	}

	/**
	 * Add BeyondWords actions to the bulk-action dropdown.
	 *
	 * @param array<string,string> $bulk_array Existing bulk actions.
	 *
	 * @return array<string,string>
	 */
	public static function bulk_actions_edit( $bulk_array ) {
		$bulk_array['beyondwords_generate_audio'] = __( 'Generate audio', 'speechkit' );
		$bulk_array['beyondwords_delete_audio']   = __( 'Delete audio', 'speechkit' );

		return $bulk_array;
	}

	/**
	 * Handle the "Generate audio" bulk action.
	 *
	 * @param string $redirect   Redirect URL the bulk handler will use.
	 * @param string $doaction   Selected bulk action.
	 * @param int[]  $object_ids Post IDs in the bulk selection.
	 */
	public static function handle_bulk_generate_action( $redirect, $doaction, $object_ids ) {
		if ( 'beyondwords_generate_audio' !== $doaction ) {
			return $redirect;
		}

		$redirect = remove_query_arg(
			array(
				'beyondwords_bulk_generated',
				'beyondwords_bulk_deleted',
				'beyondwords_bulk_failed',
				'beyondwords_bulk_error',
			),
			$redirect
		);

		sort( $object_ids );

		$generated = 0;
		$failed    = 0;

		try {
			foreach ( $object_ids as $post_id ) {
				update_post_meta( $post_id, 'beyondwords_generate_audio', '1' );
			}

			foreach ( $object_ids as $post_id ) {
				if ( \BeyondWords\Core\Core::generate_audio_for_post( $post_id ) ) {
					++$generated;
				} else {
					++$failed;
				}
			}
		} catch ( \Exception $e ) {
			$redirect = add_query_arg( 'beyondwords_bulk_error', $e->getMessage(), $redirect );
		}

		$redirect = add_query_arg( 'beyondwords_bulk_generated', $generated, $redirect );
		$redirect = add_query_arg( 'beyondwords_bulk_failed', $failed, $redirect );

		$nonce = wp_create_nonce( 'beyondwords_bulk_edit_result' );

		return add_query_arg( 'beyondwords_bulk_edit_result_nonce', $nonce, $redirect );
	}

	/**
	 * Handle the "Delete audio" bulk action.
	 *
	 * @param string $redirect   Redirect URL the bulk handler will use.
	 * @param string $doaction   Selected bulk action.
	 * @param int[]  $object_ids Post IDs in the bulk selection.
	 */
	public static function handle_bulk_delete_action( $redirect, $doaction, $object_ids ) {
		if ( 'beyondwords_delete_audio' !== $doaction ) {
			return $redirect;
		}

		$redirect = remove_query_arg(
			array(
				'beyondwords_bulk_generated',
				'beyondwords_bulk_deleted',
				'beyondwords_bulk_failed',
				'beyondwords_bulk_error',
			),
			$redirect
		);

		sort( $object_ids );

		try {
			$result   = self::delete_audio_for_posts( $object_ids );
			$redirect = add_query_arg( 'beyondwords_bulk_deleted', count( $result ), $redirect );
		} catch ( \Exception $e ) {
			$redirect = add_query_arg( 'beyondwords_bulk_error', $e->getMessage(), $redirect );
		}

		$nonce = wp_create_nonce( 'beyondwords_bulk_edit_result' );

		return add_query_arg( 'beyondwords_bulk_edit_result_nonce', $nonce, $redirect );
	}
}
