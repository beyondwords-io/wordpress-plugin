<?php
/**
 * Admin notices for the BeyondWords bulk-edit actions.
 *
 * @package BeyondWords\AdminPosts
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\AdminPosts;

defined( 'ABSPATH' ) || exit;

/**
 * One notice per post-bulk-action result, all gated on the same nonce so
 * direct URL fiddling can't surface arbitrary text.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Notices {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ self::class, 'generated_notice' ] );
		add_action( 'admin_notices', [ self::class, 'deleted_notice' ] );
		add_action( 'admin_notices', [ self::class, 'failed_notice' ] );
		add_action( 'admin_notices', [ self::class, 'error_notice' ] );
	}

	/**
	 * "Audio was requested for N posts." notice after a Generate Audio bulk action.
	 */
	public static function generated_notice(): void {
		$count = self::get_query_count( 'beyondwords_bulk_generated' );

		if ( null === $count ) {
			return;
		}

		$message = sprintf(
			/* translators: %d is replaced with the number of posts processed */
			_n(
				'Audio was requested for %d post.',
				'Audio was requested for %d posts.',
				$count,
				'speechkit'
			),
			$count
		);
		?>
		<div id="beyondwords-bulk-edit-notice-generated" class="notice notice-info is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * "Audio was deleted for N posts." notice after a Delete Audio bulk action.
	 */
	public static function deleted_notice(): void {
		$count = self::get_query_count( 'beyondwords_bulk_deleted' );

		if ( null === $count ) {
			return;
		}

		$message = sprintf(
			/* translators: %d is replaced with the number of posts processed */
			_n(
				'Audio was deleted for %d post.',
				'Audio was deleted for %d posts.',
				$count,
				'speechkit'
			),
			$count
		);
		?>
		<div id="beyondwords-bulk-edit-notice-deleted" class="notice notice-info is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * "N posts failed." notice after a bulk action where some items errored.
	 */
	public static function failed_notice(): void {
		$count = self::get_query_count( 'beyondwords_bulk_failed' );

		if ( null === $count ) {
			return;
		}

		$message = sprintf(
			/* translators: %d is replaced with the number of posts that were skipped */
			_n(
				'%d post failed, check for errors in the BeyondWords column below.',
				'%d posts failed, check for errors in the BeyondWords column below.',
				$count,
				'speechkit'
			),
			$count
		);
		?>
		<div id="beyondwords-bulk-edit-notice-failed" class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Top-level error notice after a bulk action that threw.
	 */
	public static function error_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! self::verify_result_nonce() || ! isset( $_GET['beyondwords_bulk_error'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = sanitize_text_field( wp_unslash( $_GET['beyondwords_bulk_error'] ) );

		if ( '' === $message ) {
			return;
		}
		?>
		<div id="beyondwords-bulk-edit-notice-error" class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Verify the result-nonce embedded in bulk-action redirects, fatally exiting
	 * via `wp_nonce_ays()` on tamper.
	 *
	 * Returns false (without exiting) when the nonce param is absent so callers
	 * can short-circuit normal page loads cheaply.
	 */
	private static function verify_result_nonce(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['beyondwords_bulk_edit_result_nonce'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['beyondwords_bulk_edit_result_nonce'] ) ), 'beyondwords_bulk_edit_result' ) ) {
			wp_nonce_ays( '' );
		}

		return true;
	}

	/**
	 * Read a positive integer count from `$_GET[$key]` after verifying the result nonce.
	 *
	 * @return int|null `null` when nonce missing, count param missing, or count not positive.
	 */
	private static function get_query_count( string $key ): ?int {
		if ( ! self::verify_result_nonce() ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $key ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count = (int) sanitize_text_field( wp_unslash( $_GET[ $key ] ) );

		return $count > 0 ? $count : null;
	}
}
