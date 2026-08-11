<?php

declare( strict_types = 1 );

// phpcs:disable
/**
 *
 * @link              https://beyondwords.io
 * @since             3.0.0
 * @package           Beyondwords\Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords - AI audio for publishers
 * Plugin URI:        https://beyondwords.io
 * Description:       Turn WordPress articles into audio as you publish, with tools for distribution, monetization, and analytics.
 * Author:            BeyondWords
 * Author URI:        https://beyondwords.io
 * Version:           7.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speechkit
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 6.6
 */
// phpcs:enable

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// phpcs:disable
define('BEYONDWORDS__PLUGIN_VERSION', '7.0.0');
define('BEYONDWORDS__PLUGIN_DIR',     plugin_dir_path(__FILE__));
define('BEYONDWORDS__PLUGIN_URI',     plugin_dir_url(__FILE__));
// phpcs:enable

BeyondWords\Core\Plugin::init();
