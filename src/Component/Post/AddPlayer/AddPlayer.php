<?php

declare(strict_types=1);

/**
 * BeyondWords "Add Player" component.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.2.0
 */

namespace Beyondwords\Wordpress\Component\Post\AddPlayer;

use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * AddPlayer
 *
 * @since 3.2.0
 */
class AddPlayer
{
    // The CSS declaration block for the player preview in both Classic Editor and Block Editor.
    public const PLAYER_PREVIEW_STYLE_FORMAT = "iframe [data-beyondwords-player]:empty:after, .edit-post-visual-editor [data-beyondwords-player]:empty:after { content: '%s'; }"; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('init', [self::class, 'registerBlock']);
        add_action('enqueue_block_editor_assets', [self::class, 'addBlockEditorStylesheet']);

        add_action('admin_head', [self::class, 'addEditorStyles']);
        add_filter('tiny_mce_before_init', [self::class, 'filterTinyMceSettings']);

        add_filter('mce_external_plugins', [self::class, 'addPlugin']);
        add_filter('mce_buttons', [self::class, 'addButton']);
        add_filter('mce_css', [self::class, 'addStylesheet']);
    }

    /**
     * Register Block.
     *
     * @since 3.2.0
     * @since 6.0.0 Make static.
     */
    public static function registerBlock()
    {
        \register_block_type(__DIR__);
    }

    /**
     * Add TinyMCE buttons.
     *
     * @since 6.0.0 Make static.
     *
     * @param array TinyMCE plugin array
     */
    public static function addPlugin(array $plugin_array): array
    {
        $plugin_array['beyondwords_player'] = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/tinymce.js';
        return $plugin_array;
    }

    /**
     * Register TinyMCE buttons.
     *
     * @since 6.0.0 Make static.
     *
     * @param array TinyMCE buttons array
     */
    public static function addButton(array $buttons): array
    {
        $advIndex = array_search('wp_adv', $buttons);

        if ($advIndex === false) {
            $advIndex = count($buttons);
        }

        array_splice($buttons, $advIndex, 0, ['beyondwords_player']);

        return $buttons;
    }

    /**
     * Filters the comma-delimited list of stylesheets to load in TinyMCE.
     *
     * @since 6.0.0 Make static.
     *
     * @param string $stylesheets Comma-delimited list of stylesheets.
     *
     * @return string Comma-delimited list of stylesheets with the "Add Player" CSS appended.
     */
    public static function addStylesheet(string $stylesheets): string
    {
        return $stylesheets . ',' . BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/AddPlayer.css';
    }

    /**
     * "Player Preview" i18n styles.
     *
     * Player preview uses the CSS :after to set the content so we pass the CSS through WordPress i18n functions here.
     *
     * @since 3.3.0
     * @since 6.0.0 Make static.
     *
     * @return string CSS Block for player preview i18n delcerations.
     */
    public static function playerPreviewI18nStyles()
    {
        return sprintf(
            self::PLAYER_PREVIEW_STYLE_FORMAT,
            esc_attr__('Player placeholder: The position of the audio player.', 'speechkit')
        );
    }

    /**
     * Tiny MCE before init.
     *
     * Adds i18n-compatible TinyMCE Classic Editor CSS for the player placeholder.
     *
     * @since 3.3.0
     * @since 6.0.0 Make static.
     *
     * @param mixed[] $setings An array with TinyMCE config.
     *
     * @return mixed[] An array with TinyMCE config.
     */
    public static function filterTinyMceSettings(array $settings): array
    {
        if (isset($settings['content_style'])) {
            $settings['content_style'] .= ' ' . self::playerPreviewI18nStyles() . ' ';
        } else {
            $settings['content_style'] = self::playerPreviewI18nStyles() . ' ';
        }

        return $settings;
    }

    /**
     * Add editor styles.
     *
     * Adds i18n-compatible Block Editor CSS for the player placeholder.
     *
     * @since 3.3.0
     * @since 6.0.0 Make static.
     */
    public static function addEditorStyles()
    {
        $allowed_html = [
            'style' => [],
        ];

        echo wp_kses(
            sprintf('<style>%s</style>', self::playerPreviewI18nStyles()),
            $allowed_html
        );
    }

    /**
     * Add Block Editor Stylesheet.
     *
     * @since 6.0.0 Make static.
     */
    public static function addBlockEditorStylesheet(string $hook): void
    {
        // Only enqueue for Gutenberg/Post screens
        if (CoreUtils::isGutenbergPage() || $hook === 'post.php' || $hook === 'post-new.php') {
            // Register the Classic/Block Editor "Add Player" CSS
            wp_enqueue_style(
                'beyondwords-AddPlayer',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/AddPlayer.css',
                [],
                BEYONDWORDS__PLUGIN_VERSION
            );
        }
    }
}
