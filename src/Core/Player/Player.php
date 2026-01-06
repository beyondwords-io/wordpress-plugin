<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Environment;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Player
 *
 * Entry point for registering player-related WordPress hooks.
 */
defined('ABSPATH') || exit;

class Player
{
    /**
     * List of renderer class names (must have static check() and render()).
     *
     * @var string[]
     */
    protected static array $renderers = [
        Renderer\Amp::class,
        Renderer\Javascript::class,
    ];

    /**
     * Add WordPress hooks.
     */
    public static function init(): void
    {
        // Actions.
        add_action('init', [self::class, 'registerShortcodes']);

        // Filters.
        add_filter('the_content', [self::class, 'replaceLegacyCustomPlayer'], 5);
        add_filter('the_content', [self::class, 'autoPrependPlayer'], 1000000);
        add_filter('newsstand_the_content', [self::class, 'autoPrependPlayer']);
    }

    /**
     * Register the [beyondwords_player] shortcode.
     */
    public static function registerShortcodes(): void
    {
        add_shortcode('beyondwords_player', fn() => self::renderPlayer());
    }

    /**
     * Conditionally prepend the player to a string (the post content).
     *
     *
     */
    public static function autoPrependPlayer($content)
    {
        if (! is_singular() || self::hasCustomPlayer($content)) {
            return $content;
        }

        return self::renderPlayer() . $content;
    }

    /**
     * Replace the legacy custom player div with the shortcode.
     *
     * @since 6.0.0
     * @since 6.0.1 Use regex to match legacy player divs.
     *
     * @param string $content The post content.
     *
     * @return string The post content.
     */
    public static function replaceLegacyCustomPlayer($content)
    {
        if (! is_singular()) {
            return $content;
        }

        // Use regex to match legacy player divs with any whitespace or attribute ordering.
        // data-beyondwords-player is a boolean attribute - its presence indicates a player div,
        // regardless of the value (true, false, or any other value).
        // This handles variations like:
        // - <div data-beyondwords-player="true"></div>
        // - <div data-beyondwords-player="anything"></div>
        // - <div data-beyondwords-player></div> (boolean attribute)
        // - <div data-beyondwords-player contenteditable="false"></div>
        // - <div contenteditable="false" data-beyondwords-player> </div>
        // - <div data-beyondwords-player />
        $pattern = '/<div\s+(?=[^>]*data-beyondwords-player[\s>=\/])[^>]*(?:\/>|>\s*<\/div>)/i';

        return preg_replace($pattern, '[beyondwords_player]', $content);
    }

    /**
     * Render a player (AMP/JS depending on context).
     */
    public static function renderPlayer(): string
    {
        $post = get_post();

        if (! $post instanceof \WP_Post || ! self::isEnabled($post)) {
            return '';
        }

        $html = '';

        foreach (self::$renderers as $rendererClass) {
            if (is_callable([$rendererClass, 'check']) && $rendererClass::check($post)) {
                if (is_callable([$rendererClass, 'render'])) {
                    $html = $rendererClass::render($post);
                    break;
                }
            }
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);
        $contentId = PostMetaUtils::getContentId($post->ID, true);

        /**
         * Filters the HTML of the BeyondWords Player.
         *
         * @since 4.0.0
         * @since 4.3.0 Applied to all player renderers (AMP and JavaScript).
         *
         * @param string $html      The HTML for the audio player.
         * @param int    $postId    WordPress post ID.
         * @param int    $projectId BeyondWords project ID.
         * @param int    $contentId BeyondWords content ID.
         */
        $html = apply_filters('beyondwords_player_html', $html, $post->ID, $projectId, $contentId);

        return $html;
    }

    /**
     * Check if the player is enabled for a post. This considers "Headless" mode
     * as enabled since we still want to output the player script tag for Headless.
     *
     * @param \WP_Post $post Post object.
     *
     * @return bool True if the player is enabled.
     */
    public static function isEnabled(\WP_Post $post): bool
    {
        if (PostMetaUtils::getDisabled($post->ID)) {
            return false;
        }

        // Default to "Enabled".
        $playerUI = get_option(PlayerUI::OPTION_NAME, PlayerUI::ENABLED);

        $enabled = [PlayerUI::ENABLED, PlayerUI::HEADLESS];

        return in_array($playerUI, $enabled, true);
    }

    /**
     * Detect if a custom player is already in the content.
     *
     *
     */
    public static function hasCustomPlayer(string $content): bool
    {
        // Detect shortcode.
        if (has_shortcode($content, 'beyondwords_player')) {
            return true;
        }

        $crawler = new Crawler($content);

        // Detect player script tag.
        $scriptXpath = sprintf('//script[@async][@defer][contains(@src, "%s")]', Environment::getJsSdkUrl());
        if ($crawler->filterXPath($scriptXpath)->count() > 0) {
            return true;
        }

        // Detect legacy player div.
        if ($crawler->filterXPath('//div[@data-beyondwords-player="true"]')->count() > 0) {
            return true;
        }

        return false;
    }
}
