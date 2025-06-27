<?php
/**
 * Filters the content params we send for audio processing.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: beyondwords_content_params
 * Description:       Filters the content params we send for audio processing.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */

/**
 * Filters the content params we send for audio processing.
 * In this example we prefix the Post ID to the post body.
 *
 * @since 4.0.0
 *
 * @param string $params The content params.
 * @param int    $post_id The post ID.
 */
function my_beyondwords_content_params( $params, $post_id ) {
    update_post_meta($post_id, 'BEFORE:beyondwords_content', $params['body']);

    $params['body'] = '<p>Post ID: ' . $post_id . '</p>' . $params['body'];

    update_post_meta($post_id, 'AFTER:beyondwords_content', $params['body']);

    return $params;
}
add_filter( 'beyondwords_content_params', 'my_beyondwords_content_params', 10, 2 );
