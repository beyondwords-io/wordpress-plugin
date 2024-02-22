<?php
/**
 * REST API: Insert
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       REST API: Insert
 * Description:       Assert we can generate audio for posts that are <em>inserted</em> using WP REST API Endpoints.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action( 'rest_api_init', function () {
  register_rest_route( 'beyondwords/v3-tests', '/post/insert', array(
    'methods' => 'POST',
    'callback' => 'beyondwords_rest_api_insert',
  ), true );
} );

function beyondwords_rest_api_insert(\WP_REST_Request $request) {
    $metaInput = [];

    if (isset($request['beyondwords_generate_audio'])) {
      $metaInput['beyondwords_generate_audio'] = $request['beyondwords_generate_audio'];
    }

    if (isset($request['speechkit_generate_audio'])) {
      $metaInput['speechkit_generate_audio'] = $request['speechkit_generate_audio'];
    }

    if (isset($request['publish_post_to_speechkit'])) {
      $metaInput['publish_post_to_speechkit'] = $request['publish_post_to_speechkit'];
    }

    $result = wp_insert_post([
        'post_status' => 'publish',
        'post_title' => 'Inserted using REST API',
        'post_content' => '<p>Uses post_status and meta_input values to match Japan Times REST API integration.</p>',
        'meta_input' => $metaInput,
    ], true, true);

    if (is_wp_error($result)) {
        return $result;
    }

    $response = [
      'post_id' => $result,
    ];

    return new WP_REST_Response( $response, 200 );
}