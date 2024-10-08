<?php
/**
 * Forces draft audio for e2e tests so we don't overload the system with
 * audio generation requests.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords: e2e Testing Filters
 * Description:       Forces REST API and player SDK params for e2e Cypress testing.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */

/**
 * Filters the content params we send for audio processing.
 *
 * @since 5.0.0
 *
 * @param array $params  The content params.
 * @param int   $post_id The post ID.
 */
function beyondwords_force_audio_status_to_draft( $params, $post_id ) {
    $params['status'] = 'draft';
    
    return $params;
}

add_filter( 'beyondwords_content_params', 'beyondwords_force_audio_status_to_draft', 10, 2 );

/**
 * Filters the BeyondWords Player SDK parameters.
 *
 * @since 5.0.0
 *
 * @param array $params  The SDK parameters.
 * @param int   $post_id The post ID.
 */
function beyondwords_force_player_content_id( $params, $post_id ) {
    $content_id = '';

    if ( defined('BEYONDWORDS_TESTS_CONTENT_ID') ) {
        $content_id = BEYONDWORDS_TESTS_CONTENT_ID;
    }
    

    $params[ 'contentId' ] = $content_id;

    return $params;
}
add_filter( 'beyondwords_player_sdk_params', 'beyondwords_force_player_content_id', 10, 2 );
  