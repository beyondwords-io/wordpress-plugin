<?php
/**
 * Filter the BeyondWords Player SDK params, styling the player with custom
 * colours and adding a widget for each section (each paragraph).
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Filter: beyondwords_player_sdk_params
 * Description:       Filter the BeyondWords Player SDK params.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */
function my_beyondwords_player_sdk_params( $params ) {
  $params['iconColor']             = 'rgb(234, 75, 151)';
  $params['highlightSections']     = 'all-none';
  $params['clickableSections']     = 'none';
  $params['segmentWidgetSections'] = 'body';
  $params['segmentWidgetPosition'] = '10-oclock';

  return $params;
}

add_filter( 'beyondwords_player_sdk_params', 'my_beyondwords_player_sdk_params', 10 );
