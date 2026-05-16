<?php
/**
 * Base class for player renderers.
 *
 * Subclasses (`Amp`, `Javascript`) override `check()` to decide whether their
 * variant applies, and add a `render()` method to produce HTML.
 *
 * @package BeyondWords\Player\Renderer
 * @since   6.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Player\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Common eligibility checks for every renderer.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Base {

	/**
	 * Whether a player should be rendered for this post in this request.
	 *
	 * Returns false during preview, in the block editor, or when the post is
	 * missing a project ID. For REST API integration we additionally require
	 * a content ID; Magic Embed (client-side) doesn't need one.
	 *
	 * @param \WP_Post $post WordPress post object.
	 */
	public static function check( \WP_Post $post ): bool {
		if ( function_exists( 'is_preview' ) && is_preview() ) {
			return false;
		}

		if ( \BeyondWords\Core\Utils::is_gutenberg_page() || \BeyondWords\Core\Utils::is_edit_screen() ) {
			return false;
		}

		$project_id = \BeyondWords\Post\Meta::get_project_id( $post->ID );

		if ( ! $project_id ) {
			return false;
		}

		$content_id = \BeyondWords\Post\Meta::get_content_id( $post->ID );
		$method     = \BeyondWords\Settings\Fields::get_integration_method( $post );

		return \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $method
			|| ( \BeyondWords\Settings\Fields::INTEGRATION_REST_API === $method && $content_id );
	}
}
