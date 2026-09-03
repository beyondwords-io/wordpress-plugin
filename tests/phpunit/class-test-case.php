<?php

declare(strict_types=1);

/**
 * Base Test Case.
 */
abstract class TestCase extends WP_UnitTestCase
{
    /**
     * Capture output from a callback without printing it to console.
     *
     * expectOutputRegex() makes PHPUnit swallow the output while still allowing assertions on it.
     *
     * @param callable $callback The function that produces output
     * @return string The captured output
     */
    protected function capture_output(callable $callback): string
    {
        $this->expectOutputRegex('/.*/s');

        $callback();

        return $this->getActualOutput();
    }

    /**
     * Intercept HTTP requests to a URL containing $contentId and return a 404.
     *
     * Non-matching requests pass through to the mock API server.
     *
     * @param string   $contentId The content ID that should trigger a 404.
     * @param string[] $methods   HTTP methods to intercept (e.g. ['PUT']).
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    protected function add_not_found_filter(string $contentId, array $methods): \Closure
    {
        $filter = function ($preempt, $parsedArgs, $url) use ($contentId, $methods) {
            if (
                in_array($parsedArgs['method'] ?? '', $methods, true) &&
                str_contains($url, '/content/' . $contentId)
            ) {
                return [
                    'response' => ['code' => 404, 'message' => 'Not Found'],
                    'body'     => '{"code":404,"message":"Not Found"}',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };

        add_filter('pre_http_request', $filter, 10, 3);

        return $filter;
    }

    /**
     * WP_Error message the transport-failure filter fails the create with.
     */
    protected const TRANSPORT_ERROR_MESSAGE = 'cURL error 28: Operation timed out after 3000 milliseconds';

    /**
     * Reject a content create the way the API rejects an already-used `source_id`.
     *
     * @param string|null $existingContentId Answers the follow-up lookup; null lets the GET fall through.
     * @param string|null $sourceUrl         Source URL on that content; defaults to this site's.
     * @param string|null $sourceId          Source ID on that content; defaults to echoing the requested ID.
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    protected function add_duplicate_source_id_filter(
        ?string $existingContentId = null,
        ?string $sourceUrl = null,
        ?string $sourceId = null
    ): \Closure {
        return $this->add_failing_create_filter(
            [
                'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
                'body'     => '{"code":422,"message":"Invalid request body","errors":[{"location":"source_id","message":"has already been taken"}]}',
                'headers'  => [],
                'cookies'  => [],
            ],
            $existingContentId,
            $sourceUrl,
            $sourceId
        );
    }

    /**
     * Fail a content create with a transport error, optionally answering the follow-up lookup.
     *
     * Models a client timeout after the API may already have accepted the POST.
     *
     * @param string|null $existingContentId Answers the follow-up lookup; null lets the GET fall through.
     * @param string|null $sourceUrl         Source URL on that content; defaults to this site's.
     * @param string|null $sourceId          Source ID on that content; defaults to echoing the requested ID.
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    protected function add_create_transport_failure_filter(
        ?string $existingContentId = null,
        ?string $sourceUrl = null,
        ?string $sourceId = null
    ): \Closure {
        return $this->add_failing_create_filter(
            new \WP_Error('http_request_failed', self::TRANSPORT_ERROR_MESSAGE),
            $existingContentId,
            $sourceUrl,
            $sourceId
        );
    }

    /**
     * Fail a content create with the given response, optionally answering the follow-up lookup.
     *
     * The lookup stub echoes the requested ID back as `source_id` (as the real
     * API does for a source-ID lookup) unless $sourceId overrides it.
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    private function add_failing_create_filter(
        array|\WP_Error $createResponse,
        ?string $existingContentId,
        ?string $sourceUrl,
        ?string $sourceId
    ): \Closure {
        $sourceUrl = $sourceUrl ?? home_url('/?p=1');

        $filter = function ($preempt, $parsedArgs, $url) use ($createResponse, $existingContentId, $sourceUrl, $sourceId) {
            $method = $parsedArgs['method'] ?? '';

            if ($method === 'POST' && str_ends_with($url, '/content')) {
                return $createResponse;
            }

            if ($existingContentId !== null && $method === 'GET' && preg_match('#/content/([^/?\#]+)#', $url, $matches)) {
                return [
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'body'     => wp_json_encode([
                        'id'            => $existingContentId,
                        'source_id'     => $sourceId ?? $matches[1],
                        'source_url'    => $sourceUrl,
                        'status'        => 'processed',
                        'preview_token' => 'a-preview-token',
                    ]),
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }

            return $preempt;
        };

        add_filter('pre_http_request', $filter, 10, 3);

        return $filter;
    }
}
