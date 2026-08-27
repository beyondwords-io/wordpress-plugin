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
     * Reject a content create the way the API rejects an already-used `source_id`.
     *
     * @param string|null $existingContentId Answers the follow-up lookup; null lets the GET fall through.
     * @param string|null $sourceUrl         Source URL on that content; defaults to this site's.
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    protected function add_duplicate_source_id_filter(
        ?string $existingContentId = null,
        ?string $sourceUrl = null
    ): \Closure {
        $sourceUrl = $sourceUrl ?? home_url('/?p=1');

        $filter = function ($preempt, $parsedArgs, $url) use ($existingContentId, $sourceUrl) {
            $method = $parsedArgs['method'] ?? '';

            if ($method === 'POST' && str_ends_with($url, '/content')) {
                return [
                    'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
                    'body'     => '{"code":422,"message":"Invalid request body","errors":[{"location":"source_id","message":"has already been taken"}]}',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }

            if ($existingContentId !== null && $method === 'GET' && str_contains($url, '/content/')) {
                return [
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'body'     => wp_json_encode([
                        'id'            => $existingContentId,
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

	/**
	 * Fail a content create with a transport error, optionally answering the follow-up lookup.
	 *
	 * Models a client timeout after the API may already have accepted the POST.
	 *
	 * @param string|null $existingContentId Answers the follow-up lookup; null lets the GET fall through.
	 * @param string|null $sourceUrl         Source URL on that content; defaults to this site's.
	 * @param string      $errorMessage      WP_Error message the create returns.
	 *
	 * @return \Closure Filter callback (save a reference to remove it later).
	 */
	protected function add_create_transport_failure_filter(
		?string $existingContentId = null,
		?string $sourceUrl = null,
		string $errorMessage = 'cURL error 28: Operation timed out after 3000 milliseconds'
	): \Closure {
		$sourceUrl = $sourceUrl ?? home_url('/?p=1');

		$filter = function ($preempt, $parsedArgs, $url) use ($existingContentId, $sourceUrl, $errorMessage) {
			$method = $parsedArgs['method'] ?? '';

			if ($method === 'POST' && str_ends_with($url, '/content')) {
				return new \WP_Error('http_request_failed', $errorMessage);
			}

			if ($existingContentId !== null && $method === 'GET' && str_contains($url, '/content/')) {
				return [
					'response' => ['code' => 200, 'message' => 'OK'],
					'body'     => wp_json_encode([
						'id'            => $existingContentId,
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
