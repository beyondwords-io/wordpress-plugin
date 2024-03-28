<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Player;

/**
 * The "Legacy" BeyondWords Player.
 *
 * @deprecated 4.3.0 Scheduled for removal in plugin version 5.0. Use the "Latest" player instead.
 * @link https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/updating-to-the-latest-player
 **/
class LegacyPlayer extends Player
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Actions
        add_action('init', array($this, 'registerShortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));

        // Filters
        add_filter('the_content', array($this, 'autoPrependPlayer'), 1000000);
        add_filter('newsstand_the_content', array($this, 'autoPrependPlayer'));
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
        if (! is_singular()) {
            return;
        }

        // JS SDK Player script, filtered by $this->scriptLoaderTag()
        add_filter('script_loader_tag', array($this, 'scriptLoaderTag'), 10, 3);

        wp_enqueue_script(
            'beyondwords-sdk',
            Environment::getJsSdkUrlLegacy(),
            array(),
            BEYONDWORDS__PLUGIN_VERSION,
            true
        );
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
}