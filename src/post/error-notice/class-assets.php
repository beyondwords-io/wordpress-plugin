<?php
/**
 * BeyondWords Error Notice — asset enqueue.
 *
 * Owns the block-editor stylesheet for the post error notice. Split from
 * [class-error-notice.php](class-error-notice.php) so each feature folder
 * has a single class responsible for asset registration.
 *
 * @package BeyondWords\Post\ErrorNotice
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\ErrorNotice;

defined( 'ABSPATH' ) || exit;

/**
 * Error notice asset enqueue.
 *
 * @since 7.0.0
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_assets', [ self::class, 'enqueue_block_assets' ] );
	}

	/**
	 * Enqueue the error-notice CSS on Gutenberg screens.
	 */
	public static function enqueue_block_assets(): void {
		if ( ! \BeyondWords\Core\Utils::is_gutenberg_page() ) {
			return;
		}

		wp_enqueue_style(
			'beyondwords-ErrorNotice',
			BEYONDWORDS__PLUGIN_URI . 'src/post/error-notice/error-notice.css',
			[],
			BEYONDWORDS__PLUGIN_VERSION
		);
	}
}
