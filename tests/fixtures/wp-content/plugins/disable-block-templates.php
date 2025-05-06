<?php
/**
 * Disable Block Templates.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Disable Block Templates
 * Description:       Disables block templates so they don't get in the way for Cypress e2e tests.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_filter( 'default_block_template_types', '__return_empty_array' );

add_filter( 'block_editor_settings_all', function( $settings, $context ) {
  if ( $context->post && $context->post->post_type === 'page' ) {
    $settings['__experimentalTemplateShown'] = true;
  }
  return $settings;
}, 10, 2 );
