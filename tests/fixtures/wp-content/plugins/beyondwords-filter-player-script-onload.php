<?php
/**
 * Filter the BeyondWords Player script onload attribute.
 * Allows you to do things like call custom functions when the player has instantiated.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: beyondwords_player_script_onload
 * Description:       Filter the BeyondWords Player script onload attribute.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */

 /**
  * Filters the onload attribute of the BeyondWords Player script.
  *
  * Note that to support multiple players on one page, the
  * default script uses `document.querySelectorAll() to target all
  * instances of `div[data-beyondwords-player]` in the HTML source.
  * If this approach is removed then multiple occurrences of the
  * BeyondWords player in one page may not work as expected.
  *
  * @link https://github.com/beyondwords-io/player/blob/main/doc/getting-started.md#how-to-configure-it
  *
  * @since 4.0.0
  *
  * @param string $script The string value of the onload script.
  * @param array  $params The SDK params for the current post, including
  *                       `projectId` and `contentId`.
  */
 function my_beyondwords_player_script_onload( $onload, $params ) {
     // Console log the params we pass to the SDK
     $myCommand = 'console.log("🔊", ' . wp_json_encode($params, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES) . ');';

     // Prepend the command to the default onload script
     return $myCommand . $onload;
 }
 add_filter( 'beyondwords_player_script_onload', 'my_beyondwords_player_script_onload', 10, 2 );
