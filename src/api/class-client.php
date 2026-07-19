<?php
/**
 * BeyondWords REST API client: one method per endpoint, errors normalised into post meta.
 *
 * Auth and Content-Type headers are injected via the `http_request_args`
 * filter, only for requests targeting the BeyondWords API host.
 *
 * @package BeyondWords\Api
 * @since   3.0.0
 * @since   7.0.0 Moved from BeyondWords\Core\ApiClient to BeyondWords\Api\Client.
 */

declare( strict_types = 1 );

namespace BeyondWords\Api;

defined( 'ABSPATH' ) || exit;

/**
 * BeyondWords API client.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Client {

	/**
	 * Format for the `beyondwords_error_message` post meta value.
	 *
	 * The HTTP-status prefix lets `Sync::update_or_recreate_audio()` recognise
	 * 404s by matching `#404:` without parsing the body.
	 */
	const ERROR_FORMAT = '#%s: %s';

	/**
	 * How long to cache editor dropdown data (languages, voices, templates, project settings).
	 */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * How long to negative-cache a failed editor-dropdown fetch.
	 *
	 * Short enough that an outage self-heals within minutes, long enough that an
	 * unreachable API doesn't block every admin edit-screen render.
	 *
	 * @since 7.0.0
	 */
	const CACHE_TTL_ON_ERROR = 2 * MINUTE_IN_SECONDS;

	/**
	 * Default timeout, in seconds, for a BeyondWords API request.
	 *
	 * VIP's approved ceiling for a blocking remote request; content writes return
	 * immediately and generate audio server-side, so nothing needs longer.
	 *
	 * @since 7.0.0
	 */
	const DEFAULT_REQUEST_TIMEOUT = 3;

	/**
	 * Timeout, in seconds, for the voices GET — the one slow endpoint.
	 *
	 * Voices is ~3.7s p95 against ~250ms for every other GET, so the default
	 * timeout would abandon (and then negative-cache) many cold-cache fetches.
	 *
	 * @since 7.0.0
	 */
	const VOICES_REQUEST_TIMEOUT = 8;

	/**
	 * Register WordPress hooks.
	 *
	 * Must run early in the bootstrap so the filter precedes any API call.
	 */
	public static function init(): void {
		// The VIP warning targets raised timeouts; this filter only adds headers.
		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_args
		add_filter( 'http_request_args', [ self::class, 'filter_http_request_args' ], 10, 2 );
	}

	/**
	 * Inject `X-Api-Key` and JSON `Content-Type` headers for BeyondWords API requests only.
	 *
	 * @param array<string,mixed> $args WordPress HTTP args.
	 * @param string              $url  Outbound request URL.
	 *
	 * @return array<string,mixed>
	 */
	public static function filter_http_request_args( $args, $url ) {
		if ( ! is_array( $args ) || ! is_string( $url ) ) {
			return $args;
		}

		$api_url = \BeyondWords\Core\Urls::get_api_url();

		if ( '' === $api_url || ! str_starts_with( $url, $api_url ) ) {
			return $args;
		}

		$headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : [];

		// Caller-supplied X-Api-Key wins (lets tests inject deliberately bad keys).
		if ( ! isset( $headers['X-Api-Key'] ) ) {
			$headers['X-Api-Key'] = (string) get_option( 'beyondwords_api_key', '' );
		}

		$method = strtoupper( (string) ( $args['method'] ?? 'GET' ) );

		if (
			in_array( $method, [ 'POST', 'PUT', 'DELETE' ], true )
			&& ! isset( $headers['Content-Type'] )
		) {
			$headers['Content-Type'] = 'application/json';
		}

		$args['headers'] = $headers;

		return $args;
	}

	/**
	 * GET /projects/:project/content/:content_id
	 *
	 * @param string          $content_id BeyondWords content ID.
	 * @param int|string|null $project_id Optional project ID override.
	 *
	 * @return array<mixed>|\WP_Error|false Raw HTTP response, WP_Error on transport
	 *                                      failure, or false when an ID is missing.
	 */
	public static function get_content( int|string $content_id, int|string|null $project_id = null ): array|\WP_Error|false {
		if ( ! $project_id ) {
			$project_id = get_option( 'beyondwords_project_id' );
		}

		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url = sprintf( '%s/projects/%d/content/%s', \BeyondWords\Core\Urls::get_api_url(), $project_id, rawurlencode( (string) $content_id ) );

		return self::call_api( 'GET', $url );
	}

	/**
	 * POST /projects/:project/content
	 *
	 * @param int $post_id WordPress post ID.
	 *
	 * @return array<mixed>|null|false Decoded response body, or false when the
	 *                                 post has no project ID.
	 */
	public static function create_audio( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content', \BeyondWords\Core\Urls::get_api_url(), $project_id );
		$body     = \BeyondWords\Post\Content::get_content_params( $post_id );
		$response = self::call_api( 'POST', $url, $body, $post_id );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * PUT /projects/:project/content/:content_id
	 *
	 * Falls back to the post ID as the content ID for Magic Embed posts that
	 * never had a BeyondWords-issued ID.
	 *
	 * @param int $post_id WordPress post ID.
	 *
	 * @return array<mixed>|null|false Decoded response body, or false when an ID is missing.
	 */
	public static function update_audio( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
		$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id, true );

		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content/%s', \BeyondWords\Core\Urls::get_api_url(), $project_id, rawurlencode( (string) $content_id ) );
		$body     = \BeyondWords\Post\Content::get_content_params( $post_id );
		$response = self::call_api( 'PUT', $url, $body, $post_id );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * DELETE /projects/:project/content/:content_id
	 *
	 * @param int $post_id WordPress post ID.
	 *
	 * @return array<mixed>|null|false `false` when the request didn't return 204.
	 */
	public static function delete_audio( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
		$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id, true );

		return self::delete_audio_by_ids( $project_id, $content_id, $post_id );
	}

	/**
	 * DELETE /projects/:project/content/:content_id using explicit IDs.
	 *
	 * Split out from `delete_audio()` so the deferred trash/delete cron job can
	 * still delete after the post meta has been wiped.
	 *
	 * @since 7.0.0
	 *
	 * @param int|string|false $project_id BeyondWords project ID.
	 * @param int|string|false $content_id BeyondWords content ID.
	 * @param int|false        $post_id    Optional post ID for error attribution.
	 *
	 * @return array<mixed>|null|false `false` when an ID is missing or the request didn't return 204.
	 */
	public static function delete_audio_by_ids( int|string|false $project_id, int|string|false $content_id, int|false $post_id = false ): array|null|false {
		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content/%s', \BeyondWords\Core\Urls::get_api_url(), $project_id, rawurlencode( (string) $content_id ) );
		$response = self::call_api( 'DELETE', $url, '', $post_id );

		if ( 204 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * POST /projects/:project/content/batch_delete
	 *
	 * Refuses cross-project batches — the API only supports one project per request.
	 *
	 * @param int[] $post_ids WordPress post IDs.
	 *
	 * @return int[]|false Updated post IDs on success, empty array for non-OK responses.
	 *
	 * @throws \Exception When no posts have BeyondWords data, or multiple projects are mixed.
	 */
	public static function batch_delete_audio( array $post_ids ): array|false {
		$content_ids      = [];
		$updated_post_ids = [];

		foreach ( $post_ids as $post_id ) {
			$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
			if ( ! $project_id ) {
				continue;
			}

			$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id );
			if ( ! $content_id ) {
				continue;
			}

			$content_ids[ $project_id ][] = $content_id;
			$updated_post_ids[]           = $post_id;
		}

		if ( empty( $content_ids ) ) {
			throw new \Exception(
				esc_html__( 'None of the selected posts had valid BeyondWords audio data.', 'speechkit' )
			);
		}

		if ( count( $content_ids ) > 1 ) {
			throw new \Exception(
				esc_html__( 'Batch delete can only be performed on audio belonging a single project.', 'speechkit' )
			);
		}

		$project_id = array_key_first( $content_ids );
		$url        = sprintf( '%s/projects/%d/content/batch_delete', \BeyondWords\Core\Urls::get_api_url(), $project_id );
		$body       = (string) wp_json_encode( [ 'ids' => $content_ids[ $project_id ] ] );

		$response = wp_remote_request( $url, self::build_args( 'POST', $body ) );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// On failure, return no IDs so the caller keeps local meta and can retry.
		return $response_code <= 299 ? $updated_post_ids : [];
	}

	/**
	 * GET /projects/:project/player/by_source_id/:post_id
	 *
	 * Magic Embed bootstrap: BeyondWords looks up or creates content for the source URL.
	 *
	 * @param int $post_id WordPress post ID used as the source ID.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_player_by_source_id( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );

		if ( ! $project_id ) {
			return false;
		}

		$url     = sprintf( '%s/projects/%d/player/by_source_id/%d', \BeyondWords\Core\Urls::get_api_url(), $project_id, $post_id );
		$headers = [
			'X-Import'  => 'true',
			'X-Referer' => esc_url( get_permalink( $post_id ) ),
		];

		$response = self::call_api( 'GET', $url, '', $post_id, $headers );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * GET /organization/languages
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_languages(): array|null|false {
		$url = sprintf( '%s/organization/languages', \BeyondWords\Core\Urls::get_api_url() );

		return self::cached_get( 'languages', $url );
	}

	/**
	 * GET /organization/voices?filter[language.code]=…
	 *
	 * @param int|string $language_code BeyondWords language code (or numeric ID).
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_voices( int|string $language_code ): array|null|false {
		$url = sprintf(
			'%s/organization/voices?filter[language.code]=%s&filter[scopes][]=primary&filter[scopes][]=secondary',
			\BeyondWords\Core\Urls::get_api_url(),
			rawurlencode( strval( $language_code ) )
		);

		return self::cached_get( 'voices_' . $language_code, $url, self::VOICES_REQUEST_TIMEOUT );
	}

	/**
	 * Look up one voice by ID by listing all voices for a language.
	 *
	 * The API doesn't expose `/voices/:id`, so we fetch the list and filter.
	 *
	 * @param int              $voice_id      Voice ID.
	 * @param int|string|false $language_code Language code (required — no global fallback as of 7.0.0).
	 *
	 * @return object|array<mixed>|false Voice record, or false when missing.
	 */
	public static function get_voice( int $voice_id, int|string|false $language_code = false ): object|array|false {
		if ( ! $language_code ) {
			return false;
		}

		$voices = self::get_voices( $language_code );

		if ( empty( $voices ) ) {
			return false;
		}

		return array_column( $voices, null, 'id' )[ $voice_id ] ?? false;
	}

	/**
	 * GET /projects/:id/video_settings
	 *
	 * @param int|null $project_id Optional override; falls back to the global option.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_video_settings( ?int $project_id = null ): array|null|false {
		if ( ! $project_id ) {
			$project_id = get_option( 'beyondwords_project_id' );

			if ( ! $project_id ) {
				return false;
			}
		}

		$url = sprintf( '%s/projects/%d/video_settings', \BeyondWords\Core\Urls::get_api_url(), (int) $project_id );

		return self::cached_get( 'video_settings_' . (int) $project_id, $url );
	}

	/**
	 * GET /projects/:id
	 *
	 * @since 7.0.0
	 *
	 * @param int|null $project_id Optional override; falls back to the global option.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_project( ?int $project_id = null ): array|null|false {
		if ( ! $project_id ) {
			$project_id = get_option( 'beyondwords_project_id' );

			if ( ! $project_id ) {
				return false;
			}
		}

		$url = sprintf( '%s/projects/%d', \BeyondWords\Core\Urls::get_api_url(), (int) $project_id );

		return self::cached_get( 'project_' . (int) $project_id, $url );
	}

	/**
	 * GET /summarization_settings_templates
	 *
	 * @since 7.0.0
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_summarization_settings_templates(): array|null|false {
		$url = sprintf( '%s/summarization_settings_templates', \BeyondWords\Core\Urls::get_api_url() );

		return self::cached_get( 'summarization_settings_templates', $url );
	}

	/**
	 * GET /video_settings_templates
	 *
	 * @since 7.0.0
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_video_settings_templates(): array|null|false {
		$url = sprintf( '%s/video_settings_templates', \BeyondWords\Core\Urls::get_api_url() );

		return self::cached_get( 'video_settings_templates', $url );
	}

	/**
	 * Make the API call, normalising errors into post meta when a post is supplied.
	 *
	 * A 401 also clears `beyondwords_valid_api_connection` so the settings page
	 * re-runs validation.
	 *
	 * @param string               $method  HTTP method.
	 * @param string               $url     Absolute URL.
	 * @param string               $body    Request body (already JSON-encoded for write methods).
	 * @param int|false            $post_id WordPress post ID for error attribution; false to suppress.
	 * @param array<string,string> $headers Extra per-request headers.
	 * @param int                  $timeout Request timeout in seconds. Defaults to DEFAULT_REQUEST_TIMEOUT.
	 */
	public static function call_api( string $method, string $url, string $body = '', int|false $post_id = false, array $headers = [], int $timeout = self::DEFAULT_REQUEST_TIMEOUT ): array|\WP_Error {
		$post = get_post( $post_id );

		self::delete_errors( $post_id );

		$response = wp_remote_request( $url, self::build_args( $method, $body, $headers, $timeout ) );

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $response_code ) {
			delete_option( 'beyondwords_valid_api_connection' );
		}

		if (
			$post instanceof \WP_Post
			&& \BeyondWords\Settings\Fields::INTEGRATION_REST_API === \BeyondWords\Settings\Fields::get_integration_method( $post )
			&& ( is_wp_error( $response ) || $response_code > 299 )
		) {
			$message = self::error_message_from_response( $response );
			self::save_error_message( $post_id, $message, $response_code );
		}

		return $response;
	}

	/**
	 * Build the WordPress HTTP args for a BeyondWords API call.
	 *
	 * Auth and Content-Type headers are added by `filter_http_request_args()`,
	 * not here, so they also apply to third-party calls against the API.
	 *
	 * @param string               $method  HTTP method.
	 * @param string               $body    Request body.
	 * @param array<string,string> $headers Extra per-request headers.
	 * @param int                  $timeout Request timeout in seconds.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_args( string $method, string $body = '', array $headers = [], int $timeout = self::DEFAULT_REQUEST_TIMEOUT ): array {
		return [
			'blocking' => true,
			'body'     => $body,
			'headers'  => $headers,
			'method'   => strtoupper( $method ),
			'timeout'  => $timeout,
		];
	}

	/**
	 * Build a transient key for a cached GET.
	 *
	 * Salted with the project ID + API key so changing either invalidates
	 * implicitly — no flush needed, which object-cache hosts can't do anyway.
	 *
	 * @since 7.0.0
	 *
	 * @param string $suffix Endpoint-specific key suffix.
	 */
	private static function cache_key( string $suffix ): string {
		$salt = substr(
			md5( (string) get_option( 'beyondwords_project_id', '' ) . '|' . (string) get_option( 'beyondwords_api_key', '' ) ),
			0,
			12
		);

		return 'beyondwords_api_' . $suffix . '_' . $salt;
	}

	/**
	 * GET an editor-render-path endpoint, caching both hits and failures.
	 *
	 * Failures are negative-cached for the shorter {@see CACHE_TTL_ON_ERROR} so
	 * an unreachable API is probed at most once per interval, not every render.
	 *
	 * @since 7.0.0
	 *
	 * @param string $suffix  Cache-key suffix (include any project/language id).
	 * @param string $url     Absolute endpoint URL.
	 * @param int    $timeout Request timeout in seconds.
	 *
	 * @return array<mixed>|null|false Decoded body on the fetching call; the cached
	 *                                 value ([] after a cached failure) thereafter.
	 */
	private static function cached_get( string $suffix, string $url, int $timeout = self::DEFAULT_REQUEST_TIMEOUT ): array|null|false {
		$key    = self::cache_key( $suffix );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = self::call_api( 'GET', $url, '', false, [], $timeout );
		$decoded  = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
			! is_wp_error( $response )
			&& wp_remote_retrieve_response_code( $response ) < 300
			&& is_array( $decoded )
		) {
			set_transient( $key, $decoded, self::CACHE_TTL );

			return $decoded;
		}

		set_transient( $key, [], self::CACHE_TTL_ON_ERROR );

		return $decoded;
	}

	/**
	 * Pull a human-readable error string out of a BeyondWords API response.
	 *
	 * BeyondWords returns errors in two shapes — `errors[]` (validation) and
	 * `message` (other) — so we check both and fall back to the HTTP status text.
	 */
	public static function error_message_from_response( array|\WP_Error $response ): string {
		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = wp_remote_retrieve_response_message( $response );

		if ( is_array( $body ) ) {
			if ( array_key_exists( 'errors', $body ) ) {
				$messages = [];
				foreach ( $body['errors'] as $error ) {
					$messages[] = implode( ' ', array_values( $error ) );
				}
				$message = implode( ', ', $messages );
			} elseif ( array_key_exists( 'message', $body ) ) {
				// `message` is arbitrary JSON; coerce so the `: string` return
				// type holds under strict_types.
				$message = is_string( $body['message'] )
					? $body['message']
					: (string) wp_json_encode( $body['message'] );
			}
		}

		return $message;
	}

	/**
	 * Clear any error meta keys for a post.
	 *
	 * @param int|false $post_id WordPress post ID; false is a no-op.
	 */
	public static function delete_errors( int|false $post_id ): void {
		if ( ! $post_id ) {
			return;
		}

		delete_post_meta( $post_id, 'speechkit_error_message' );
		delete_post_meta( $post_id, 'beyondwords_error_message' );
	}

	/**
	 * Persist an error message to a post for surfacing in the editor.
	 *
	 * Skipped for Magic Embed 404s because client-side fetches retry on
	 * subsequent visits — surfacing a 404 here would be misleading.
	 *
	 * @param int|false  $post_id WordPress post ID; false is a no-op.
	 * @param string     $message Error message.
	 * @param int|string $code    HTTP status (or string code).
	 */
	public static function save_error_message( int|false $post_id, string $message = '', int|string $code = 500 ): void {
		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if (
			404 === $code
			&& $post instanceof \WP_Post
			&& \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === \BeyondWords\Settings\Fields::get_integration_method( $post )
		) {
			return;
		}

		if ( ! $message ) {
			$message = sprintf(
				/* translators: %s is replaced with the support email link */
				esc_html__( 'API request error. Please contact %s.', 'speechkit' ),
				'<a href="mailto:support@beyondwords.io">support@beyondwords.io</a>'
			);
		}

		if ( ! $code ) {
			$code = 500;
		}

		update_post_meta(
			$post_id,
			'beyondwords_error_message',
			sprintf( self::ERROR_FORMAT, (string) $code, $message )
		);
	}
}
