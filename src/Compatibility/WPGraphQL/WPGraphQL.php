<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Compatibility\WPGraphQL;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use WPGraphQL as WPGraphQLPlugin;

/**
 * Expose BeyondWords fields in WPGraphQL.
 *
 * @since 3.6.0
 * @since 4.7.0 Moved graphqlRegisterTypes() from Beyondwords\Wordpress\Core to here.
 */
defined('ABSPATH') || exit;

class WPGraphQL
{
    /**
     * Init.
     *
     * @since 3.6.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        // Actions for WPGraphQL
        add_action('graphql_register_types', [self::class, 'graphqlRegisterTypes']);
    }

    /**
     * GraphQL: Register types.
     *
     * @since 3.6.0
     * @since 4.0.0 Register contentId field, and contentId/podcastId are now String, not Int
     * @since 4.7.0 Moved graphqlRegisterTypes() from Beyondwords\Wordpress\Core to here.
     * @since 6.0.0 Make static, add sourceId field.
     */
    public static function graphqlRegisterTypes()
    {
        register_graphql_object_type('Beyondwords', [
            'description' => __('BeyondWords audio details. Use this data to embed an audio player using the BeyondWords JavaScript SDK.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
            'fields' => [
                'sourceId' => [
                    'description' => __('BeyondWords source ID', 'speechkit'),
                    'type' => 'String'
                ],
                'projectId' => [
                    'description' => __('BeyondWords project ID', 'speechkit'),
                    'type' => 'Int'
                ],
                'contentId' => [
                    'description' => __('BeyondWords content ID', 'speechkit'),
                    'type' => 'String'
                ],
                'podcastId' => [
                    'description' => __('BeyondWords legacy podcast ID', 'speechkit'),
                    'type' => 'String'
                ],
            ],
        ]);

        $beyondwordsPostTypes = SettingsUtils::getCompatiblePostTypes();

        $graphqlPostTypes = WPGraphQLPlugin::get_allowed_post_types();

        $postTypes = array_intersect($beyondwordsPostTypes, $graphqlPostTypes);

        if (! empty($postTypes) && is_array($postTypes)) {
            foreach ($postTypes as $postType) {
                $postTypeObject = get_post_type_object($postType);

                register_graphql_field($postTypeObject->graphql_single_name, 'beyondwords', [
                    'type'        => 'Beyondwords',
                    'description' => __('BeyondWords audio details', 'speechkit'),
                    'resolve'     => function (WPGraphQLPlugin\Model\Post $post) {
                        $fields = [
                            'sourceId' => (string) $post->ID,
                        ];

                        $projectId = PostMetaUtils::getProjectId($post->ID);

                        if (! empty($projectId)) {
                            $fields['projectId'] = $projectId;
                        }

                        $contentId = PostMetaUtils::getContentId($post->ID);

                        if (! empty($contentId)) {
                            $fields['contentId'] = $contentId;
                            $fields['podcastId'] = $contentId; // legacy
                        }

                        return $fields;
                    }
                ]);
            }
        }
    }
}
