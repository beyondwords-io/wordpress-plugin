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
}
