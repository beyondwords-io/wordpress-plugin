<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Request;
use Beyondwords\Wordpress\Component\Post\PostContentUtils;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 **/
class ApiClient
{
    public const ERROR_FORMAT = '#%s: %s';

    /**
     * Init.
     */
    public function init()
    {
        add_action('admin_notices', array($this, 'adminNotices'));
    }

    /**
     * POST /projects/:id/content.
     *
     * @since 3.0.0
     *
     * @param int $postId WordPress Post ID
     *
     * @return Response|false Response, or false
     **/
    public function createAudio($postId)
    {
        $projectId = PostMetaUtils::getProjectId($postId);

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content', Environment::getApiUrl(), $projectId);

        $body = PostContentUtils::getContentParams($postId);

        $request = new Request('POST', $url, $body);

        return $this->callApi($request, $postId);
    }

    /**
     * PUT /projects/:id/content/:id.
     *
     * @since 3.0.0
     *
     * @param int $postId WordPress Post ID
     *
     * @return Response|false Response, or false
     **/
    public function updateAudio($postId)
    {
        $projectId = PostMetaUtils::getProjectId($postId);
        $contentId = PostMetaUtils::getContentId($postId);

        if (! $projectId || ! $contentId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content/%s', Environment::getApiUrl(), $projectId, $contentId);

        $body = PostContentUtils::getContentParams($postId);

        $request = new Request('PUT', $url, $body);

        return $this->callApi($request, $postId);
    }

    /**
     * DELETE /projects/:id/content/:id.
     *
     * @since 3.0.0
     *
     * @param int $postId WordPress Post ID
     *
     * @return Response|false Response, or false
     **/
    public function deleteAudio($postId)
    {
        $projectId = PostMetaUtils::getProjectId($postId);
        $contentId = PostMetaUtils::getContentId($postId);

        if (! $projectId || ! $contentId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/content/%s', Environment::getApiUrl(), $projectId, $contentId);

        $request = new Request('DELETE', $url);

        return $this->callApi($request, $postId);
    }

    /**
     * DELETE /projects/:id/content/:id.
     *
     * @since 4.1.0
     *
     * @param int[] $postIds Array of WordPress Post IDs.
     *
     * @throws \Exception
     * @return int[] The Post IDs with deleted audio.
     **/
    public function batchDeleteAudio($postIds)
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

        $body = wp_json_encode(['ids' => $contentIds[$projectId]]);

        $request = new Request('POST', $url, $body);

        $args = array(
            'blocking'    => true,
            'body'        => $request->getBody(),
            'headers'     => $request->getHeaders(),
            'method'      => $request->getMethod(),
            'sslverify'   => true,
        );

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
     * GET /organization/languages
     *
     * @since 4.0.0 Introduced
     * @since 4.0.2 Prefix endpoint with /organization
     *
     * @return array|object Array of voices or API error object.
     **/
    public function getLanguages()
    {
        $url = sprintf('%s/organization/languages', Environment::getApiUrl());

        $request = new Request('GET', $url);

        return $this->callApi($request);
    }

    /**
     * GET /organization/voices
     *
     * @since 4.0.0 Introduced
     * @since 4.0.2 Prefix endpoint with /organization
     * @since 4.5.1 Check the $languageId param is numeric.
     *
     * @param int $languageId BeyondWords Language ID
     *
     * @return array|object Array of voices or API error object.
     **/
    public function getVoices($languageId)
    {
        if (! is_numeric($languageId)) {
            return [];
        }

        $url = sprintf('%s/organization/voices?filter[language.id]=%s', Environment::getApiUrl(), urlencode(strval($languageId))); // phpcs:ignore Generic.Files.LineLength.TooLong

        $request = new Request('GET', $url);

        return $this->callApi($request);
    }

    /**
     * GET /projects/:id.
     *
     * @since 4.0.0
     *
     * @return Response|false Response, or false
     **/
    public function getProject()
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d', Environment::getApiUrl(), $projectId);

        $request = new Request('GET', $url);

        return $this->callApi($request);
    }

    /**
     * GET /projects/:id/player_settings.
     *
     * @since 4.0.0
     *
     * @return Response|false Response, or false
     **/
    public function getPlayerSettings()
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/player_settings', Environment::getApiUrl(), $projectId);

        $request = new Request('GET', $url);

        return $this->callApi($request);
    }

    /**
     * PUT /projects/:id/player_settings.
     *
     * @since 4.0.0
     *
     * @param array $settings Associative array of player settings.
     *
     * @return Response|false Response, or false
     **/
    public function updatePlayerSettings($settings)
    {
        $projectId = get_option('beyondwords_project_id');

        if (! $projectId) {
            return false;
        }

        $url = sprintf('%s/projects/%d/player_settings', Environment::getApiUrl(), $projectId);

        $request = new Request('PUT', $url, wp_json_encode($settings));

        return $this->callApi($request);
    }

    /**
     * GET /projects/:id/video_settings.
     *
     * @since 4.1.0
     *
     * @param int $projectId BeyondWords Project ID.
     *
     * @return Response|false Response, or false
     **/
    public function getVideoSettings($projectId = null)
    {
        if (! $projectId) {
            $projectId = get_option('beyondwords_project_id');

            if (! $projectId) {
                return false;
            }
        }

        $url = sprintf('%s/projects/%d/video_settings', Environment::getApiUrl(), $projectId);

        $request = new Request('GET', $url);

        $request = new Request('GET', $url);

        return $this->callApi($request);
    }

