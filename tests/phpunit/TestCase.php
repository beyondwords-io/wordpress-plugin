<?php

declare(strict_types=1);

/**
 * Base Test Case.
 *
 * This extends WP_UnitTestCase to provide additional functionality.
 */
abstract class TestCase extends WP_UnitTestCase
{
    /**
     * Capture output from a callback without printing it to console.
     *
     * This method tells PHPUnit to expect output, which prevents it from
     * printing the output to the console while still allowing assertions
     * on the captured output.
     *
     * @param callable $callback The function that produces output
     * @return string The captured output
     */
    protected function captureOutput(callable $callback): string
    {
        // Tell PHPUnit we expect output (this prevents console printing)
        $this->expectOutputRegex('/.*/s');

        // Execute the callback (output will be captured by PHPUnit)
        $callback();

        // Return the captured output for assertions
        return $this->getActualOutput();
    }

    /**
     * Intercept HTTP requests to a URL containing $contentId and return a 404.
     *
     * Only requests whose method is in $methods and whose URL contains
     * `/content/{$contentId}` are intercepted; everything else passes through
     * to the mock API server.
     *
     * @param string   $contentId The content ID that should trigger a 404.
     * @param string[] $methods   HTTP methods to intercept (e.g. ['PUT']).
     *
     * @return \Closure Filter callback (save a reference to remove it later).
     */
    protected function addNotFoundFilter(string $contentId, array $methods): \Closure
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
