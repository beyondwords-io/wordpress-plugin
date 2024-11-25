<?php
/**
 * Don't auto-prepend the BeyondWords Player.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: beyondwords_player_auto_prepend
 * Description:       Don't auto-prepend the BeyondWords Player.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */
add_filter( 'beyondwords_player_auto_prepend', '__return_false' );
