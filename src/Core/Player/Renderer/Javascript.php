<?php

namespace Beyondwords\Wordpress\Core\Player\Renderer;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\CoreUtils;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\ConfigBuilder;

/**
 * Class Javascript.
 *
 * Responsible for rendering the JavaScript BeyondWords player.
 */
class Javascript
{
    /**
     * Check whether we should use the JavaScript player for the current post.
     *
     * @param \WP_Post $post WordPress post object.
     *
     * @return bool True if JavaScript player should be used.
     */
    public static function check(\WP_Post $post): bool
    {
        if (function_exists('is_preview') && is_preview()) {
            return false;
        }

        if (CoreUtils::isGutenbergPage() || CoreUtils::isEditScreen()) {
            return false;
        }

        $projectId = PostMetaUtils::getProjectId($post->ID);

        if (! $projectId) {
            return false;
        }

        $contentId = PostMetaUtils::getContentId($post->ID);
        $method = IntegrationMethod::getIntegrationMethod($post);

        return $method === IntegrationMethod::CLIENT_SIDE ||
               ($method === IntegrationMethod::REST_API && $contentId);
    }

    /**
     * Render the JavaScript player HTML.
     *
     * @param \WP_Post $post
     * @return string HTML output.
     */
    public static function render($post): string
    {
        if (PlayerUI::DISABLED === get_option(PlayerUI::OPTION_NAME)) {
            return '';
        }

        $params = ConfigBuilder::build($post);

        $jsonParams = wp_json_encode($params, JSON_UNESCAPED_SLASHES);
        $jsonParams = sprintf('{target:this, ...%s}', $jsonParams);

        $onload = sprintf('new BeyondWords.Player(%s);', $jsonParams);
        $onload = apply_filters('beyondwords_player_script_onload', $onload, $params);

        return sprintf(
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
            '<script async defer src="%s" onload=\'%s\'></script>',
            Environment::getJsSdkUrl(),
            $onload
        );
    }
}
