<?php
/**
 * Debug the `body` param for REST API content requests.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords - Debug REST API request body
 * Description:       Stores the value of the `body` param that we send in each of our REST API content requests in a `beyondwords_debug_rest_api_body` field for each post.
 * Author:            BeyondWords
 * Author URI:        https://beyondwords.io
 * Version:           1.0.0
 * Text Domain:       speechkit
 * License:           No License
 */

/**
 * Filters the content params we send for audio processing.
 *
 * In this case we return the same params. We only hook into the filter
 * to do some debugging of the `body` param for REST API content requests.
 *
 * @since 4.7.0
 *
 * @param string $params  The content params.
 * @param int    $post_id The post ID.
 */
function beyondwords_debug_rest_api_body( $params, $post_id ) {
    add_post_meta($post_id, 'beyondwords_debug_rest_api_body', $params['body']);

    return $params;
}
add_filter( 'beyondwords_content_params', 'beyondwords_debug_rest_api_body', 9999, 2 );
