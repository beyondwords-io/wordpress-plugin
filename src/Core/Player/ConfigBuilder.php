<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use BeyondWords\Settings\Fields as SettingsFields;
use Beyondwords\Wordpress\Core\Core;

/**
 * Class ConfigBuilder
 *
 * Constructs the parameters object for the BeyondWords JS SDK.
 */
defined('ABSPATH') || exit;

class ConfigBuilder
{
    /**
     * Build JavaScript SDK parameters for the player.
     *
     * @since 6.0.0 Introduced.
     *
     * @param \WP_Post $post WordPress post object.
     *
     * @return object Parameters for JS SDK.
     */
    public static function build(\WP_Post $post): object
    {
        $projectId = PostMetaUtils::getProjectId($post->ID);

        $params = [
            'projectId' => is_numeric($projectId) ? (int) $projectId : $projectId,
        ];

        $params = self::mergePostSettings($post, $params);

        return (object) apply_filters('beyondwords_player_sdk_params', $params, $post->ID);
    }

    /**
     * Merge post-specific settings into the SDK parameters.
     *
     * @param \WP_Post $post   WordPress post object.
     * @param array    $params Existing params.
     *
     * @return array Modified params.
     */
    public static function mergePostSettings(\WP_Post $post, array $params): array
    {
        $contentId = PostMetaUtils::getContentId($post->ID);

        if (! empty($contentId)) {
            $params['contentId'] = (string) $contentId;
        }

        $playerUI = get_option(SettingsFields::OPTION_PLAYER_UI);

        if ($playerUI === SettingsFields::PLAYER_UI_HEADLESS) {
            $params['showUserInterface'] = false;
        }

        $style = PostMetaUtils::getPlayerStyle($post->ID);

        if (! empty($style)) {
            $params['playerStyle'] = $style;
        }

        $content = get_post_meta($post->ID, 'beyondwords_player_content', true);

        if (! empty($content)) {
            $params['loadContentAs'] = [$content];
        }

        $method = SettingsFields::get_integration_method($post);

        if ($method === SettingsFields::INTEGRATION_CLIENT_SIDE && empty($params['contentId'])) {
            $params['clientSideEnabled'] = true;
            $params['sourceId'] = (string) $post->ID;
            unset($params['contentId']);
        }

        return $params;
    }
}
