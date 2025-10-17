<?php

namespace Beyondwords\Wordpress\Core\Player\Renderer;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Core\CoreUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * Class Amp
 *
 * Renders the AMP-compatible BeyondWords player.
 */
class Amp extends Base
{
    /**
     * Check whether we should use the AMP player for the current post.
     *
     * @param \WP_Post $post WordPress post object.
     *
     * @return bool True if AMP player should be used.
     */
    public static function check(\WP_Post $post): bool
    {
        if (! CoreUtils::isAmp()) {
            return false;
        }

        return parent::check($post);
    }

    /**
     * Render AMP player HTML.
     *
     *
     * @return string HTML markup for AMP player.
     */
    public static function render(\WP_Post $post): string
    {
        $projectId = PostMetaUtils::getProjectId($post->ID);
        $contentId = PostMetaUtils::getContentId($post->ID, true); // Fallback to Post ID if Content ID is not set

        $src = sprintf(Environment::getAmpPlayerUrl(), $projectId, $contentId);

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
        return ob_get_clean();
    }
}