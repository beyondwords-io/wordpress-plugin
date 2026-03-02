<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Debug;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings registration for the debug tool.
 *
 * @since 1.0.0
 */
class Settings {
	/**
	 * Option name for storing the debug setting.
	 */
	const OPTION_NAME = 'beyondwords_debug_rest_api';

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
	}

	/**
	 * Register the settings.
	 *
	 * @since 1.0.0
	 */
	public static function register_settings() {
		register_setting(
			'beyondwords_debug_settings',
			self::OPTION_NAME,
			[
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			]
		);
	}

	/**
	 * Check if debug logging is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {
		return (bool) get_option( self::OPTION_NAME, false );
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		delete_option( self::OPTION_NAME );
	}
}

Settings::init();
