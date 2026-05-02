<?php
/**
 * JavaScript BeyondWords player renderer.
 *
 * @package BeyondWords\Player\Renderer
 * @since   6.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Player\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the BeyondWords player as a SDK script tag with an inline init call.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Javascript extends Base {

	/**
	 * Render the JavaScript player HTML.
	 *
	 * Returns an empty string when Player UI is set to "Disabled" — the post
	 * is otherwise eligible (per `Base::check()`), the publisher has just
	 * opted out of the visible player UI.
	 *
	 * @param \WP_Post $post    Post object.
	 * @param string   $context Rendering context: 'auto' or 'shortcode'.
	 */
	public static function render( \WP_Post $post, string $context = 'shortcode' ): string {
		if ( \BeyondWords\Settings\Fields::PLAYER_UI_DISABLED === get_option( \BeyondWords\Settings\Fields::OPTION_PLAYER_UI ) ) {
			return '';
		}

		$params      = \BeyondWords\Player\ConfigBuilder::build( $post );
		$json_params = wp_json_encode( $params, JSON_UNESCAPED_SLASHES );
		$json_params = sprintf( '{target:this, ...%s}', $json_params );

		$onload = sprintf( 'new BeyondWords.Player(%s);', $json_params );
		$onload = apply_filters( 'beyondwords_player_script_onload', $onload, $params );

		return sprintf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			'<script data-beyondwords-player-context="%s" async defer src="%s" onload=\'%s\'></script>',
			esc_attr( $context ),
			\BeyondWords\Core\Environment::get_js_sdk_url(),
			$onload
		);
	}
}
