<?php

declare( strict_types=1 );

/**
 *
 * @link              https://beyondwords.io
 * @since             1.0.0
 * @package           Beyondwords\Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords - Debug Tool
 * Plugin URI:        https://beyondwords.io
 * Description:       Debug tool for logging BeyondWords REST API requests and responses.
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

require_once plugin_dir_path( __FILE__ ) . 'src/class-log-file.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-page.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-settings.php';

register_deactivation_hook(
	__FILE__,
	function () {
		Beyondwords\Wordpress\Debug\Settings::deactivate();
		Beyondwords\Wordpress\Debug\LogFile::delete_log_file();
	}
);
