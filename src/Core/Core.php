<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 **/
class Core
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static and stop loading plugin text domain on init.
     */
    public static function init(): void
    {
        // Actions
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueBlockEditorAssets'], 1, 0);
        add_action('init', [self::class, 'registerMeta'], 99, 3);

        // Actions for adding/updating posts
        add_action('wp_after_insert_post', [self::class, 'onAddOrUpdatePost'], 99);

        // Actions for trashing/deleting posts
        add_action('wp_trash_post', [self::class, 'onTrashPost']);
        add_action('before_delete_post', [self::class, 'onDeletePost']);

        add_filter('is_protected_meta', [self::class, 'isProtectedMeta'], 10, 2);

        // Older posts may be missing beyondwords_language_code, so we'll try to set it.
        add_filter('get_post_metadata', [self::class, 'getLangCodeFromJsonIfEmpty'], 10, 3);
    }

    /**
     * Should process post status?
     *
     * @since 3.5.0
     * @since 3.7.0 Process audio for posts with 'pending' status
     * @since 5.0.0 Remove beyondwords_post_statuses filter.
     * @since 6.0.0 Make static.
     *
     * @param string $status WordPress post status (e.g. 'pending', 'publish', 'private', 'future', etc).
     */
    public static function shouldProcessPostStatus(string $status): bool
    {
        $statuses = ['pending', 'publish', 'private', 'future'];

        /**
         * Filters the post statuses that we consider for audio processing.
         *
         * When a post is saved with any other post status we will not send
         * any data to the BeyondWords API.
         *
         * The default values are "pending", "publish", "private" and "future".
         *
         * @since 3.3.3 Introduced as beyondwords_post_statuses.
         * @since 3.7.0 Process audio for posts with 'pending' status.
         * @since 4.3.0 Renamed from beyondwords_post_statuses to beyondwords_settings_post_statuses.
         *
         * @param string[] $statuses The post statuses that we consider for audio processing.
         */
        $statuses = apply_filters('beyondwords_settings_post_statuses', $statuses);

        // Only generate audio for certain post statuses
        if (is_array($statuses) && in_array($status, $statuses)) {
            return true;
        }

        return false;
    }

    /**
     * Should generate audio for post?
     *
     * @since 3.5.0
     * @since 3.10.0 Remove wp_is_post_revision check
     * @since 5.1.0  Regenerate audio for all post statuses
     * @since 6.0.0  Make static, ignore revisions, refactor status
     *               checks, and add support Magic Embed support.
     *
     * @param int $postId WordPress Post ID.
     */
    public static function shouldGenerateAudioForPost(int $postId): bool
    {
        // Ignore autosaves and revisions
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return false;
        }

        $status = get_post_status($postId);

        // Only (re)generate audio for certain post statuses.
        if (! self::shouldProcessPostStatus($status)) {
            return false;
        }

        // Generate if the "Generate audio" custom field is set.
        if (PostMetaUtils::hasGenerateAudio($postId)) {
            return (bool) get_post_meta($postId, 'beyondwords_generate_audio', true);
        }

        return false;
    }

    /**
     * Generate audio for a post if certain conditions are met.
     *
     * @since 3.0.0
     * @since 3.2.0 Added speechkit_post_statuses filter
     * @since 3.5.0 Refactored, adding self::shouldGenerateAudioForPost()
     * @since 5.1.0 Move project ID check into self::shouldGenerateAudioForPost()
     * @since 6.0.0 Make static and support Magic Embed.
     *
     * @param int $postId WordPress Post ID.
     *
     * @return array|false|null Response from API, or false if audio was not generated.
     */
    public static function generateAudioForPost(int $postId): array|false|null
    {
        // Perform checks to see if this post should be processed
        if (! self::shouldGenerateAudioForPost($postId)) {
            return false;
        }

        $post = get_post($postId);
        if (! $post) {
            return false;
        }

        $integrationMethod = IntegrationMethod::getIntegrationMethod($post);

        // For Magic Embed we call the "get_player_by_source_id" endpoint to import content.
        if (IntegrationMethod::CLIENT_SIDE === $integrationMethod) {
            // Save the integration method & Project ID.
            update_post_meta($postId, 'beyondwords_integration_method', IntegrationMethod::CLIENT_SIDE);
            update_post_meta($postId, 'beyondwords_project_id', get_option('beyondwords_project_id'));

            return ApiClient::getPlayerBySourceId($postId);
        }

        // For non-Magic Embed we use the REST API to generate audio.
        update_post_meta($postId, 'beyondwords_integration_method', IntegrationMethod::REST_API);

        // Does this post already have audio?
        $contentId = PostMetaUtils::getContentId($postId);

        // Has autoregeneration for Post updates been disabled?
        if ($contentId) {
            if (defined('BEYONDWORDS_AUTOREGENERATE') && ! BEYONDWORDS_AUTOREGENERATE) {
                return false;
            }

            $response = ApiClient::updateAudio($postId);
        } else {
            $response = ApiClient::createAudio($postId);
        }

        $projectId = PostMetaUtils::getProjectId($postId);

        self::processResponse($response, $projectId, $postId);

        return $response;
    }

    /**
     * Delete audio for post.
     *
     * @since 4.0.5
     * @since 6.0.0 Make static.
     *
     * @param int $postId WordPress Post ID.
     *
     * @return array|false|null Response from API, or false if audio was not generated.
     */
    public static function deleteAudioForPost(int $postId): array|false|null
    {
        return ApiClient::deleteAudio($postId);
    }

    /**
     * Batch delete audio for posts.
     *
     * @since 4.1.0
     * @since 6.0.0 Make static.
     *
     * @param int[] $postIds Array of WordPress Post IDs.
     *
     * @return array|false Response from API, or false if audio was not generated.
     */
    public static function batchDeleteAudioForPosts(array $postIds): array|false|null
    {
        return ApiClient::batchDeleteAudio($postIds);
    }

    /**
     * Process the response body of a BeyondWords REST API response.
     *
     * @since 3.0.0
     * @since 3.7.0 Stop saving response.access_key, we don't currently use it.
     * @since 4.0.0 Replace Podcast IDs with Content IDs
     * @since 4.5.0 Save response.preview_token to support post scheduling.
     * @since 5.0.0 Stop saving `beyondwords_podcast_id`.
     * @since 6.0.0 Make static.
     */
    public static function processResponse(mixed $response, int|string|false $projectId, int $postId): mixed
    {
        if (! is_array($response)) {
            return $response;
        }

        if ($projectId && ! empty($response['id'])) {
            update_post_meta($postId, 'beyondwords_project_id', $projectId);
            update_post_meta($postId, 'beyondwords_content_id', $response['id']);

            if (! empty($response['preview_token'])) {
                update_post_meta($postId, 'beyondwords_preview_token', $response['preview_token']);
            }

            if (! empty($response['language'])) {
                update_post_meta($postId, 'beyondwords_language_code', $response['language']);
            }

            if (! empty($response['title_voice_id'])) {
                update_post_meta($postId, 'beyondwords_title_voice_id', $response['title_voice_id']);
            }

            if (! empty($response['summary_voice_id'])) {
                update_post_meta($postId, 'beyondwords_summary_voice_id', $response['summary_voice_id']);
            }

            if (! empty($response['body_voice_id'])) {
                update_post_meta($postId, 'beyondwords_body_voice_id', $response['body_voice_id']);
            }
        }

        return $response;
    }

    /**
     * Enqueue Core (built & minified) JS for Block Editor.
     *
     * @since 3.0.0
     * @since 4.5.1 Disable plugin features if we don't have valid API settings.
     * @since 6.0.0 Make static.
     */
    public static function enqueueBlockEditorAssets(): void
    {
        if (! SettingsUtils::hasValidApiConnection()) {
            return;
        }

        $postType = get_post_type();

        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (in_array($postType, $postTypes, true)) {
            $assetFile = include BEYONDWORDS__PLUGIN_DIR . 'build/index.asset.php';

            // Register the Block Editor JS
            wp_enqueue_script(
                'beyondwords-block-js',
                BEYONDWORDS__PLUGIN_URI . 'build/index.js',
                $assetFile['dependencies'],
                $assetFile['version'],
                true
            );
        }
    }

    /**
     * Register meta fields for REST API output.
     *
     * It is recommended to register meta keys for a specific combination
     * of object type and object subtype.
     *
     * @since 2.5.0
     * @since 3.9.0 Don't register speechkit_status - downgrades to plugin v2.x are no longer expected.
     * @since 6.0.0 Make static.
     **/
    public static function registerMeta(): void
    {
        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (is_array($postTypes)) {
            $keys = CoreUtils::getPostMetaKeys('all');

            foreach ($postTypes as $postType) {
                $options = [
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => 'string',
                    'default' => '',
                    'object_subtype' => $postType,
                    'prepare_callback' => 'sanitize_text_field',
                    'sanitize_callback' => 'sanitize_text_field',
                    'auth_callback' => fn(): bool => current_user_can('edit_posts'),
                ];

                foreach ($keys as $key) {
                    register_meta('post', $key, $options);
                }
            }
        }
    }

    /**
     * Make all of our custom fields private, so they don't appear in the
     * "Custom Fields" panel, which can cause conflicts for the Block Editor.
     *
     * https://github.com/WordPress/gutenberg/issues/23078
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function isProtectedMeta(bool $protected, string $metaKey): bool
    {
        $keysToProtect = CoreUtils::getPostMetaKeys('all');

        if (in_array($metaKey, $keysToProtect, true)) {
            $protected = true;
        }

        return $protected;
    }

    /**
     * On trash post.
     *
     * We attempt to send a DELETE REST API request when a post is trashed so the audio
     * no longer appears in playlists, or in the publishers BeyondWords dashboard.
     *
     * @since 3.9.0 Introduced.
     * @since 5.4.0 Renamed from onTrashOrDeletePost, and we now remove all
     *              BeyondWords data when a post is trashed.
     * @since 6.0.0 Make static.
     *
     * @param int $postId Post ID.
     **/
    public static function onTrashPost(int $postId): void
    {
        ApiClient::deleteAudio($postId);
        PostMetaUtils::removeAllBeyondwordsMetadata($postId);
    }

    /**
     * On delete post.
     *
     * We attempt to send a DELETE REST API request when a post is deleted so the audio
     * no longer appears in playlists, or in the publishers BeyondWords dashboard.
     *
     * @since 5.4.0 Introduced, replacing onTrashOrDeletePost.
     * @since 6.0.0 Make static.
     *
     * @param int $postId Post ID.
     **/
    public static function onDeletePost(int $postId): void
    {
        ApiClient::deleteAudio($postId);
    }

    /**
     * WP Save Post action.
     *
     * Fires after a post, its terms and meta data has been saved.
     *
     * @since 3.0.0
     * @since 3.2.0 Added beyondwords_post_statuses filter.
     * @since 3.6.1 Improve $postBefore hash comparison.
     * @since 3.9.0 Renamed method from wpAfterInsertPost to onAddOrUpdatePost.
     * @since 4.0.0 Removed hash comparison.
     * @since 4.4.0 Delete audio if beyondwords_delete_content custom field is set.
     * @since 4.5.0 Remove unwanted debugging custom fields.
     * @since 5.1.0 Move post status check out of here.
     * @since 6.0.0 Make static and refactor for Magic Embed updates.
     *
     * @param int $postId Post ID.
     **/
    public static function onAddOrUpdatePost(int $postId): bool
    {
        // Has the "Remove" feature been used?
        if (get_post_meta($postId, 'beyondwords_delete_content', true) === '1') {
            // Make DELETE API request
            self::deleteAudioForPost($postId);

            // Remove custom fields
            PostMetaUtils::removeAllBeyondwordsMetadata($postId);

            return false;
        }

        return (bool) self::generateAudioForPost($postId);
    }

    /**
     * Get the language code from a JSON mapping if it is empty.
     *
     * @since 5.4.0 Introduced.
     * @since 6.0.0 Make static.
     *
     * @param mixed  $value     The value of the metadata.
     * @param int    $object_id The ID of the object metadata is for.
     * @param string $meta_key  The key of the metadata.
     * @param bool   $single    Whether to return a single value.
     */
    public static function getLangCodeFromJsonIfEmpty(mixed $value, int $object_id, string $meta_key): mixed
    {
        if ('beyondwords_language_code' === $meta_key && empty($value)) {
            $languageId = get_post_meta($object_id, 'beyondwords_language_id', true);

            if ($languageId) {
                $langCodes = json_decode(file_get_contents(BEYONDWORDS__PLUGIN_DIR . 'assets/lang-codes.json'), true);

                if (is_array($langCodes) && array_key_exists($languageId, $langCodes)) {
                    return [$langCodes[$languageId]];
                }
            }
        }

        return $value;
    }
}
