<?php

declare( strict_types=1 );

/**
 *
 * @link              https://speechkit.io
 * @since             1.0.0
 * @package           Beyondwords\Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       SpeechKit - Export Tool
 * Plugin URI:        https://speechkit.io
 * Description:       Adds the "Export SpeechKit Data" tool.
 * Author:            SpeechKit
 * Author URI:        https://speechkit.io
 * Version:           1.0.2
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speechkit
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'src/class-exporter.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-page.php';
