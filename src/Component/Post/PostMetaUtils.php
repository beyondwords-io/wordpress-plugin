<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Component\Post;

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * BeyondWords Post Meta (Custom Field) Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
class PostMetaUtils
{
    public const WP_ERROR_FORMAT = 'WP_Error [%s] %s';

    /**
     * Get "renamed" Post Meta.
     *
     * We previously saved custom fields with a prefix of `speechkit_*` and we now
     * save them with a prefix of `beyondwords_*`.
     *
     * This method checks both prefixes, copying `speechkit_*` data to `beyondwords_*`.
     *
     * @since 3.7.0
     *
     * @param int    $postId Post ID.
     * @param string $name   Custom field name, without the prefix.
     *
     * @return string
     */
    public static function getRenamedPostMeta($postId, $name)
    {
        if (metadata_exists('post', $postId, 'beyondwords_' . $name)) {
            return get_post_meta($postId, 'beyondwords_' . $name, true);
        }

        if (metadata_exists('post', $postId, 'speechkit_' . $name)) {
            $value = get_post_meta($postId, 'speechkit_' . $name, true);

            // Migrate over to newer `beyondwords_*` format
            update_post_meta($postId, 'beyondwords_' . $name, $value);

            return $value;
        }

        return '';
    }

    /**
     * Get the BeyondWords metadata for a Post.
     *
     * @since 4.1.0 Append 'beyondwords_version' and 'wordpress_version'.
     * @since 5.2.3 Introduce $type parameter to customize the metadata returned.
     */
    public static function getMetadata($postId, $type = 'current')
    {
        global $wp_version;

        $keys = CoreUtils::getPostMetaKeys($type);

        // Get all meta in a single query for performance
        $metadata = has_meta($postId);

        // Filter out non-BeyondWords meta
        $metadata = array_filter($metadata, function ($item) use ($keys) {
            return in_array($item['meta_key'], $keys);
        });

        // Create empty values for missing meta
        foreach ($keys as $key) {
            $hasMeta = array_search($key, array_column($metadata, 'meta_key'));

            if (! $hasMeta) {
                // phpcs:disable WordPress.DB.SlowDBQuery
                array_push(
                    $metadata,
                    [
                        'meta_id'    => null,
                        'meta_key'   => $key,
                        'meta_value' => '',
                    ]
                );
                // phpcs:enable WordPress.DB.SlowDBQuery
            }
        }

        // Optionally prepend useful non-meta values
        if ($type === 'all') {
            // phpcs:disable WordPress.DB.SlowDBQuery
            array_push(
                $metadata,
                [
                    'meta_id'    => null,
                    'meta_key'   => 'beyondwords_version',
                    'meta_value' => BEYONDWORDS__PLUGIN_VERSION,
                    'readonly'   => true,
                ],
                [
                    'meta_id'    => null,
                    'meta_key'   => 'wordpress_version',
                    'meta_value' => $wp_version,
                    'readonly'   => true,
                ],
                [
                    'meta_id'    => null,
                    'meta_key'   => 'wordpress_post_id',
                    'meta_value' => $postId,
                    'readonly'   => true,
                ],
            );
            // phpcs:enable WordPress.DB.SlowDBQuery
        }

        return $metadata;
    }

    /**
     * Remove the BeyondWords metadata for a Post.
     *
     * @since 3.9.0 Introduced.
     * @since 5.2.3 Use CoreUtils::getPostMetaKeys('all') to get all meta keys.
     */
    public static function removeAllBeyondwordsMetadata($postId)
    {
        $keys = CoreUtils::getPostMetaKeys('all');

        foreach ($keys as $key) {
            delete_post_meta($postId, $key, null);
        }

        return true;
    }

    /**
     * Get the Content ID for a WordPress Post.
     *
     * Over time there have been various approaches to storing the Content ID.
     * This function tries each approach in reverse-date order.
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     * @since 4.0.0 Renamed to getContentId() & prioritise beyondwords_content_id
     * @since 5.0.0 Remove beyondwords_content_id filter.
     *
     * @param int $postId Post ID.
     *
     * @return int|false Content ID, or false
     */
    public static function getContentId($postId)
    {
        // Check for "Content ID" custom field (string, uuid)
        $contentId = get_post_meta($postId, 'beyondwords_content_id', true);

        if (! $contentId) {
            // Also try "Podcast ID" custom field (number, or string for > 4.x)
            $contentId = PostMetaUtils::getPodcastId($postId);
        }

        return $contentId;
    }

