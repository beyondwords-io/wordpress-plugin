<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\CoreUtils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The BeyondWords Player.
 *
 * This is an alternate Player class using the inline script method that
 * is recommended in the player docs.
 *
 * @link https://github.com/beyondwords-io/player/blob/main/doc/getting-started.md.
 **/
class PlayerInline
{
    /**
     * Init.
     */
    public function init()
    {
        // Actions
        add_action('init', array($this, 'registerShortcodes'));

        // Filters
        add_filter('the_content', array($this, 'autoPrependPlayer'), 1000000);
        add_filter('newsstand_the_content', array($this, 'autoPrependPlayer'));
    }

    /**
     * Register shortcodes.
     *
     * @since 4.2.0
     */
    public function registerShortcodes()
    {
        add_shortcode('beyondwords_player', array($this, 'playerShortcode'));
    }

    /**
     * HTML output for the BeyondWords player shortcode.
     *
     * @since 4.2.0
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function playerShortcode()
    {
        return $this->playerHtml();
    }

    /**
     * Auto-prepends the BeyondWords player to WordPress content.
     *
     * @since 3.0.0
     * @since 4.2.0 Renamed from addPlayerToContent to autoPrependPlayer.
     * @since 4.2.0 Perform hasCustomPlayer() check here.
     * @since 4.6.1 Only auto-prepend player for frontend is_singular screens.
     *
     * @param string $content WordPress content.
     *
     * @return string
     */
    public function autoPrependPlayer($content)
    {
        if (! is_singular()) {
            return $content;
        }

        if ($this->hasCustomPlayer($content)) {
            return $content;
        }

        return $this->playerHtml() . $content;
    }

    /**
     * Player HTML.
     *
     * Displays JS SDK variant of the BeyondWords audio player, for both
     * AMP and non-AMP content.
     *
     * @param WP_Post $post WordPress Post.
     *
     * @since 3.0.0
     * @since 3.1.0 Added _doing_it_wrong deprecation warnings
     *
     * @return string
     */
    public function playerHtml($post = false)
    {
        if (! ($post instanceof \WP_Post)) {
            $post = get_post($post);
        }

        if (! $post) {
            return '';
        }

        if (! $this->isPlayerEnabled($post)) {
            return '';
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);

        if (! $projectId) {
            return '';
        }

        $contentId = PostMetaUtils::getContentId($post->ID);

        if (! $contentId) {
            return '';
        }

        // AMP or JS Player?
        if ($this->useAmpPlayer()) {
            $html = $this->ampPlayerHtml($post->ID, $projectId, $contentId);
        } else {
            $html = $this->jsPlayerHtml($post->ID, $projectId, $contentId);
        }

        /**
         * Filters the HTML of the BeyondWords Player.
         *
         * @since 4.0.0
         * @since 4.3.0 Applied to both AMP and no-AMP content.
         *
         * @param string $html      The HTML for the JS audio player. The audio player JavaScript may
         *                          fail to locate the target element if you remove or replace the
         *                          default contents of this parameter.
         * @param int    $postId    WordPress post ID.
         * @param int    $projectId BeyondWords project ID.
         * @param int    $contentId BeyondWords content ID.
         */
        $html = apply_filters('beyondwords_player_html', $html, $post->ID, $projectId, $contentId);

        return $html;
    }

    /**
     * Has custom player?
     *
     * Checks the post content to see whether a custom player has been added.
     *
     * @since 3.2.0
     * @since 4.2.0 Pass $content as a parameter, check for [beyondwords_player] shortcode
     * @since 4.2.4 Check $content is a string
     *
     * @param string $content WordPress content.
     *
     * @return boolean
     */
    public function hasCustomPlayer($content)
    {
        if (! is_string($content)) {
            return false;
        }

        if (strpos($content, '[beyondwords_player]') !== false) {
            return true;
        }

        $crawler = new Crawler($content);

        return count($crawler->filterXPath('//div[@data-beyondwords-player="true"]')) > 0;
    }

