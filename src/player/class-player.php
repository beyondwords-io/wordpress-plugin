<?php
/**
 * BeyondWords player entry point.
 *
 * Owns the front-end hooks that decide when and how a BeyondWords player is
 * rendered on a post. Per-context rendering is delegated to the renderer
 * classes under `src/player/renderer/`.
 *
 * @package BeyondWords\Player
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Player;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end player rendering coordinator.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Player {

	/**
	 * Renderer classes evaluated in order. The first whose `check()` returns
	 * true wins — keep AMP first so AMP requests get the AMP player.
	 *
	 * @var string[]
	 */
	protected static array $renderers = [
		\BeyondWords\Player\Renderer\Amp::class,
		\BeyondWords\Player\Renderer\Javascript::class,
	];

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_shortcodes' ] );

		// Replace legacy `<div data-beyondwords-player>` markup with the modern
		// shortcode early, then auto-prepend the player at very late priority
		// so other `the_content` filters can't strip it.
		add_filter( 'the_content', [ self::class, 'replace_legacy_custom_player' ], 5 );
		add_filter( 'the_content', [ self::class, 'auto_prepend_player' ], 1000000 );
		add_filter( 'newsstand_the_content', [ self::class, 'auto_prepend_player' ] );
	}

	/**
	 * Register the `[beyondwords_player]` shortcode.
	 */
	public static function register_shortcodes(): void {
		add_shortcode( 'beyondwords_player', static fn() => self::render_player( 'shortcode' ) );
	}

	/**
	 * Auto-prepend the player to `the_content` on singular pages, unless the
	 * editor has already placed a player via shortcode/script/legacy div.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public static function auto_prepend_player( $content ) {
		if ( ! is_singular() || self::has_custom_player( $content ) ) {
			return $content;
		}

		return self::render_player( 'auto' ) . $content;
	}

	/**
	 * Convert legacy `<div data-beyondwords-player>` placeholders to the
	 * modern shortcode so the rest of the pipeline only deals with one form.
	 *
	 * The regex tolerates attribute reordering, missing values (boolean
	 * attribute), and self-closing tags — see the inline examples.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public static function replace_legacy_custom_player( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

		// Examples this matches:
		// <div data-beyondwords-player="true"></div>
		// <div data-beyondwords-player></div>
		// <div data-beyondwords-player contenteditable="false"></div>
		// <div contenteditable="false" data-beyondwords-player> </div>
		// <div data-beyondwords-player />
		$pattern = '/<div\s+(?=[^>]*data-beyondwords-player[\s>=\/])[^>]*(?:\/>|>\s*<\/div>)/i';

		return preg_replace( $pattern, '[beyondwords_player]', $content );
	}

	/**
	 * Pick a renderer for the current post and return its HTML.
	 *
	 * The `beyondwords_player_html` filter runs even when no renderer matches
	 * — that way third-party code can still inject markup conditionally.
	 *
	 * @param string $context 'auto' (auto-prepend) or 'shortcode'.
	 */
	public static function render_player( string $context = 'shortcode' ): string {
		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! self::is_enabled( $post ) ) {
			return '';
		}

		$html = '';

		foreach ( self::$renderers as $renderer_class ) {
			if (
				is_callable( [ $renderer_class, 'check' ] )
				&& $renderer_class::check( $post )
				&& is_callable( [ $renderer_class, 'render' ] )
			) {
				$html = $renderer_class::render( $post, $context );
				break;
			}
		}

		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post->ID );
		$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post->ID, true );

		/**
		 * Filters the HTML of the BeyondWords Player.
		 *
		 * @since 4.0.0
		 * @since 4.3.0 Applied to all player renderers (AMP and JavaScript).
		 * @since 6.1.0 Added `$context` parameter.
		 *
		 * @param string $html       Audio player HTML.
		 * @param int    $post_id    WordPress post ID.
		 * @param int    $project_id BeyondWords project ID.
		 * @param int    $content_id BeyondWords content ID.
		 * @param string $context    Rendering context: 'auto' or 'shortcode'.
		 */
		return apply_filters( 'beyondwords_player_html', $html, $post->ID, $project_id, $content_id, $context );
	}

	/**
	 * Whether the player should be active for this post.
	 *
	 * Headless mode counts as enabled — we still emit the SDK script so the
	 * publisher's own UI can drive it.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function is_enabled( \WP_Post $post ): bool {
		if ( \BeyondWords\Post\PostMetaUtils::get_disabled( $post->ID ) ) {
			return false;
		}

		$player_ui = get_option( \BeyondWords\Settings\Fields::OPTION_PLAYER_UI, \BeyondWords\Settings\Fields::PLAYER_UI_ENABLED );

		return in_array(
			$player_ui,
			[ \BeyondWords\Settings\Fields::PLAYER_UI_ENABLED, \BeyondWords\Settings\Fields::PLAYER_UI_HEADLESS ],
			true
		);
	}

	/**
	 * Detect whether the post content already contains a BeyondWords player.
	 *
	 * @param string $content Post content.
	 */
	public static function has_custom_player( string $content ): bool {
		if ( has_shortcode( $content, 'beyondwords_player' ) ) {
			return true;
		}

		$crawler = new \Symfony\Component\DomCrawler\Crawler( $content );

		$script_xpath = sprintf( '//script[@async][@defer][contains(@src, "%s")]', \BeyondWords\Core\Environment::get_js_sdk_url() );
		if ( $crawler->filterXPath( $script_xpath )->count() > 0 ) {
			return true;
		}

		if ( $crawler->filterXPath( '//div[@data-beyondwords-player="true"]' )->count() > 0 ) {
			return true;
		}

		return false;
	}
}
