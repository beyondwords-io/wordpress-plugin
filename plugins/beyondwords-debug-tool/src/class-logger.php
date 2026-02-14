<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Debug;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTTP request/response logging for the debug tool.
 *
 * @since 1.0.0
 */
class Logger {
	/**
	 * Temporary storage for request data to pair with responses.
	 *
	 * @var array
	 */
	private static $pending_requests = [];

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Only hook into HTTP API if debugging is enabled.
		if ( Settings::is_debug_enabled() ) {
			add_filter( 'pre_http_request', [ self::class, 'log_pre_request' ], 10, 3 );
			add_filter( 'http_response', [ self::class, 'log_response' ], 10, 3 );
		}
	}

	/**
	 * Get the BeyondWords API URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_api_url() {
		if ( defined( 'BEYONDWORDS_API_URL' ) ) {
			return constant( 'BEYONDWORDS_API_URL' );
		}

		return 'https://api.beyondwords.io/v1';
	}

	/**
	 * Check if a URL is a BeyondWords API request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool
	 */
	private static function is_beyondwords_request( $url ) {
		$api_url = self::get_api_url();
		return str_starts_with($url, $api_url);
	}

	/**
	 * Log a request before it's made.
	 *
	 * @since 1.0.0
	 *
	 * @param false|array|\WP_Error $preempt     Whether to preempt an HTTP request's return value.
	 * @param array                 $parsed_args HTTP request arguments.
	 * @param string                $url         The request URL.
	 *
	 * @return false|array|\WP_Error
	 */
	public static function log_pre_request( $preempt, $parsed_args, $url ) {
		if ( ! self::is_beyondwords_request( $url ) ) {
			return $preempt;
		}

		$request_id  = uniqid( 'req_', true );
		$timestamp   = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$stack_trace = self::get_stack_trace();

		$log_entry = [
			'========================================',
			sprintf( '[%s] REQUEST START (ID: %s)', $timestamp, $request_id ),
			'========================================',
			sprintf( 'URL: %s', $url ),
			sprintf( 'Method: %s', strtoupper( $parsed_args['method'] ?? 'GET' ) ),
			'',
			'Headers:',
			self::format_headers( $parsed_args['headers'] ?? [] ),
			'',
			'Body:',
			self::format_body( $parsed_args['body'] ?? '' ),
			'',
			'Stack Trace:',
			$stack_trace,
			'',
		];

		LogFile::write_to_log( implode( "\n", $log_entry ) );

		// Store request info for pairing with response.
		self::$pending_requests[ $url ] = [
			'id'        => $request_id,
			'timestamp' => microtime( true ),
		];

		return $preempt;
	}

	/**
	 * Log a response after it's received.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $response    HTTP response or WP_Error object.
	 * @param array           $parsed_args HTTP request arguments.
	 * @param string          $url         The request URL.
	 *
	 * @return array|\WP_Error
	 */
	public static function log_response( $response, $parsed_args, $url ) {
		if ( ! self::is_beyondwords_request( $url ) ) {
			return $response;
		}

		$timestamp  = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$request_id = isset( self::$pending_requests[ $url ] ) ? self::$pending_requests[ $url ]['id'] : 'unknown';
		$duration   = '';

		if ( isset( self::$pending_requests[ $url ]['timestamp'] ) ) {
			$elapsed  = microtime( true ) - self::$pending_requests[ $url ]['timestamp'];
			$duration = sprintf( ' (%.3fs)', $elapsed );
		}

		$log_entry = [
			'----------------------------------------',
			sprintf( '[%s] RESPONSE%s (ID: %s)', $timestamp, $duration, $request_id ),
			'----------------------------------------',
			sprintf( 'URL: %s', $url ),
		];

		if ( is_wp_error( $response ) ) {
			$log_entry[] = sprintf( 'Error Code: %s', $response->get_error_code() );
			$log_entry[] = sprintf( 'Error Message: %s', $response->get_error_message() );
		} else {
			$status_code    = wp_remote_retrieve_response_code( $response );
			$status_message = wp_remote_retrieve_response_message( $response );
			$headers        = wp_remote_retrieve_headers( $response );
			$body           = wp_remote_retrieve_body( $response );

			$log_entry[] = sprintf( 'Status: %d %s', $status_code, $status_message );
			$log_entry[] = '';
			$log_entry[] = 'Response Headers:';
			$log_entry[] = self::format_headers( $headers instanceof \ArrayAccess ? $headers->getAll() : (array) $headers );
			$log_entry[] = '';
			$log_entry[] = 'Response Body:';
			$log_entry[] = self::format_body( $body );
		}

		$log_entry[] = '';
		$log_entry[] = '========================================';
		$log_entry[] = '';
		$log_entry[] = '';

		LogFile::write_to_log( implode( "\n", $log_entry ) );

		// Clean up pending request.
		unset( self::$pending_requests[ $url ] );

		return $response;
	}

	/**
	 * Format headers for logging.
	 *
	 * @since 1.0.0
	 *
	 * @param array $headers The headers to format.
	 *
	 * @return string
	 */
	private static function format_headers( $headers ) {
		if ( empty( $headers ) ) {
			return '  (none)';
		}

		$lines = [];
		foreach ( $headers as $name => $value ) {
			$display_value = is_array( $value ) ? implode( ', ', $value ) : $value;

			// Mask API keys, showing only last 4 characters.
			if ( strtolower( $name ) === 'x-api-key' && strlen( $display_value ) > 4 ) {
				$display_value = '******' . substr( $display_value, -4 );
			}

			$lines[] = sprintf( '  %s: %s', $name, $display_value );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Format body for logging, scrubbing sensitive fields.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $body The body to format.
	 *
	 * @return string
	 */
	private static function format_body( $body ) {
		if ( empty( $body ) ) {
			return '  (empty)';
		}

		if ( is_array( $body ) ) {
			$body = wp_json_encode( $body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		// Try to pretty-print JSON, scrubbing sensitive fields.
		$decoded = json_decode( $body, true );
		if ( json_last_error() === JSON_ERROR_NONE && $decoded !== null ) {
			$decoded = self::scrub_sensitive_fields( $decoded );
			$body    = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		// Indent each line.
		$lines = explode( "\n", $body );
		return '  ' . implode( "\n  ", $lines );
	}

	/**
	 * Sensitive field names to mask in logged JSON bodies.
	 */
	const SENSITIVE_KEYS = [
		'token',
		'access_token',
		'refresh_token',
		'secret',
		'password',
		'api_key',
		'apikey',
		'authorization',
		'credential',
		'private_key',
	];

	/**
	 * Recursively scrub sensitive fields from a decoded JSON array.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data The decoded JSON data.
	 *
	 * @return array The scrubbed data.
	 */
	private static function scrub_sensitive_fields( array $data ): array {
		foreach ( $data as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = self::scrub_sensitive_fields( $value );
			} elseif ( is_string( $key ) && in_array( strtolower( $key ), self::SENSITIVE_KEYS, true ) && is_string( $value ) && strlen( $value ) > 0 ) {
				$value = '******';
			}
		}

		return $data;
	}

	/**
	 * Get a formatted stack trace.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function get_stack_trace() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Stack traces are core functionality of this debug tool.
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
		$lines = [];

		foreach ( $trace as $i => $frame ) {
			// Skip internal frames.
			if ( $i < 3 ) {
				continue;
			}

			// Strip ABSPATH to avoid leaking server directory structure.
			$file     = str_replace( ABSPATH, '', $frame['file'] ?? '(unknown file)' );
			$line     = $frame['line'] ?? '?';
			$class    = isset( $frame['class'] ) ? $frame['class'] . $frame['type'] : '';
			$function = $frame['function'] ?? '(unknown function)';

			$lines[] = sprintf( '  #%d %s:%d %s%s()', $i - 3, $file, $line, $class, $function );
		}

		return empty( $lines ) ? '  (no stack trace available)' : implode( "\n", $lines );
	}
}

Logger::init();
