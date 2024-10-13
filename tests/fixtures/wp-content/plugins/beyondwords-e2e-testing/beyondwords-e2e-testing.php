<?php
/**
 * WordPress filters for Cypress e2e testing.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords: e2e testing
 * Description:       WordPress filters for Cypress e2e testing.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */

/**
 * Filters the content params we send for audio processing.
 * 
 * Forces content to be created in "draft" status to prevent unnecessary audio processing 
 * during e2e tests.
 *
 * @since 5.0.0
 *
 * @param array $params  The content params.
 * @param int   $post_id The post ID.
 */
function beyondwords_e2e_draft_status_for_content( $params, $post_id ) {
    $params['status'] = 'draft';
    
    return $params;
}

add_filter( 'beyondwords_content_params', 'beyondwords_e2e_draft_status_for_content', 10, 2 );

/**
 * Filters the value of the beyondwords_project_id option.
 *
 * @since 5.0.0
 *
 * @param mixed  $value  Value of the option.
 * @param string $option Option name.
 */
function beyondwords_e2e_option_project_id($value, $option) {
    if ( ! empty($value) && defined('BEYONDWORDS_TESTS_PROJECT_ID') ) {
        return BEYONDWORDS_TESTS_PROJECT_ID;
    }

    return $value;
}
add_filter( 'option_beyondwords_project_id', 'beyondwords_e2e_option_project_id', 100, 2 );

/**
 * Filters the value of the beyondwords_api_key option.
 *
 * @since 5.0.0
 *
 * @param mixed  $value  Value of the option.
 * @param string $option Option name.
 */
function beyondwords_e2e_option_api_key($value, $option) {
    if ( ! empty($value) && defined('BEYONDWORDS_TESTS_API_KEY') ) {
        return BEYONDWORDS_TESTS_API_KEY;
    }

    return $value;
}
add_filter( 'option_beyondwords_api_key', 'beyondwords_e2e_option_api_key', 100, 2 );

/**
 * Short-circuits the return value of a meta field.
 *
 * @since 5.0.0
 *
 * @param mixed  $value     The value to return.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Metadata key.
 * @param bool   $single    Whether to return only the first value of the specified `$meta_key`.
 * @param string $meta_type Type of object metadata is for.
 */
function beyondwords_e2e_get_post_metadata($metadata, $object_id, $meta_key, $single, $meta_type){
    // beyondwords_content_id
    if ( 
        'beyondwords_content_id' === $meta_key 
        && ! empty($metadata) 
        && defined('BEYONDWORDS_TESTS_CONTENT_ID') 
    ) {
        return $single ? BEYONDWORDS_TESTS_CONTENT_ID : [ BEYONDWORDS_TESTS_CONTENT_ID ];
    }

    // beyondwords_project_id
    if ( 
        'beyondwords_project_id' === $meta_key 
        && ! empty($metadata) 
        && defined('BEYONDWORDS_TESTS_PROJECT_ID') 
    ) {
        return $single ? BEYONDWORDS_TESTS_PROJECT_ID : [ BEYONDWORDS_TESTS_PROJECT_ID ];
    }

    return $metadata;
}
add_filter( 'get_post_metadata', 'beyondwords_e2e_get_post_metadata', 100, 5 );