<?php

declare(strict_types=1);

// phpcs:disable
/**
 *
 * @link              https://beyondwords.io
 * @since             3.0.0
 * @package           Beyondwords\Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords - Text-to-Speech
 * Plugin URI:        https://beyondwords.io
 * Description:       The effortless way to make content listenable. Automatically create audio versions and embed via our customizable player.
 * Author:            BeyondWords
 * Author URI:        https://beyondwords.io
 * Version:           5.3.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speechkit
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 5.8
 */
// phpcs:enable

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Composer autoload
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Define constants
// phpcs:disable
define('BEYONDWORDS__PLUGIN_VERSION', '5.3.0');
define('BEYONDWORDS__PLUGIN_DIR',     plugin_dir_path(__FILE__));
define('BEYONDWORDS__PLUGIN_URI',     plugin_dir_url(__FILE__));
// phpcs:enable

// Follow WordPress convention by using snakecase for variable name
$beyondwords_wordpress_plugin = new Beyondwords\Wordpress\Plugin();
$beyondwords_wordpress_plugin->init();