    /**
     * Call the BeyondWords API backend.
     *
     * @since 3.0.0
     * @since 3.9.0 Stop saving the speechkit_status post meta - downgrades to plugin v2.x are no longer expected.
     * @since 4.0.0 Removed hash comparison.
     * @since 4.4.0 Handle 204 responses with no body.
     *
     * @param Request $request Request.
     * @param int     $postId  WordPress Post ID
     *
     * @return array|null|false JSON-decoded response body, or null for 204, or false on failure
     **/
    public function callApi($request, $postId = false)
    {
        // Pure
        $args = $this->buildRequestArgs($request);

        if ($postId) {
            // Side-effect: db write
            $this->deleteErrors($postId);

            // Side-effect: db write
            $this->addRequestLog($request, $args, $postId);
        }

        // WordPress core methods
        $response        = wp_remote_request($request->getUrl(), $args);
        $responseCode    = wp_remote_retrieve_response_code($response);
        $responseMessage = wp_remote_retrieve_response_message($response);
        $responseBody    = json_decode(wp_remote_retrieve_body($response), true);

        // 204 responses have no body
        if ($responseCode === 204) {
            return null;
        }

        // Handle HTTP errors
        if (is_wp_error($response) || $responseCode > 299) {
            // Prefer the response "message" field over the HTTP status message
            if (is_array($responseBody) && array_key_exists('message', $responseBody)) {
                $responseMessage = $responseBody['message'];
            }
        }

        $responseBodyJson = wp_remote_retrieve_body($response);
        $responseBody     = json_decode($responseBodyJson, true);

        // Response had a HTTP error code (3XX, 4XX, 5XX)
        if ($responseCode > 299) {
            $errorMessage = $this->errorMessageFromResponse($response);

            $this->saveErrorMessage($postId, $responseMessage, $responseCode);

            return false;
        }

        // Handle invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = sprintf(
                /* translators: %s is replaced with the reason that JSON parsing failed */
                __('Unable to parse JSON in BeyondWords API response. Reason: %s.', 'speechkit'),
                // Don't allow any tags
                wp_kses(json_last_error_msg(), [])
            );

            $this->saveErrorMessage($postId, $errorMessage, 500);

            return false;
        }

        if ($postId) {
            // Modifies db
            $this->deleteRequestLog($postId);
        }

        return $responseBody;
    }

    /**
     * Build the request args for wp_remote_request().
     *
     * @since 3.0.0
     * @since 4.0.0 Removed hash comparison and display 403 errors.
     * @since 4.1.0 Introduced.
     *
     * @param Request $request BeyondWords Request.
     *
     * @return array WordPress HTTP Request arguments.
     */
    public function buildRequestArgs($request)
    {
        return [
            'blocking'    => true,
            'body'        => $request->getBody(),
            'headers'     => $request->getHeaders(),
            'method'      => $request->getMethod(),
            'sslverify'   => true,
        ];
    }

    /**
     * Error message from BeyondWords REST API response.
     *
     * @since 4.1.0
     *
     * @param mixed[] $response BeyondWords REST API response.
     *
     * @return string Error message.
     */
    public function errorMessageFromResponse($response)
    {
        $body = wp_remote_retrieve_body($response);
        $body = json_decode($body, true);

        if (is_array($body) && array_key_exists('errors', $body)) {
            $messages = [];

            foreach ($body['errors'] as $error) {
                $messages[] = implode(" ", array_values($error));
            }

            $message = implode(", ", $messages);
        } elseif (is_array($body) && array_key_exists('message', $body)) {
            $message = $body['message'];
        } else {
            $message = wp_remote_retrieve_response_message($response);
        }

        return $message;
    }

    /**
     * Deletes errors for a post.
     *
     * @since 4.1.0 Introduced.
     *
     * @param int $postId WordPress post ID.
     */
    public function deleteErrors($postId)
    {
        // Reset any existing errors before making this API call
        delete_post_meta($postId, 'speechkit_error_message');
        delete_post_meta($postId, 'beyondwords_error_message');
    }

    /**
     * Log the request details for a post.
     *
     * @since 4.1.0 Introduced.
     *
     * @param int    $postId   WordPress post ID.
     * @param Request $request BeyondWords Request.
     *
     * @param array WordPress HTTP Request arguments.
     */
    public function addRequestLog($request, $args, $postId)
    {
        // Log the request URL and args for debugging
        // (these are removed for successful requests)
        update_post_meta($postId, 'beyondwords_request_url', $request->getUrl());
        update_post_meta($postId, 'beyondwords_request_args', var_export($args, true)); // phpcs:ignore
    }

    /**
     * Deletes request details for a post.
     *
     * @since 4.1.0 Introduced.
     *
     * @param int $postId WordPress post ID.
     */
    public function deleteRequestLog($postId)
    {
        // Success, so remove request URL and args log
        delete_post_meta($postId, 'beyondwords_request_url');
        delete_post_meta($postId, 'beyondwords_request_args');
    }

    /**
     * Add an error message for a post.
     *
     * @since 4.1.0 Introduced.
     * @since 4.4.0 Rename from error() to saveErrorMessage().
     *
     * @param int    $postId  WordPress post ID.
     * @param string $message Error message.
     * @param int    $code    Error code.
     */
    public function saveErrorMessage($postId, $message = '', $code = 500)
    {
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
            sprintf(self::ERROR_FORMAT, (string)$code, $message, $code)
        );
    }
}
