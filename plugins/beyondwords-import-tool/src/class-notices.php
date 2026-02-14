<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notice queue for the import tool.
 *
 * @since 1.0.0
 */
class Notices {
	/**
	 * Queued admin notices to display.
	 *
	 * @var array
	 */
	private static $notices = [];

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_notices', [ self::class, 'render' ] );
	}

	/**
	 * Queue an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type (error, warning, success, info).
	 */
	public static function add( $message, $type = 'info' ) {
		self::$notices[] = [
			'message' => $message,
			'type'    => self::validate_notice_type( $type ),
		];
	}

	/**
	 * Validate and sanitize notice type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The notice type to validate.
	 * @return string Valid notice type, defaults to 'info' if invalid.
	 */
	private static function validate_notice_type( $type ) {
		$valid_types = [ 'error', 'warning', 'success', 'info', 'updated' ];

		if ( in_array( $type, $valid_types, true ) ) {
			return $type;
		}

		return 'info';
	}

	/**
	 * Render all queued admin notices.
	 *
	 * @since 1.0.0
	 */
	public static function render() {
		foreach ( self::$notices as $notice ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				wp_kses(
					$notice['message'],
					[
						'br'   => [],
						'code' => [],
					]
				)
			);
		}
	}
}

Notices::init();
