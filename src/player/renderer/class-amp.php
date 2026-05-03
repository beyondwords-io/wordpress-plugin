<?php
/**
 * AMP-compatible BeyondWords player renderer.
 *
 * Used when the request is being served through an AMP plugin
 * (`\BeyondWords\Core\Utils::is_amp()`).
 *
 * @package BeyondWords\Player\Renderer
 * @since   6.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Player\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the player as an `<amp-iframe>` so it survives the AMP validator.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Amp extends Base {

	/**
	 * Whether the AMP renderer applies to this request.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function check( \WP_Post $post ): bool {
		if ( ! \BeyondWords\Core\Utils::is_amp() ) {
			return false;
		}

		return parent::check( $post );
	}

	/**
	 * Render the AMP player HTML.
	 *
	 * @param \WP_Post $post    Post object.
	 * @param string   $context Rendering context: 'auto' or 'shortcode'.
	 */
	public static function render( \WP_Post $post, string $context = 'shortcode' ): string {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post->ID );
		$content_id = \BeyondWords\Post\Meta::get_content_id( $post->ID, true );

		$src = sprintf( \BeyondWords\Core\Environment::get_amp_player_url(), $project_id, $content_id );

		ob_start();
		?>
		<amp-iframe
			data-beyondwords-player-context="<?php echo esc_attr( $context ); ?>"
			frameborder="0"
			height="43"
			layout="responsive"
			sandbox="allow-scripts allow-same-origin allow-popups"
			scrolling="no"
			src="<?php echo esc_url( $src ); ?>"
			width="295"
		>
			<amp-img
				height="150"
				layout="responsive"
				placeholder
				src="<?php echo esc_url( \BeyondWords\Core\Environment::get_amp_img_url() ); ?>"
				width="643"
			></amp-img>
		</amp-iframe>
		<?php
		return ob_get_clean();
	}
}