    /**
     * Get the (legacy) Podcast ID for a WordPress Post.
     *
     * Over time there have been various approaches to storing the Podcast ID.
     * This function tries each approach in reverse-date order.
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     * @since 4.0.0 Allow string values for UUIDs stored >= v4.x
     *
     * @param int $postId Post ID.
     *
     * @return int|false Podcast ID, or false
     */
    public static function getPodcastId($postId)
    {
        // Check for "Podcast ID" custom field (number, or string for > 4.x)
        $podcastId = PostMetaUtils::getRenamedPostMeta($postId, 'podcast_id');

        if ($podcastId) {
            return $podcastId;
        }

        // It may also be found by parsing post_meta._speechkit_link
        $speechkit_link = get_post_meta($postId, '_speechkit_link', true);
        // Player URL can be either /a/[ID] or /e/[ID] or /m/[ID]
        preg_match('/\/[aem]\/(\d+)/', (string)$speechkit_link, $matches);
        if ($matches) {
            return intval($matches[1]);
        }

        // It may also be found by parsing post_meta.speechkit_response
        $speechkit_response = static::getHttpResponseBodyFromPostMeta($postId, 'speechkit_response');
        preg_match('/"podcast_id":(")?(\d+)(?(1)\1|)/', (string)$speechkit_response, $matches);
        // $matches[2] is the Podcast ID (response.podcast_id)
        if ($matches && $matches[2]) {
            return intval($matches[2]);
        }

        /**
         * It may also be found by parsing post_meta.speechkit_info
         *
         * NOTE: This has been copied verbatim from the existing iframe player check
         *       at Speechkit_Public::iframe_player_embed_html(), in case it is
         *       needed for posts which were created a very long time ago.
         *       I cannot write unit tests for this to pass, they always fail for me,
         *       so there are currently no tests for it.
         **/
        $article = get_post_meta($postId, 'speechkit_info', true);
        if (empty($article) || ! isset($article['share_url'])) {
            // This is exactly the same if/else statement that we have at
            // Speechkit_Public::iframe_player_embed_html(), but there is
            // nothing for us to to do here.
        } else {
            // This is the part that we need...
            $url = $article['share_url'];

            // Player URL can be either /a/[ID] or /e/[ID] or /m/[ID]
            preg_match('/\/[aem]\/(\d+)/', (string)$url, $matches);
            if ($matches) {
                return intval($matches[1]);
            }
        }

        // todo throw ContentIdNotFoundException???

        return false;
    }

    /**
     * Get the BeyondWords preview token for a WordPress Post.
     *
     * The preview token allows us to play audio that has a future scheduled
     * publish date, so we can preview the audio in WordPress admin before it
     * is published.
     *
     * The token is supplied by the BeyondWords REST API whenever audio content
     * is created/updated, and stored in a WordPress custom field.
     *
     * @since 4.5.0
     *
     * @param int $postId Post ID.
     *
     * @return string Preview token
     */
    public static function getPreviewToken($postId)
    {
        $previewToken = get_post_meta($postId, 'beyondwords_preview_token', true);

        return $previewToken;
    }

    /**
     * Get the 'Generate Audio' value for a Post.
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     *
     * @param int $postId Post ID.
     *
     * @return bool
     */
    public static function hasGenerateAudio($postId)
    {
        if (PostMetaUtils::getRenamedPostMeta($postId, 'generate_audio') === '1') {
            return true;
        }

        if (get_post_meta($postId, 'publish_post_to_speechkit', true) === '1') {
            return true;
        }

        $projectId = PostMetaUtils::getProjectId($postId);
        $contentId = PostMetaUtils::getContentId($postId);

        if ($projectId && $contentId) {
            return true;
        }

        return false;
    }

    /**
     * Get the Project ID for a WordPress Post.
     *
     * It is possible to change the BeyondWords project ID in the plugin settings,
     * so the current Project ID setting will not necessarily be correct for all
     * historic Posts. Because of this, we attempt to retrive the correct Project ID
     * from various custom fields, then fall-back to the plugin setting.
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     * @since 4.0.0 Apply beyondwords_project_id filter
     * @since 5.0.0 Remove beyondwords_project_id filter.
     *
     * @param int $postId Post ID.
     *
     * @return int|false Project ID, or false
     */
    public static function getProjectId($postId)
    {
        // Check custom fields
        $projectId = intval(PostMetaUtils::getRenamedPostMeta($postId, 'project_id'));

        if (! $projectId) {
            // It may also be found by parsing post_meta.speechkit_response
            $speechkit_response = static::getHttpResponseBodyFromPostMeta($postId, 'speechkit_response');
            preg_match('/"project_id":(")?(\d+)(?(1)\1|)/', (string)$speechkit_response, $matches);
            // $matches[2] is the Project ID (response.project_id)
            if ($matches && $matches[2]) {
                $projectId = intval($matches[2]);
            }
        }

        // If we still don't have an ID then use the default from the plugin settings
        if (! $projectId) {
            $projectId = intval(get_option('beyondwords_project_id'));
        }

        if (! $projectId) {
            // Return boolean false instead of numeric 0
            $projectId = false;
        }

        // todo throw ProjectIdNotFoundException?

        return $projectId;
    }

