<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\CoreUtils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The "Legacy" BeyondWords Player.
 *
 * @deprecated Scheduled for removal in v5.0
 **/
class LegacyPlayer
{
    /**
     * Init.
     */
    public function init()
    {
        // Actions
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));

        // Filters
        add_filter('the_content', array($this, 'addPlayerToContent'));
        add_filter('newsstand_the_content', array($this, 'addPlayerToContent'));
    }

    /**
     * Adds the BeyondWords player to WordPress content.
     *
     * @since 3.0.0
     *
     * @param string $content WordPress content.
     *
     * @return string
     */
    public function addPlayerToContent($content)
    {
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

        $podcastId = PostMetaUtils::getContentId($post->ID);

        if (! $podcastId) {
            return '';
        }

        // AMP or JS Player?
        if ($this->useAmpPlayer()) {
            $html = $this->ampPlayerHtml($post->ID, $projectId, $podcastId);
        } else {
            $html = $this->jsPlayerHtml($post->ID, $projectId, $podcastId);
        }

        return $html;
    }

    /**
     * Has custom player?
     *
     * Checks the post content to see whether a custom player has been added.
     *
     * @param int $postId WordPress Post ID.
     *
     * @since 3.2.0
     *
     * @return boolean
     */
    public function hasCustomPlayer($postId)
    {
        $content = get_the_content(null, false, $postId);

        $crawler = new Crawler($content);

        return count($crawler->filterXPath('//div[@data-beyondwords-player="true"]')) > 0;
    }

    /**
     * JS Player HTML.
     *
     * Displays the HTML required for the JS player.
     *
     * @param int $postId    WordPress Post ID.
     * @param int $projectId BeyondWords Project ID.
     * @param int $podcastId BeyondWords Podcast ID.
     *
     * @since 3.0.0
     * @since 3.1.0 Added speechkit_js_player_html filter
     *
     * @return string
     */
    public function jsPlayerHtml($postId, $projectId, $podcastId)
    {
        $html = '';

        if (! $this->hasCustomPlayer($postId)) {
            $html .= '<div data-beyondwords-player="true" contenteditable="false"></div>';
        }

        /**
         * Filters the HTML of the BeyondWords Player.
         *
         * @since 4.0.0
         *
         * @param string $html       The HTML for the JS audio player. The audio player JavaScript may
         *                           fail to locate the target element if you remove or replace the
         *                           default contents of this parameter.
         * @param int    $post_id    WordPress post ID.
         * @param int    $project_id BeyondWords project ID.
         * @param int    $podcast_id BeyondWords podcast ID.
         */
        $html = apply_filters('beyondwords_player_html', $html, $postId, $projectId, $podcastId);

        /**
         * Filters the HTML of the BeyondWords JS audio player.
         *
         * @since 3.3.3
         * @deprecated Scheduled for removal in v5.0
         *
         * @param string $html       The HTML for the JS audio player. The audio player JavaScript may
         *                           fail to locate the target element if you remove or replace the
         *                           default contents of this parameter.
         * @param int    $post_id    WordPress post ID.
         * @param int    $project_id BeyondWords project ID.
         * @param int    $podcast_id BeyondWords podcast ID.
         */
        $html = apply_filters('beyondwords_js_player_html', $html, $postId, $projectId, $podcastId);

        return $html;
    }

    /**
     * AMP Player HTML.
     *
     * Displays the HTML required for the AMP player.
     *
     * @param int $postId    WordPress Post ID.
     * @param int $projectId BeyondWords Project ID.
     * @param int $podcastId BeyondWords Podcast ID.
     *
     * @since 3.0.0
     * @since 3.1.0 Added speechkit_amp_player_html filter
     *
     * @return string
     */
    public function ampPlayerHtml($postId, $projectId, $podcastId)
    {
        $src = sprintf(Environment::getAmpPlayerUrl(), $projectId, $podcastId);

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
         * @since 3.3.3
         *
         * @param string $html       The HTML for the AMP audio player.
         * @param int    $post_id    WordPress Post ID.
         * @param int    $project_id BeyondWords Project ID.
         * @param int    $podcast_id BeyondWords Podcast ID.
         */
        $html = apply_filters('beyondwords_amp_player_html', $html, $postId, $projectId, $podcastId);

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

        /**
         * Filters the enabled/disabled (shown/hidden) status of the player for each post.
         *
         * @since 3.3.3
         *
         * @param boolean $enabled   Is the player enabled (shown) for this post?
         * @param int     $post_id    WordPress post ID.
         */
        $enabled = apply_filters('beyondwords_post_player_enabled', $enabled, $post->ID);

        return $enabled;
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function enqueueScripts()
    {
        if (! is_singular() && ! is_admin()) {
            return;
        }

        // JS SDK Player script, filtered by $this->scriptLoaderTag()
        add_filter('script_loader_tag', array($this, 'scriptLoaderTag'), 10, 3);

        wp_enqueue_script(
            'beyondwords-sdk',
            Environment::getJsSdkUrlLegacy(),
            array(),
            null,
            true
        );
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
     * Filters the HTML script tag of an enqueued script.
     *
     * @param string $tag    The <script> tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     *
     * @since 3.0.0
     *
     * @see https://developer.wordpress.org/reference/hooks/script_loader_tag/
     * @see https://stackoverflow.com/a/59594789
     *
     * @return string
     */
    public function scriptLoaderTag($tag, $handle, $src)
    {
        if ($handle === 'beyondwords-sdk') :
            if (! $this->usePlayerJsSdk()) {
                return '';
            }

            $post = get_post();

            $params = $this->jsPlayerParams($post);

            ob_start();
            ?>
            <script id="beyondwords-sdk" type="module">
                //<![CDATA[
                import BeyondWords from '<?php echo esc_url($src); ?>';
                import { v4 as uuidv4 } from 'https://jspm.dev/uuid';

                const PLAYER_SELECTOR = 'div[data-beyondwords-player]:not([data-beyondwords-init])';

                const players = Array.from(
                    document.querySelectorAll(PLAYER_SELECTOR)
                );

                await Promise.all(players.map((item, index) => {
                    const playerId = `beyondwords-player-${uuidv4()}`;

                    item.setAttribute('id', playerId);

                    return BeyondWords.player({
                        ...<?php echo wp_json_encode($params, JSON_FORCE_OBJECT); ?>,
                        "renderNode": playerId,
                    }).then((player) => {
                        item.setAttribute('data-beyondwords-init', 'true');
                        console.log(`🔊 #${playerId} is initialized`);
                    }).catch(err => {
                        console.error(err);
                    });
                }));
                //]]>
            </script>
            <?php
            return ob_get_clean();
        endif;

        return $tag;
    }

    /**
     * JavaScript SDK parameters.
     *
     * Note that the default return value for this method is an associative array, but
     * the HTML output will be forced to an object due to `wp_json_encode($params, JSON_FORCE_OBJECT)`
     * in `Player::scriptLoaderTag()`.
     *
     * @since 3.1.0
     *
     * @see https://docs.beyondwords.io/docs/javascript-sdk-automatic-player
     *
     * @param WP_Post $post WordPress Post.
     *
     * @return array
     */
    public function jsPlayerParams($post)
    {
        if (!($post instanceof \WP_Post)) {
            return [];
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);
        $podcastId = PostMetaUtils::getContentId($post->ID);

        $skBackend = Environment::getBackendUrl();
        $skBackendApi = Environment::getApiUrl();

        $params = [
            'projectId' => (int)$projectId,
            'podcastId' => $podcastId,
        ];

        if (get_option('beyondwords_player_size') === 'medium') {
            $params['playerType'] = 'manual';
        }

        if (strlen($skBackend)) {
            $params['skBackend'] = esc_url($skBackend);
        }

        if (is_admin()) {
            $params['processingStatus'] = true;
            $params['apiWriteKey'] = get_option('beyondwords_api_key', '');

            if (strlen($skBackendApi)) {
                $params['skBackendApi'] = esc_url($skBackendApi);
            }
        }

        /**
         * Filters the BeyondWords JavaScript SDK parameters.
         *
         * @since 3.3.3
         *
         * @param array The default JS SDK params.
         */
        $params = apply_filters('beyondwords_js_player_params', $params);

        return $params;
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

        // Gutenberg has a dedicated React component for the player
        if (CoreUtils::isGutenbergPage()) {
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
        if (! $projectId) {
            return false;
        }

        $podcastId = PostMetaUtils::getContentId($post->ID);
        if (! $podcastId) {
            return false;
        }

        return true;
    }
}