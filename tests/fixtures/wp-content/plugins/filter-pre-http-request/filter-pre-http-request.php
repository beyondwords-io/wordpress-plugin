<?php
/**
 * Filter the HTTP requests made during testing.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: pre_http_request
 * Description:       Filter WordPress HTTP requests made during testing.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */
function my_pre_http_request( false|array|WP_Error $response, array $parsed_args, string $url ) {
    // @todo Check this is a HTTP request to a specific domain.
    if (strpos($url, 'http(s)://any_domain.com') === false) {
        return $response;
    }

    // @todo check the parsed_args param to determine which response we should return.

    // @todo Return a response object to short-circuit the request.
    return [
        'body' => [
            'id' => 1,
        ],
        'headers' => [
            'content-type' => 'application/json',
        ],
        'response' => [
            'code' => 201,
        ],
    ];

    // @todo Alternatively return a WP_Error object to block the request.
    return new WP_Error(
        'http_request_block',
        __( 'This request is not allowed', 'speechkit' )
    );
}

add_filter( 'pre_http_request', 'my_pre_http_request' );
