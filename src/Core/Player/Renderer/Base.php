<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player\Renderer;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * Class Base.
 *
 * Base class for player renderers.
 */
defined('ABSPATH') || exit;

class Base
{
    /**
     * Check whether a player should be rendered.
     *
     * @param \WP_Post $post WordPress post object.
     *
     * @return bool True if a player should be rendered.
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
}
