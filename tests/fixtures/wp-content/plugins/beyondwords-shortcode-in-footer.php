<?php
/**
 * Output a BeyondWords player shortcode in the site footer.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords: Shortcode in footer
 * Description:       Outputs a BeyondWords player shortcode in the site footer.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */
function beyondwords_shortcode_in_footer() {
    echo do_shortcode( '[beyondwords_player]' );
}

add_action( 'wp_footer', 'beyondwords_shortcode_in_footer' );
