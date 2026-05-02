<?php
/**
 * Lightweight HTTP request value object used by `ApiClient`.
 *
 * Wraps method/url/body/headers and pre-populates the BeyondWords API key
 * header so callers don't have to.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Mutable request descriptor passed to `ApiClient::call_api()`.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Request {

	const AUTH_HEADER_NAME         = 'X-Api-Key';
	const CONTENT_TYPE_HEADER_NAME = 'Content-Type';
	const CONTENT_TYPE_HEADER_VALUE = 'application/json';

	private string $method  = '';
	private string $url     = '';
	private string $body    = '';
	private array $headers = [];

	/**
	 * Build a request, auto-attaching the API key and (for write methods) a JSON
	 * Content-Type header.
	 *
	 * @param string               $method  HTTP method (case-insensitive).
	 * @param string               $url     Absolute URL.
	 * @param string               $body    Request body (already JSON-encoded for write methods).
	 * @param array<string,string> $headers Extra headers, merged on top of the auto-attached ones.
	 */
	public function __construct(
		string $method,
		string $url,
		string $body = '',
		array $headers = []
	) {
		$this->set_method( $method );
		$this->set_url( $url );
		$this->set_body( $body );

		$this->add_headers(
			[
				self::AUTH_HEADER_NAME => get_option( 'beyondwords_api_key' ),
			]
		);

		if ( in_array( $this->method, [ 'POST', 'PUT', 'DELETE' ], true ) ) {
			$this->add_headers(
				[
					self::CONTENT_TYPE_HEADER_NAME => self::CONTENT_TYPE_HEADER_VALUE,
				]
			);
		}

		$this->add_headers( $headers );
	}

	public function get_method(): string {
		return $this->method;
	}

	public function set_method( string $method ): void {
		$this->method = strtoupper( $method );
	}

	public function get_url(): string {
		return $this->url;
	}

	public function set_url( string $url ): void {
		$this->url = $url;
	}

	public function get_body(): string {
		return $this->body;
	}

	public function set_body( string $body ): void {
		$this->body = $body;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_headers(): array {
		return $this->headers;
	}

	/**
	 * @param array<string,string> $headers
	 */
	public function set_headers( array $headers ): void {
		$this->headers = $headers;
	}

	/**
	 * Merge additional headers on top of the existing ones (later wins).
	 *
	 * @param array<string,string> $headers
	 */
	public function add_headers( array $headers ): void {
		$this->set_headers( array_merge( $this->get_headers(), $headers ) );
	}
}
