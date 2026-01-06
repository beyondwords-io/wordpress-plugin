<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Request;
use Beyondwords\Wordpress\Component\Post\PostContentUtils;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 **/
class ApiClient
{
    /**
     * Error format.
     *
     * The error format used to display error messages in WordPress admin.
     *
     * @var string
     */
    public const ERROR_FORMAT = '#%s: %s';

    /**
     * GET /projects/:id/content/:id.
     *
     * @param string     $contentId BeyomndWords Content ID
     * @param int|string $projectId BeyondWords Project ID, optional.
     *
     * @return WP_Response|false
     **/
    public static function getContent(int|string $contentId, int|string|null $projectId = null): array|false
    {
        if (! $projectId) {
            $projectId = get_option('beyondwords_project_id');
        }

        if (! $projectId || ! $contentId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content/%s', Environment::getApiUrl(), $projectId, $contentId);

        $request  = new Request('GET', $url);

        return self::callApi($request);
    }

    /**
     * POST /projects/:id/content.
     *
     * @since 3.0.0
     * @since 5.2.0 Make static.
     *
     * @param int $postId WordPress Post ID
     *
     * @return mixed JSON-decoded response body
     **/
    public static function createAudio(int $postId): array|null|false
    {
        $projectId = PostMetaUtils::getProjectId($postId);

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content', Environment::getApiUrl(), $projectId);

        $body = PostContentUtils::getContentParams($postId);

        $request  = new Request('POST', $url, $body);
        $response = self::callApi($request, $postId);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * PUT /projects/:id/content/:id.
     *
     * @since 3.0.0
     * @since 5.2.0 Make static.
     * @since 6.0.0 Add support for Magic Embed.
     *
     * @param int $postId WordPress Post ID
     *
     * @return mixed JSON-decoded response body
     **/
    public static function updateAudio(int $postId): array|null|false
    {
        $projectId = PostMetaUtils::getProjectId($postId);
        $contentId = PostMetaUtils::getContentId($postId, true); // fallback to Post ID

        if (! $projectId || ! $contentId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content/%s', Environment::getApiUrl(), $projectId, $contentId);

        $body = PostContentUtils::getContentParams($postId);

        $request  = new Request('PUT', $url, $body);
        $response = self::callApi($request, $postId);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * DELETE /projects/:id/content/:id.
     *
     * @since 3.0.0
     * @since 5.2.0 Make static.
     * @since 6.0.0 Add support for Magic Embed.
     *
     * @param int $postId WordPress Post ID
     *
     * @return mixed JSON-decoded response body
     **/
    public static function deleteAudio(int $postId): array|null|false
    {
        $projectId = PostMetaUtils::getProjectId($postId);
        $contentId = PostMetaUtils::getContentId($postId, true); // fallback to Post ID

        if (! $projectId || ! $contentId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content/%s', Environment::getApiUrl(), $projectId, $contentId);

        $request  = new Request('DELETE', $url);
        $response = self::callApi($request, $postId);
        $code     = wp_remote_retrieve_response_code($response);

        // Expect 204 Deleted
        if ($code !== 204) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * DELETE /projects/:id/content/:id.
     *
     * @since 4.1.0
     * @since 5.2.0 Make static.
     * @since 5.2.2 Remove sslverify param & increase timeout to 30s for REST API calls.
     *
     * @param int[] $postIds Array of WordPress Post IDs.
     *
     * @throws \Exception
     * @return mixed JSON-decoded response body
     **/
    public static function batchDeleteAudio(array $postIds): array|false
    {
        $contentIds = [];
        $updatedPostIds = [];

        foreach ($postIds as $postId) {
            $projectId = PostMetaUtils::getProjectId($postId);

            if (! $projectId) {
                continue;
            }

            $contentId = PostMetaUtils::getContentId($postId);

            if (! $contentId) {
                continue;
            }

            $contentIds[$projectId][] = $contentId;
            $updatedPostIds[] = $postId;
        }

        if (! count($contentIds)) {
            throw new \Exception(esc_html__('None of the selected posts had valid BeyondWords audio data.', 'speechkit')); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        if (count($contentIds) > 1) {
            throw new \Exception(esc_html__('Batch delete can only be performed on audio belonging a single project.', 'speechkit')); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        $projectId = array_key_first($contentIds);

        $url = sprintf('%s/projects/%d/content/batch_delete', Environment::getApiUrl(), $projectId);

        $body = (string) wp_json_encode(['ids' => $contentIds[$projectId]]);

        $request = new Request('POST', $url, $body);

        $args = [
            'blocking' => true,
            'body'     => $request->getBody(),
            'headers'  => $request->getHeaders(),
            'method'   => $request->getMethod(),
            'timeout'  => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
        ];

        $response = wp_remote_request($request->getUrl(), $args);

        // WordPress error performing API call
        if (is_wp_error($response)) {
            throw new \Exception(esc_html($response->get_error_message()));
        }

        $responseCode = wp_remote_retrieve_response_code($response);

        if ($responseCode <= 299) {
            // An OK response means all content IDs in the request were deleted
            return $updatedPostIds;
        } else {
            // For non-OK responses we do not want to delete any custom fields,
            // so return an empty array
            return [];
        }
    }

    /**
     * GET /projects/:id/player/by_source_id/:id.
     *
     * This will return the player data for a post by its source ID. It is used
     * for Client-Side integration, where the content is generated based on the
     * source ID & URL of the post instead of a BeyondWords REST API call.
     *
     * @since 6.0.0 Introduced.
     *
     * @param int $postId WordPress Post ID
     *
     * @return mixed JSON-decoded response body, or false on failure.
     **/
    public static function getPlayerBySourceId(int $postId): array|null|false
    {
        $projectId = PostMetaUtils::getProjectId($postId);

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/player/by_source_id/%d', Environment::getApiUrl(), $projectId, $postId);

        $request = new Request('GET', $url);
        $request->addHeaders([
            'X-Import' => 'true',
            'X-Referer' => esc_url(get_permalink($postId)),
        ]);

        $response = self::callApi($request, $postId);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * GET /organization/languages
     *
     * @since 4.0.0 Introduced
     * @since 4.0.2 Prefix endpoint with /organization
     * @since 5.0.0 Cache response using transients
     * @since 5.2.0 Make static.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function getLanguages(): array|null|false
    {
        $url = sprintf('%s/organization/languages', Environment::getApiUrl());

        $request  = new Request('GET', $url);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * GET /organization/voices
     *
     * @since 4.0.0 Introduced
     * @since 4.0.2 Prefix endpoint with /organization
     * @since 4.5.1 Check the $languageId param is numeric.
     * @since 5.0.0 Accept numeric language ID or string language code as param.
     * @since 5.2.0 Make static.
     *
     * @param int|string $language BeyondWords Language code or numeric ID
     *
     * @return mixed JSON-decoded response body
     **/
    public static function getVoices(int|string $languageCode): array|null|false
    {
        $url = sprintf(
            '%s/organization/voices?filter[language.code]=%s&filter[scopes][]=primary&filter[scopes][]=secondary',
            Environment::getApiUrl(),
            urlencode(strval($languageCode))
        );

        $request  = new Request('GET', $url);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Loops though GET /organization/voices, because
     * GET /organization/voice is not available.
     *
     * @since 5.4.0
     *
     * @param int       $voiceId  Voice ID.
     * @param int|false $languageCode Language code, optional.
     *
     * @return object|false Voice, or false if not found.
     **/
    public static function getVoice(int $voiceId, int|string|false $languageCode = false): object|array|false
    {
        if (! $languageCode) {
            $languageCode = get_option('beyondwords_project_language_code');
        }

        $voices = self::getVoices($languageCode);

        if (empty($voices)) {
            return false;
        }

        return array_column($voices, null, 'id')[$voiceId] ?? false;
    }

    /**
     * PUT /voices/:id.
     *
     * @since 5.0.0
     * @since 5.2.0 Make static.
     * @since 6.0.0 Cast body to string.
     *
     * @param array $settings Associative array of voice settings.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function updateVoice(int $voiceId, array $settings): array|null|false
    {
        if (empty($voiceId)) {
            return false;
        }

        $url = sprintf('%s/organization/voices/%d', Environment::getApiUrl(), $voiceId);

        $body = (string) wp_json_encode($settings);

        $request  = new Request('PUT', $url, $body);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * GET /projects/:id.
     *
     * @since 4.0.0
     * @since 5.0.0 Cache response using transients
     * @since 5.2.0 Make static.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function getProject(): array|null|false
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d', Environment::getApiUrl(), $projectId);

        $request  = new Request('GET', $url);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * PUT /projects/:id.
     *
     * @since 5.0.0
     * @since 5.2.0 Make static.
     * @since 6.0.0 Cast body to string.
     *
     * @param array $settings Associative array of project settings.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function updateProject(array $settings): array|null|false
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d', Environment::getApiUrl(), $projectId);

        $body = (string) wp_json_encode($settings);

        $request  = new Request('PUT', $url, $body);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * GET /projects/:id/player_settings.
     *
     * @since 4.0.0
     * @since 5.2.0 Make static.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function getPlayerSettings(): array|null|false
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/player_settings', Environment::getApiUrl(), $projectId);

        $request  = new Request('GET', $url);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * PUT /projects/:id/player_settings.
     *
     * @since 4.0.0
     * @since 5.2.0 Make static.
     * @since 6.0.0 Cast body to string.
     *
     * @param array $settings Associative array of player settings.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function updatePlayerSettings(array $settings): array|null|false
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/player_settings', Environment::getApiUrl(), $projectId);

        $body = (string) wp_json_encode($settings);

        $request  = new Request('PUT', $url, $body);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * GET /projects/:id/video_settings.
     *
     * @since 4.1.0
     * @since 5.0.0 Cache response using transients
     * @since 5.2.0 Make static.
     *
     * @param int $projectId BeyondWords Project ID.
     *
     * @return mixed JSON-decoded response body
     **/
    public static function getVideoSettings(int|null $projectId = null): array|null|false
    {
        if (! $projectId) {
            $projectId = get_option('beyondwords_project_id');

            if (! $projectId) {
                return false;
            }
        }

        $url = sprintf('%s/projects/%d/video_settings', Environment::getApiUrl(), (int)$projectId);

        $request  = new Request('GET', $url);
        $response = self::callApi($request);

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Call the BeyondWords API backend, logging any errors if the requests for a particular post.
     *
     * @todo investigate whether we can move the logging into a WordPress HTTP filter.
     *
     * @since 3.0.0
     * @since 3.9.0 Stop saving the speechkit_status post meta - downgrades to plugin v2.x are no longer expected.
     * @since 4.0.0 Removed hash comparison.
     * @since 4.4.0 Handle 204 responses with no body.
     * @since 5.2.0 Make static, return result from wp_remote_request.
     * @since 6.0.0 Add Magic Embed support and stop saving temporary request logs.
     *
     * @param Request $request Request.
     * @param int     $postId  WordPress Post ID
     *
     * @return array|WP_Error The response array or a WP_Error on failure. See WP_Http::request() for
     *                        information on return value.
     **/
    public static function callApi(Request $request, int|false $postId = false): array|\WP_Error
    {
        $post = get_post($postId);

        // Delete existing errors before making this API call
        self::deleteErrors($postId);

        $args = self::buildRequestArgs($request);

        // Get response
        $response     = wp_remote_request($request->getUrl(), $args);
        $responseCode = wp_remote_retrieve_response_code($response);

        // Mark API connection as invalid for 401 (API key may have been revoked)
        if ($responseCode === 401) {
            delete_option('beyondwords_valid_api_connection');
        }

        // Save error messages from WordPress HTTP errors and BeyondWords REST API error responses
        if (
            $post instanceof \WP_Post &&
            IntegrationMethod::REST_API === IntegrationMethod::getIntegrationMethod($post) &&
            (is_wp_error($response) || $responseCode > 299)
        ) {
            $message = self::errorMessageFromResponse($response);

            self::saveErrorMessage($postId, $message, $responseCode);
        }

        return $response;
    }

    /**
     * Build the request args for wp_remote_request().
     *
     * @since 3.0.0
     * @since 4.0.0 Removed hash comparison and display 403 errors.
     * @since 4.1.0 Introduced.
     * @since 5.2.0 Make static.
     * @since 5.2.2 Remove sslverify param & increase timeout to 30s for REST API calls.
     * @since 6.0.0 Add user-agent.
     *
     * @param Request $request BeyondWords Request.
     *
     * @return array WordPress HTTP Request arguments.
     */
    public static function buildRequestArgs(Request $request): array
    {
        return [
            'blocking'   => true,
            'body'       => $request->getBody(),
            'headers'    => $request->getHeaders(),
            'method'     => $request->getMethod(),
            'timeout'    => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
        ];
    }

    /**
     * Error message from BeyondWords REST API response.
     *
     * @since 4.1.0
     * @since 5.2.0 Make static.
     *
     * @param mixed[] $response BeyondWords REST API response.
     *
     * @return string Error message.
     */
    public static function errorMessageFromResponse(array|\WP_Error $response): string
    {
        $body = wp_remote_retrieve_body($response);
        $body = json_decode($body, true);

        $message = wp_remote_retrieve_response_message($response);

        if (is_array($body)) {
            if (array_key_exists('errors', $body)) {
                $messages = [];

                foreach ($body['errors'] as $error) {
                    $messages[] = implode(' ', array_values($error));
                }

                $message = implode(', ', $messages);
            } elseif (array_key_exists('message', $body)) {
                $message = $body['message'];
            }
        }

        return $message;
    }

    /**
     * Deletes errors for a post.
     *
     * @since 4.1.0 Introduced.
     * @since 5.2.0 Make static.
     *
     * @param int $postId WordPress post ID.
     */
    public static function deleteErrors(int|false $postId): void
    {
        if (! $postId) {
            return;
        }

        // Reset any existing errors before making this API call
        delete_post_meta($postId, 'speechkit_error_message');
        delete_post_meta($postId, 'beyondwords_error_message');
    }

    /**
     * Add an error message for a post.
     *
     * This was updated in v6.0 to support Magic Embed. 404 errors are not saved for Magic Embed,
     * because content will (re)generate when pages are visited.
     *
     * @since 4.1.0 Introduced.
     * @since 4.4.0 Rename from error() to saveErrorMessage().
     * @since 5.2.0 Make static.
     * @since 6.0.0 Add Magic Embed support.
     * @since 6.0.4 Fix bug where global integration method was checked instead of post meta.
     * @since 6.0.4 Remove unnecessary extra sprintf() param for self::ERROR_FORMAT.
     *
     * @param int    $postId  WordPress post ID.
     * @param string $message Error message.
     * @param int    $code    Error code.
     */
    public static function saveErrorMessage(int|false $postId, string $message = '', int|string $code = 500): void
    {
        if (! $postId) {
            return;
        }

        $post = get_post($postId);

        // Don't save an error message for Client-side 404s - they will (re)generate when pages are visited.
        if (
            404 === $code &&
            $post instanceof \WP_Post &&
            IntegrationMethod::CLIENT_SIDE === IntegrationMethod::getIntegrationMethod($post)
        ) {
            return;
        }

        if (! $message) {
            $message = sprintf(
                /* translators: %s is replaced with the support email link */
                esc_html__('API request error. Please contact %s.', 'speechkit'),
                '<a href="mailto:support@beyondwords.io">support@beyondwords.io</a>'
            );
        }

        if (! $code) {
            $code = 500;
        }

        update_post_meta(
            $postId,
            'beyondwords_error_message',
            sprintf(self::ERROR_FORMAT, (string)$code, $message)
        );
    }
}
