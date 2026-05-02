<?php
/**
 * BeyondWords REST API client.
 *
 * Thin wrapper around `wp_remote_request()` that pre-fills auth headers,
 * normalises errors into post meta for the editor UI, and exposes one
 * method per endpoint we touch.
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * BeyondWords API client.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ApiClient {

	/**
	 * Format used for the value stored in `beyondwords_error_message` post meta.
	 *
	 * Keeping the HTTP status as the prefix lets `Core::update_or_recreate_audio()`
	 * recognise 404s by string-matching `#404:` without parsing the body.
	 */
	const ERROR_FORMAT = '#%s: %s';

	/**
	 * GET /projects/:project/content/:content_id
	 *
	 * Falls back to the global `beyondwords_project_id` option when no project
	 * ID is supplied.
	 *
	 * @param string          $content_id BeyondWords content ID.
	 * @param int|string|null $project_id Optional project ID override.
	 *
	 * @return array<mixed>|false Response array, or false when project/content ID is missing.
	 */
	public static function get_content( int|string $content_id, int|string|null $project_id = null ): array|false {
		if ( ! $project_id ) {
			$project_id = get_option( 'beyondwords_project_id' );
		}

		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url     = sprintf( '%s/projects/%d/content/%s', Environment::get_api_url(), $project_id, $content_id );
		$request = new Request( 'GET', $url );

		return self::call_api( $request );
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
		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content', Environment::get_api_url(), $project_id );
		$body     = \BeyondWords\Post\PostContentUtils::get_content_params( $post_id );
		$request  = new Request( 'POST', $url, $body );
		$response = self::call_api( $request, $post_id );

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
	 * @return array<mixed>|null|false
	 */
	public static function update_audio( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );
		$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post_id, true );

		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content/%s', Environment::get_api_url(), $project_id, $content_id );
		$body     = \BeyondWords\Post\PostContentUtils::get_content_params( $post_id );
		$request  = new Request( 'PUT', $url, $body );
		$response = self::call_api( $request, $post_id );

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
		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );
		$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post_id, true );

		if ( ! $project_id || ! $content_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/content/%s', Environment::get_api_url(), $project_id, $content_id );
		$request  = new Request( 'DELETE', $url );
		$response = self::call_api( $request, $post_id );

		if ( 204 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * POST /projects/:project/content/batch_delete
	 *
	 * Accepts a heterogeneous list of WordPress post IDs, groups them by
	 * BeyondWords project, and refuses cross-project batches (the API only
	 * supports one project per request).
	 *
	 * @param int[] $post_ids WordPress post IDs.
	 *
	 * @return int[]|false Updated post IDs on success, empty array for non-OK responses.
	 *
	 * @throws \Exception When no posts have BeyondWords data, or multiple projects are mixed.
	 */
	public static function batch_delete_audio( array $post_ids ): array|false {
		$content_ids      = array();
		$updated_post_ids = array();

		foreach ( $post_ids as $post_id ) {
			$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );
			if ( ! $project_id ) {
				continue;
			}

			$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post_id );
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
		$url        = sprintf( '%s/projects/%d/content/batch_delete', Environment::get_api_url(), $project_id );
		$body       = (string) wp_json_encode( array( 'ids' => $content_ids[ $project_id ] ) );
		$request    = new Request( 'POST', $url, $body );

		$args = array(
			'blocking' => true,
			'body'     => $request->get_body(),
			'headers'  => $request->get_headers(),
			'method'   => $request->get_method(),
			// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'timeout'  => 30,
		);

		$response = wp_remote_request( $request->get_url(), $args );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// 2xx means the API accepted the batch — return the IDs we sent so the
		// caller can clear local meta. Anything else: refuse to clear meta so
		// the operator can retry.
		return $response_code <= 299 ? $updated_post_ids : array();
	}

	/**
	 * GET /projects/:project/player/by_source_id/:post_id
	 *
	 * Magic Embed (client-side) bootstrap: tells BeyondWords to look up — or
	 * create — content for the given source URL, returning the player blob.
	 *
	 * @param int $post_id WordPress post ID used as the source ID.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_player_by_source_id( int $post_id ): array|null|false {
		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );

		if ( ! $project_id ) {
			return false;
		}

		$url     = sprintf( '%s/projects/%d/player/by_source_id/%d', Environment::get_api_url(), $project_id, $post_id );
		$request = new Request( 'GET', $url );
		$request->add_headers(
			array(
				'X-Import'  => 'true',
				'X-Referer' => esc_url( get_permalink( $post_id ) ),
			)
		);

		$response = self::call_api( $request, $post_id );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * GET /organization/languages
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_languages(): array|null|false {
		$url      = sprintf( '%s/organization/languages', Environment::get_api_url() );
		$request  = new Request( 'GET', $url );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
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
			Environment::get_api_url(),
			rawurlencode( strval( $language_code ) )
		);

		$request  = new Request( 'GET', $url );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Look up one voice by ID by listing all voices for a language.
	 *
	 * The API doesn't expose `/voices/:id`, so we fetch the list and filter.
	 *
	 * @param int               $voice_id      Voice ID.
	 * @param int|string|false  $language_code Language code (required — no global fallback as of 7.0.0).
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
	 * PUT /organization/voices/:id
	 *
	 * @param int                  $voice_id Voice ID.
	 * @param array<string,mixed>  $settings Voice settings to apply.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function update_voice( int $voice_id, array $settings ): array|null|false {
		if ( empty( $voice_id ) ) {
			return false;
		}

		$url      = sprintf( '%s/organization/voices/%d', Environment::get_api_url(), $voice_id );
		$body     = (string) wp_json_encode( $settings );
		$request  = new Request( 'PUT', $url, $body );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * GET /projects/:id
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_project(): array|null|false {
		$project_id = get_option( 'beyondwords_project_id' );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d', Environment::get_api_url(), $project_id );
		$request  = new Request( 'GET', $url );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * PUT /projects/:id
	 *
	 * @param array<string,mixed> $settings Project settings to apply.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function update_project( array $settings ): array|null|false {
		$project_id = get_option( 'beyondwords_project_id' );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d', Environment::get_api_url(), $project_id );
		$body     = (string) wp_json_encode( $settings );
		$request  = new Request( 'PUT', $url, $body );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * GET /projects/:id/player_settings
	 *
	 * @return array<mixed>|null|false
	 */
	public static function get_player_settings(): array|null|false {
		$project_id = get_option( 'beyondwords_project_id' );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/player_settings', Environment::get_api_url(), $project_id );
		$request  = new Request( 'GET', $url );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * PUT /projects/:id/player_settings
	 *
	 * @param array<string,mixed> $settings Player settings to apply.
	 *
	 * @return array<mixed>|null|false
	 */
	public static function update_player_settings( array $settings ): array|null|false {
		$project_id = get_option( 'beyondwords_project_id' );

		if ( ! $project_id ) {
			return false;
		}

		$url      = sprintf( '%s/projects/%d/player_settings', Environment::get_api_url(), $project_id );
		$body     = (string) wp_json_encode( $settings );
		$request  = new Request( 'PUT', $url, $body );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
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

		$url      = sprintf( '%s/projects/%d/video_settings', Environment::get_api_url(), (int) $project_id );
		$request  = new Request( 'GET', $url );
		$response = self::call_api( $request );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Make the API call, normalising errors into post meta when a post is
	 * supplied.
	 *
	 * For 401 responses we also clear the cached `beyondwords_valid_api_connection`
	 * so the settings page re-runs validation.
	 *
	 * Magic Embed (client-side) errors are not persisted because the SDK
	 * regenerates content lazily — surfacing those errors in the editor would
	 * be misleading.
	 *
	 * @param Request   $request BeyondWords request object.
	 * @param int|false $post_id WordPress post ID for error attribution; false to suppress.
	 */
	public static function call_api( Request $request, int|false $post_id = false ): array|\WP_Error {
		$post = get_post( $post_id );

		self::delete_errors( $post_id );

		$args     = self::build_request_args( $request );
		$response = wp_remote_request( $request->get_url(), $args );

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
	 * Build the WordPress HTTP args from a `Request`.
	 *
	 * @return array<string,mixed>
	 */
	public static function build_request_args( Request $request ): array {
		return array(
			'blocking' => true,
			'body'     => $request->get_body(),
			'headers'  => $request->get_headers(),
			'method'   => $request->get_method(),
			// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'timeout'  => 30,
		);
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
				$messages = array();
				foreach ( $body['errors'] as $error ) {
					$messages[] = implode( ' ', array_values( $error ) );
				}
				$message = implode( ', ', $messages );
			} elseif ( array_key_exists( 'message', $body ) ) {
				$message = $body['message'];
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
