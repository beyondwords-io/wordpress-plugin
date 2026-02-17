<?php

declare( strict_types=1 );

/**
 *
 * @link              https://beyondwords.io
 * @since             1.0.0
 * @package           Beyondwords\Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords - Import Tool
 * Plugin URI:        https://beyondwords.io
 * Description:       Adds the "Import BeyondWords Data" tool.
 * Author:            BeyondWords
 * Author URI:        https://beyondwords.io
 * Version:           1.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speechkit
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'src/class-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-assets.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-file-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-notices.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-page.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-post-meta.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-transients.php';
