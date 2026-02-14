<?php
/**
 * BeyondWords: Remove Auto Player
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords: Remove Auto Player
 * Plugin URI:        https://beyondwords.io
 * Description:       Removes the BeyondWords player that's automatically added to posts, keeping only manually inserted players.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
function beyondwords_remove_auto_player( $html, $post_id, $project_id, $content_id, $context ) {
	if ( $context === 'auto' ) {
		return '';
	}

	return $html;
}
add_filter( 'beyondwords_player_html', 'beyondwords_remove_auto_player', 10, 5 );