    /**
     * JS Player HTML.
     *
     * Displays the HTML required for the JS player.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param int $postId    WordPress Post ID.
     * @param int $projectId BeyondWords Project ID.
     * @param int $contentId BeyondWords Content ID.
     *
     * @since 3.0.0
     * @since 3.1.0 Added speechkit_js_player_html filter
     * @since 4.2.0 Remove hasCustomPlayer() check from here.
     * @since 5.2.0 Replace div[data-beyondwords-player] with script[onload]
     *
     * @return string
     */
    public function jsPlayerHtml($postId, $projectId, $contentId)
    {
        if (! $this->usePlayerJsSdk()) {
            return '';
        }

        $post   = get_post($postId);
        $params = $this->jsPlayerParams($post);

        $playerUI = get_option('beyondwords_player_ui', PlayerUI::ENABLED);

        $params['projectId'] = $projectId;
        $params['contentId'] = $contentId;

        $json = wp_json_encode($params, JSON_UNESCAPED_SLASHES);

        // Headless instantiates a player without a target
        if ($playerUI !== PlayerUI::HEADLESS) {
            $json = sprintf('{...%s, target:this}', $json);
        }

        $onload = sprintf('new BeyondWords.Player(%s);', $json);

        /**
         * Filters the onload attribute of the BeyondWords Player script.
         *
         * Note that the strings should be in double quotes, because the output
         * of this is run through esc_js() before it is output into the DOM.
         *
         * @link https://developer.wordpress.org/reference/functions/esc_js/
         *
         * Also note that to support multiple players on one page, the
         * default script uses `document.querySelectorAll() to target all
         * instances of `div[data-beyondwords-player]` in the HTML source.
         * If this approach is removed then multiple occurrences of the
         * BeyondWords player in one page may not work as expected.
         *
         * @link https://github.com/beyondwords-io/player/blob/main/doc/getting-started.md#how-to-configure-it
         *
         * @since 4.0.0
         *
         * @param string $script The string value of the onload script.
         * @param array  $params The SDK params for the current post, including
         *                       `projectId` and `contentId`.
         */
        $onload = apply_filters('beyondwords_player_script_onload', $onload, $params);

        $html = sprintf(
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
            '<script async defer src="%s" onload=\'%s\'></script>',
            Environment::getJsSdkUrl(),
            $onload
        );

        return $html;
    }

    /**
     * AMP Player HTML.
     *
     * Displays the HTML required for the AMP player.
     *
     * @param int $postId    WordPress Post ID.
     * @param int $projectId BeyondWords Project ID.
     * @param int $contentId BeyondWords Content ID.
     *
     * @since 3.0.0
     * @since 3.1.0 Added speechkit_amp_player_html filter
     *
     * @return string
     */
    public function ampPlayerHtml($postId, $projectId, $contentId)
    {
        $src = sprintf(Environment::getAmpPlayerUrl(), $projectId, $contentId);

        // Turn on output buffering
        ob_start();

        ?>
        <amp-iframe
            frameborder="0"
            height="43"
            layout="responsive"
            sandbox="allow-scripts allow-same-origin allow-popups"
            scrolling="no"
            src="<?php echo esc_url($src); ?>"
            width="295"
        >
            <amp-img
                height="150"
                layout="responsive"
                placeholder
                src="<?php echo esc_url(Environment::getAmpImgUrl()); ?>"
                width="643"
            ></amp-img>
        </amp-iframe>
        <?php

        $html = ob_get_clean();

        /**
         * Filters the HTML of the BeyondWords AMP audio player.
         *
         * This filter is scheduled to be removed in v5.0.
         *
         * @since 3.3.3
         *
         * @deprecated 4.3.0 beyondwords_player_html is now applied to AMP and non-AMP content.
         * @see Beyondwords\Wordpress\Core\Player\Player::playerHtml()
         *
         * @param string $html       The HTML for the AMP audio player.
         * @param int    $post_id    WordPress Post ID.
         * @param int    $project_id BeyondWords Project ID.
         * @param int    $contentId  BeyondWords Content ID.
         */
        $html = apply_filters('beyondwords_amp_player_html', $html, $postId, $projectId, $contentId);

        return $html;
    }

    /**
     * Should we show the BeyondWords audio player?
     *
     * We DO NOT want to show the player if:
     * 1. BeyondWords has been disabled in our plugin settings.
     * 2. The current post type has not been selected in our plugin settings.
     * 3. The current post has specifically been disabled from processing.
     *
     * The return value of this can be overriden with the WordPress
     * "beyondwords_post_player_enabled" filter.
     *
     * @param int|WP_Post (Optional) Post ID or WP_Post object. Default is global $post.
     *
     * @since 3.0.0
     * @since 3.3.4 Accept int|WP_Post as method parameter.
     * @since 4.0.0 Check beyondwords_player_ui custom field.
     * @since 5.0.0 Remove beyondwords_post_player_enabled filter.
     *
     * @return bool
     **/
    public function isPlayerEnabled($post = null)
    {
        $post = get_post($post);

        if (! ($post instanceof \WP_Post)) {
            return false;
        }

        // Assume we can show the player
        $enabled = true;

        // Has 'Display Player' been unchecked?
        if (PostMetaUtils::getDisabled($post->ID)) {
            $enabled = false;
        }

        // Is the player ui enabled in plugin settings?
        if ($enabled) {
            $enabled = get_option('beyondwords_player_ui', PlayerUI::ENABLED) === PlayerUI::ENABLED;
        }

        return $enabled;
    }

