<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

class Transients
{
    public const TRANSIENT_EXPIRE = 1;

    /**
     * Init.
     */
    public function init()
    {
        add_filter('pre_http_request', array($this, 'preHttpRequest'), 10, 3);
        add_filter('http_response', array($this, 'httpResponse'), 10, 3);
    }

    /**
     * Check to see whether a response transient exists.
     */
    public function preHttpRequest($response, $parsedArgs, $url)
    {
        $path = $this->getRestApiUrlPath($parsedArgs, $url);

        // Check we are calling a BeyondWords REST API endpoint
        if (! $path) {
            return false;
        }

        $transientKey = sprintf('beyondwords/response/%s', $path);

        $projectId = get_option('beyondwords_project_id');

        // Default
        $return = false;

        switch ($path) {
            case sprintf('/projects/%d', $projectId):
                $return = get_transient($transientKey);
                break;
        }

        // Returning a non-false value with short-circuit the HTTP request
        return $return;
    }

    /**
     * Save transients for selected API responses.
     */
    public function httpResponse($response, $parsedArgs, $url)
    {
        $path = $this->getRestApiUrlPath($parsedArgs, $url);

        // Check we are calling a BeyondWords REST API endpoint
        if (! $path) {
            return $response;
        }

        $transientKey = sprintf('beyondwords/response/%s', $path);

        $projectId = get_option('beyondwords_project_id');

        switch ($path) {
            case sprintf('/projects/%d', $projectId):
                set_transient($transientKey, $response, Transients::TRANSIENT_EXPIRE);
                break;
        }

        return $response;
    }

    /**
     * Save transients for selected API responses.
     */
    private function getRestApiUrlPath($parsedArgs, $url)
    {
        $apiUrl = Environment::getApiUrl();

        // Are we calling our REST API?
        if (strpos($url, $apiUrl) !== 0) {
            return false;
        }

        // Is it a GET request?
        if (is_array($parsedArgs) && array_key_exists('method', $parsedArgs) && strtoupper($parsedArgs['method']) !== 'GET') {
            return false;
        }

        // Strip REST API URL to get the endpoint path
        return substr($url, strlen($apiUrl));
    }

    /**
     * @todo move all transients into one 'beyondwords_responses', so we can
     * delete/reset it after all the backend REST API calls have been made.
     */
    public function delete_transients()
    {
        delete_transient('beyondwords_responses');
    }
}
