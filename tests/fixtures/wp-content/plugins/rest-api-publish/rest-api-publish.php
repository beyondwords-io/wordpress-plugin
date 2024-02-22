<?php
/**
 * REST API: Publish
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       REST API: Publish
 * Description:       Assert we can generate audio for posts that are <em>published</em> using WP REST API Endpoints.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action( 'rest_api_init', function () {
  register_rest_route( 'beyondwords/v3-tests', '/post/publish/(?P<id>\d+)', array(
    'methods' => 'PUT',
    'callback' => 'beyondwords_rest_api_publish',
  ), true );
} );

function beyondwords_rest_api_publish($postId) {
    $postId = wp_update_post($postId, [
        'post_status' => 'publish',
        'post_title' => 'Published using REST API',
    ]);

    if (is_wp_error($postId)) {
        return $postId;
    }

    return new WP_REST_Response( $postId, 200 );
}