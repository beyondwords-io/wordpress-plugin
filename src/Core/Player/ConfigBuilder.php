<?php

namespace Beyondwords\Wordpress\Core\Player;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;

/**
 * Class ConfigBuilder
 *
 * Constructs the parameters object for the BeyondWords JS SDK.
 */
class ConfigBuilder
{
    /**
     * Build JavaScript SDK parameters for the player.
     *
     * @param \WP_Post $post WordPress post object.
     *
     * @return object Parameters for JS SDK.
     */
    public static function build(\WP_Post $post): object
    {
        $projectId = PostMetaUtils::getProjectId($post->ID);
        $contentId = PostMetaUtils::getContentId($post->ID);

        $params = [
            'projectId' => is_numeric($projectId) ? (int)$projectId : $projectId,
            // @todo always use sourceId for JS Player?
            'contentId' => is_numeric($contentId) ? (int)$contentId : $contentId,
        ];

        $params = self::mergePluginSettings($params);
        $params = self::mergePostSettings($post, $params);

        return (object) apply_filters('beyondwords_player_sdk_params', $params, $post->ID);
    }

    /**
     * Merge global plugin settings into the SDK parameters.
     *
     * @param array $params Existing params.
     *
     * @return array Modified params.
     */
    public static function mergePluginSettings(array $params): array
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

            if (! empty($val)) {
                $params[$sdkParam] = $val;
            }
        }

        if (! empty(get_option('beyondwords_player_clickable_sections'))) {
            $params['clickableSections'] = 'body';
        }

        return $params;
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
        $playerUI = get_option('beyondwords_player_ui', PlayerUI::ENABLED);

        if ($playerUI === PlayerUI::HEADLESS) {
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

        $method = IntegrationMethod::getIntegrationMethod($post);

        if ($method === IntegrationMethod::CLIENT_SIDE) {
            $params['clientSideEnabled'] = true;
            // @todo always use sourceId for JS Player?
            $params['sourceId'] = (string) $post->ID;
            unset($params['contentId']);
        }

        return $params;
    }
}
