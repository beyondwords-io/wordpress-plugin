<?php

declare( strict_types = 1 );

namespace Beyondwords\Wordpress\Debug;

defined( 'ABSPATH' ) || exit;

/**
 * Host capability checks for the debug tool.
 *
 * @since 1.1.0
 */
class Environment {
	/**
	 * Whether this site runs on WordPress VIP.
	 *
	 * Matches the detection in the main plugin's
	 * `BeyondWords\Post\Sync::is_async_generation_enabled()`, but duplicated so
	 * this plugin still works when activated on its own.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public static function is_vip() {
		return class_exists( '\Automattic\WP\Cron_Control\Main' )
			|| function_exists( 'wpcom_vip_schedule_single_event' )
			|| defined( 'VIP_GO_APP_ENVIRONMENT' );
	}

	/**
	 * Whether the host can support file-based logging.
	 *
	 * VIP serves uploads through a stream wrapper that does not honour
	 * `LOCK_EX`, so concurrent appends are not safe there. See
	 * doc/extension-plugins.md in the main plugin repository.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public static function supports_file_logging() {
		return ! self::is_vip();
	}
}
