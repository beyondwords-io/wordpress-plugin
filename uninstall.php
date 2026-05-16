<?php

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

use BeyondWords\Core\Uninstaller;

/**
 * Uninstall script for BeyondWords.
 *
 * Executed when BeyondWords is uninstalled via built-in WordPress commands.
 *
 * @since 3.7.0
 *
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
function beyondwords_uninstall() {
	if (
		! defined( 'WP_UNINSTALL_PLUGIN' ) ||
		! WP_UNINSTALL_PLUGIN ||
		dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) )
	) {
		status_header( 404 );
        exit; // phpcs:ignore
	}

	if ( ! defined( 'BEYONDWORDS__PLUGIN_DIR' ) ) {
		define( 'BEYONDWORDS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	require BEYONDWORDS__PLUGIN_DIR . 'vendor/autoload.php';

	Uninstaller::cleanup_plugin_transients();
	Uninstaller::cleanup_plugin_options();
	Uninstaller::cleanup_custom_fields();
}

// phpcs:disable
beyondwords_uninstall();
// phpcs:enable