    /**
     * Use the AMP player?
     *
     * There are multiple AMP plugins for WordPress, so multiple checks are performed.
     *
     * @since 3.0.7
     *
     * @return bool
     */
    public function useAmpPlayer()
    {
        // https://amp-wp.org/reference/function/amp_is_request/
        if (function_exists('amp_is_request')) {
            return \amp_is_request();
        }

        // https://ampforwp.com/tutorials/article/detect-amp-page-function/
        if (function_exists('ampforwp_is_amp_endpoint')) {
            return \ampforwp_is_amp_endpoint();
        }

        // https://amp-wp.org/reference/function/is_amp_endpoint/
        if (function_exists('is_amp_endpoint')) {
            return \is_amp_endpoint();
        }

        return false;
    }

    /**
     * Use Player JS SDK?
     *
     * @since 3.0.7
     *
     * @return string
     */
    public function usePlayerJsSdk()
    {
        // AMP requests don't use the Player JS SDK
        if ($this->useAmpPlayer()) {
            return false;
        }

        // Both Gutenberg/Classic editors have their own player scripts
        if (CoreUtils::isGutenbergPage() || CoreUtils::isEditScreen()) {
            return false;
        }

        // Disable audio player in Preview, because we have not sent updates to BeyondWords API yet
        if (function_exists('is_preview') && is_preview()) {
            return false;
        }

        $post = get_post();

        if (! $post) {
            return false;
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);
        $contentId = PostMetaUtils::getContentId($post->ID);

        if ($projectId && $contentId) {
            return true;
        }

        return false;
    }

    /**
     * JavaScript SDK parameters.
     *
     * @since 3.1.0
     * @since 4.0.0 Use new JS SDK params format.
     * @since 5.3.0 Support loadContentAs param and return an object.
     *
     * @param WP_Post $post WordPress Post.
     *
     * @return object
     */
    public function jsPlayerParams($post)
    {
        if (!($post instanceof \WP_Post)) {
            return [];
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);
        $contentId = PostMetaUtils::getContentId($post->ID);

        $params = [
            'projectId' => is_numeric($projectId) ? (int)$projectId : $projectId,
            'contentId' => is_numeric($contentId) ? (int)$contentId : $contentId,
        ];

        // Player UI
        $playerUI = get_option('beyondwords_player_ui', PlayerUI::ENABLED);
        if ($playerUI === PlayerUI::HEADLESS) {
            $params['showUserInterface'] = false;
        }

        // Player Style
        // @todo overwrite global styles with post settings
        $playerStyle = PostMetaUtils::getPlayerStyle($post->ID);
        if (!empty($playerStyle)) {
            $params['playerStyle'] = $playerStyle;
        }

        // Player content
        $playerContent = get_post_meta($post->ID, 'beyondwords_player_content', true);
        if (!empty($playerContent)) {
            $params['loadContentAs'] = [ $playerContent ];
        }

        // SDK params from plugin settings
        $params = $this->addPluginSettingsToSdkParams($params);

        /**
         * Filters the BeyondWords JavaScript SDK parameters.
         *
         * @since 4.0.0
         *
         * @param array $params The default JS SDK params.
         * @param int   $postId The Post ID.
         */
        $params = apply_filters('beyondwords_player_sdk_params', $params, $post->ID);

        // Cast assoc array to object
        return (object)$params;
    }

    /**
     * Add plugin settings to SDK params.
     *
     * @since 5.0.0
     *
     * @param array $params BeyondWords Player SDK params.
     *
     * @return array Modified SDK params.
     */
    public function addPluginSettingsToSdkParams($params)
    {
        $mapping = [
            'beyondwords_player_style'              => 'playerStyle',
            'beyondwords_player_call_to_action'     => 'callToAction',
            'beyondwords_player_highlight_sections' => 'highlightSections',
            'beyondwords_player_widget_style'       => 'widgetStyle',
            'beyondwords_player_widget_position'    => 'widgetPosition',
            'beyondwords_player_skip_button_style'  => 'skipButtonStyle',
        ];

        foreach ($mapping as $wpOption => $sdkParam) {
            $val = get_option($wpOption);
            if (!empty($val)) {
                $params[$sdkParam] = $val;
            }
        }

        // Special case for clickableSections
        $val = get_option('beyondwords_player_clickable_sections');
        if (!empty($val)) {
            $params['clickableSections'] = 'body';
        }

        return $params;
    }
}
