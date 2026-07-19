<?php
/**
 * BeyondWords player entry point: front-end hooks that decide when a player renders.
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
	 * Renderer classes evaluated in order; the first whose `check()` passes wins.
	 *
	 * AMP stays first so AMP requests get the AMP player.
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

		// Legacy-markup replacement runs early; auto-prepend runs at very late
		// priority so other `the_content` filters can't strip the player.
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
	 * Auto-prepend the player to `the_content` unless the post already embeds one.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public static function auto_prepend_player( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

		// Mirrors render_player()'s own early return, but bails before the expensive
		// has_custom_player() DOM scan when no player could render anyway.
		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! self::is_enabled( $post ) ) {
			return $content;
		}

		if ( self::has_custom_player( $content ) ) {
			return $content;
		}

		return self::render_player( 'auto' ) . $content;
	}

	/**
	 * Convert legacy `<div data-beyondwords-player>` placeholders to the shortcode.
	 *
	 * The regex tolerates attribute reordering, valueless (boolean) attributes,
	 * extra attributes, and self-closing tags.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public static function replace_legacy_custom_player( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

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

		$project_id = \BeyondWords\Post\Meta::get_project_id( $post->ID );
		$content_id = \BeyondWords\Post\Meta::get_content_id( $post->ID, true );

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
	 * @since 7.0.0 "Embed: None" hides the player; the legacy disabled flag is still honoured.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function is_enabled( \WP_Post $post ): bool {
		if ( \BeyondWords\Editor\Components\SettingsFields::is_player_disabled_for_post( $post->ID ) ) {
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

		$js_sdk_url = \BeyondWords\Core\Urls::get_js_sdk_url();

		// Both XPath queries need one of these literal substrings, so when neither
		// appears we can skip the expensive per-pageview DOM parse.
		if (
			! str_contains( $content, 'data-beyondwords-player' )
			&& ! str_contains( $content, $js_sdk_url )
		) {
			return false;
		}

		$crawler = new \Symfony\Component\DomCrawler\Crawler( $content );

		$script_xpath = sprintf( '//script[@async][@defer][contains(@src, "%s")]', $js_sdk_url );
		if ( $crawler->filterXPath( $script_xpath )->count() > 0 ) {
			return true;
		}

		if ( $crawler->filterXPath( '//div[@data-beyondwords-player="true"]' )->count() > 0 ) {
			return true;
		}

		return false;
	}
}
