<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Player
 *
 * Entry point for registering player-related WordPress hooks.
 */
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
     *
     * @return void
     */
    public static function init(): void
    {
        // Actions.
        add_action('init', array(__CLASS__, 'registerShortcodes'));

        // Filters.
        add_filter('the_content', array(__CLASS__, 'replaceLegacyCustomPlayer'), 5);
        add_filter('the_content', array(__CLASS__, 'autoPrependPlayer'), 1000000);
        add_filter('newsstand_the_content', array(__CLASS__, 'autoPrependPlayer'));
    }

    /**
     * Register the [beyondwords_player] shortcode.
     *
     * @return void
     */
    public static function registerShortcodes(): void
    {
        add_shortcode('beyondwords_player', fn() => self::renderPlayer());
    }

    /**
     * Conditionally prepend the player to a string (the post content).
     *
     * @param string $content
     *
     * @return string
     */
    public static function autoPrependPlayer(string $content): string
    {
        if (! is_singular() || self::hasCustomPlayer($content)) {
            return $content;
        }

        return self::renderPlayer() . $content;
    }

    /**
     * Replace the legacy custom player div with the shortcode.
     *
     * @param string $content
     *
     * @return string
     */
    public static function replaceLegacyCustomPlayer(string $content): string
    {
        if (! is_singular()) {
            return $content;
        }

        return str_replace(
            [
                '<div data-beyondwords-player="true"></div>',
                '<div data-beyondwords-player="true" contenteditable="false"></div>',
                '<div data-beyondwords-player="true" />',
            ],
            '[beyondwords_player]',
            $content
        );
    }

    /**
     * Render a player (AMP/JS depending on context).
     *
     * @return string
     */
    public static function renderPlayer(): string
    {
        $post = get_post();

        if (! $post instanceof \WP_Post || ! self::isEnabled($post)) {
            return '';
        }

        foreach (self::$renderers as $rendererClass) {
            if (is_callable([$rendererClass, 'check']) && $rendererClass::check($post)) {
                if (is_callable([$rendererClass, 'render'])) {
                    return $rendererClass::render($post);
                }
            }
        }

        return '';
    }

    /**
     * Check if the player is enabled for a post.
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

        return get_option('beyondwords_player_ui', PlayerUI::ENABLED) === PlayerUI::ENABLED;
    }

    /**
     * Detect if a custom player is already in the content.
     *
     * @param string $content
     *
     * @return bool
     */
    public static function hasCustomPlayer(string $content): bool
    {
        // Check for shortcode.
        if (has_shortcode($content, 'beyondwords_player')) {
            return true;
        }

        // Check for legacy player div.
        return (new Crawler($content))
            ->filterXPath('//div[@data-beyondwords-player="true"]')
            ->count() > 0;
    }
}