    /**
     * Get the Body Voice ID for a WordPress Post.
     *
     * We do not filter this, because the Block Editor directly accesses this
     * custom field, bypassing any filters we add here.
     *
     * @since 4.0.0
     *
     * @param int $postId Post ID.
     *
     * @return int|false Body Voice ID, or false
     */
    public static function getBodyVoiceId($postId)
    {
        $voiceId = get_post_meta($postId, 'beyondwords_body_voice_id', true);

        return $voiceId;
    }

    /**
     * Get the Title Voice ID for a WordPress Post.
     *
     * We do not filter this, because the Block Editor directly accesses this
     * custom field, bypassing any filters we add here.
     *
     * @since 4.0.0
     *
     * @param int $postId Post ID.
     *
     * @return int|false Title Voice ID, or false
     */
    public static function getTitleVoiceId($postId)
    {
        $voiceId = get_post_meta($postId, 'beyondwords_title_voice_id', true);

        return $voiceId;
    }

    /**
     * Get the Summary Voice ID for a WordPress Post.
     *
     * We do not filter this, because the Block Editor directly accesses this
     * custom field, bypassing any filters we add here.
     *
     * @since 4.0.0
     *
     * @param int $postId Post ID.
     *
     * @return int|false Summary Voice ID, or false
     */
    public static function getSummaryVoiceId($postId)
    {
        $voiceId = get_post_meta($postId, 'beyondwords_summary_voice_id', true);

        return $voiceId;
    }

    /**
     * Get the player style for a post.
     *
     * Defaults to the plugin setting if the custom field doesn't exist.
     *
     * @since 4.1.0
     *
     * @param int $postId Post ID.
     *
     * @return string Player style.
     */
    public static function getPlayerStyle($postId)
    {
        $playerStyle = get_post_meta($postId, 'beyondwords_player_style', true);

        // Prefer custom field
        if ($playerStyle) {
            return $playerStyle;
        }

        // Fall back to plugin setting
        return get_option('beyondwords_player_style', PlayerStyle::STANDARD);
    }

    /**
     * Get the "Error Message" value for a WordPress Post.
     *
     * Supports data saved with the `beyondwords_*` prefix and the legacy `speechkit_*` prefix.
     *
     * @since 3.7.0
     *
     * @param int $postId Post ID.
     *
     * @return string
     */
    public static function getErrorMessage($postId)
    {
        return PostMetaUtils::getRenamedPostMeta($postId, 'error_message');
    }

    /**
     * Get the "Disabled" value for a WordPress Post.
     *
     * Supports data saved with the `beyondwords_*` prefix and the legacy `speechkit_*` prefix.
     *
     * @since 3.7.0
     *
     * @param int $postId Post ID.
     *
     * @return string
     */
    public static function getDisabled($postId)
    {
        return PostMetaUtils::getRenamedPostMeta($postId, 'disabled');
    }

    /**
     * Get HTTP response body from post meta.
     *
     * The data may have been saved as a WordPress HTTP response array. If it was,
     * then return the 'body' key of the HTTP response instead of the raw post meta.
     *
     * The data may also have been saved as a WordPress WP_Error instance. If it was,
     * then return a string containing the WP_Error code and message.
     *
     * @since 3.0.3
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     * @since 3.6.1 Handle responses saved as object of class WP_Error
     *
     * @param int    $postId   Post ID.
     * @param string $metaName Post Meta name.
     *
     * @return string
     */
    public static function getHttpResponseBodyFromPostMeta($postId, $metaName)
    {
        $postMeta = get_post_meta($postId, $metaName, true);

        if (is_array($postMeta)) {
            return (string)wp_remote_retrieve_body($postMeta);
        }

        if (is_wp_error($postMeta)) {
            return sprintf(PostMetaUtils::WP_ERROR_FORMAT, $postMeta->get_error_code(), $postMeta->get_error_message());
        }

        return (string)$postMeta;
    }
}
