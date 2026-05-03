<?php
/**
 * BeyondWords Add Player block — asset enqueue.
 *
 * Owns the editor stylesheet for the Add Player block placeholder. The
 * TinyMCE/MCE filter wiring stays in [class-add-player.php](class-add-player.php)
 * because those filters drive the same CSS through a different pipeline
 * (mce_css / content_style) rather than `wp_enqueue_style`.
 *
 * @package BeyondWords\Post\AddPlayer
 * @since   7.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Post\AddPlayer;

defined( 'ABSPATH' ) || exit;

/**
 * Add Player asset enqueue.
 *
 * @since 7.0.0
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue the AddPlayer CSS on Gutenberg / classic post screens.
	 *
	 * @param string|null $hook Current admin page hook (passed by the action).
	 */
	public static function enqueue_block_editor_assets( $hook = null ): void {
		if (
			\BeyondWords\Core\Utils::is_gutenberg_page()
			|| 'post.php' === $hook
			|| 'post-new.php' === $hook
		) {
			wp_enqueue_style(
				'beyondwords-AddPlayer',
				BEYONDWORDS__PLUGIN_URI . 'src/post/add-player/AddPlayer.css',
				[],
				BEYONDWORDS__PLUGIN_VERSION
			);
		}
	}
}
