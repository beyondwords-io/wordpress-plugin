<?php
/**
 * Use the inline player script tag.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: beyondwords_player_inline_script_tag
 * Description:       Use the inline player script tag.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */
function my_beyondwords_player_inline_script_tag( $inline_script_tag ) {
    return true;
}

add_filter( 'beyondwords_player_inline_script_tag', 'my_beyondwords_player_inline_script_tag' );
