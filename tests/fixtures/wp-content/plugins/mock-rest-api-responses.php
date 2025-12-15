<?php
/**
 * Mocks BeyondWords API responses for testing.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Mock REST API Responses
 * Description:       Mocks BeyondWords API responses for testing in CI/dev environments.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL-2.0-or-later
 */

// Early exit if not in mock mode.
if ( ! defined( 'BEYONDWORDS_MOCK_API' ) || ! BEYONDWORDS_MOCK_API ) {
	return;
}

add_filter( 'pre_http_request', 'beyondwords_mock_api_request', 10, 3 );

/**
 * Intercept HTTP requests to the BeyondWords API and return mock responses.
 *
 * @param false|array|\WP_Error $preempt     A preemptive return value of an HTTP request.
 * @param array                 $parsed_args HTTP request arguments.
 * @param string                $url         The request URL.
 *
 * @return false|array Return false to proceed with the request, or array to short-circuit.
 */
function beyondwords_mock_api_request( $preempt, $parsed_args, $url ) {
	// Get the API URL to match against.
	$api_url = defined( 'BEYONDWORDS_API_URL' )
		? BEYONDWORDS_API_URL
		: 'https://api.beyondwords.io/v1';

	// Check if this is a BeyondWords API request.
	if ( strpos( $url, $api_url ) !== 0 ) {
		return $preempt;
	}

	// Parse the endpoint from the URL (strip query params for routing).
	$endpoint_with_query = str_replace( $api_url, '', $url );
	$endpoint_with_query = ltrim( $endpoint_with_query, '/' );

	// Strip query params for route matching.
	$endpoint = strtok( $endpoint_with_query, '?' );

	$method = strtoupper( $parsed_args['method'] ?? 'GET' );

	// First check if endpoint matches a known route (return 404 if not).
	// This must happen BEFORE auth check so invalid endpoints return 404 not 401.
	if ( ! beyondwords_endpoint_exists( $endpoint, $method ) ) {
		$response = beyondwords_mock_response(
			array(
				'type'    => 'not_found',
				'message' => 'Not Found',
				'context' => array(),
			),
			404
		);
	} else {
		// Endpoint exists, now check authentication.
		$auth_response = beyondwords_check_auth( $parsed_args );
		if ( $auth_response !== true ) {
			$response = $auth_response;
		} else {
			// Get the mock response.
			$response = beyondwords_get_mock_response( $endpoint, $method, $parsed_args );
		}
	}

	/**
	 * Filter the mock API response.
	 *
	 * Use this filter in PHPUnit tests to override responses for specific test cases.
	 *
	 * @param array|null $response    The mock response array, or null for default.
	 * @param string     $endpoint    The API endpoint (e.g., 'projects/123').
	 * @param string     $method      The HTTP method (GET, POST, PUT, DELETE).
	 * @param array      $parsed_args The original request arguments.
	 */
	$response = apply_filters( 'beyondwords_mock_api_response', $response, $endpoint, $method, $parsed_args );

	return $response;
}

/**
 * Check authentication headers.
 *
 * @param array $parsed_args The request arguments.
 *
 * @return true|array True if authenticated, or error response array.
 */
function beyondwords_check_auth( $parsed_args ) {
	$headers = $parsed_args['headers'] ?? array();

	// Normalize header keys to lowercase for comparison.
	$headers_lower = array_change_key_case( $headers, CASE_LOWER );

	$api_key = $headers_lower['x-api-key'] ?? null;

	// Check if X-Api-Key is missing, empty, or invalid.
	if ( $api_key === null || $api_key === '' ) {
		return beyondwords_mock_response(
			array(
				'type'    => 'permission_error',
				'message' => 'Authentication token was not recognized.',
				'context' => array(),
			),
			401
		);
	}

	// Check if API key matches valid pattern (write_xxx).
	if ( ! preg_match( '/^write_[a-zA-Z0-9_]+$/', $api_key ) ) {
		return beyondwords_mock_response(
			array(
				'type'    => 'permission_error',
				'message' => 'Authentication token was not recognized.',
				'context' => array(),
			),
			401
		);
	}

	return true;
}

/**
 * Check if an endpoint matches a known route.
 *
 * @param string $endpoint The API endpoint.
 * @param string $method   The HTTP method.
 *
 * @return bool True if endpoint exists, false otherwise.
 */
function beyondwords_endpoint_exists( $endpoint, $method ) {
	$params    = beyondwords_parse_endpoint_params( $endpoint );
	$route_key = $method . ':' . $params['route'];

	$known_routes = array(
		'GET:projects/:projectId',
		'PUT:projects/:projectId',
		'POST:projects/:projectId/content',
		'PUT:projects/:projectId/content/:contentId',
		'DELETE:projects/:projectId/content/:contentId',
		'POST:projects/:projectId/content/batch_delete',
		'GET:projects/:projectId/player_settings',
		'PUT:projects/:projectId/player_settings',
		'GET:projects/:projectId/video_settings',
		'GET:organization/languages',
		'GET:organization/voices',
		'PUT:organization/voices/:voiceId',
	);

	return in_array( $route_key, $known_routes, true );
}

/**
 * Get the mock response for a given endpoint and method.
 *
 * @param string $endpoint    The API endpoint.
 * @param string $method      The HTTP method.
 * @param array  $parsed_args The request arguments.
 *
 * @return array The mock HTTP response.
 */
function beyondwords_get_mock_response( $endpoint, $method, $parsed_args ) {
	// Parse URL parameters from endpoint.
	$params = beyondwords_parse_endpoint_params( $endpoint );

	// Route to appropriate handler.
	$route_key = $method . ':' . $params['route'];

	switch ( $route_key ) {
		case 'GET:projects/:projectId':
			return beyondwords_mock_get_project( $params );

		case 'PUT:projects/:projectId':
			return beyondwords_mock_update_project( $params, $parsed_args );

		case 'POST:projects/:projectId/content':
			return beyondwords_mock_create_content( $params, $parsed_args );

		case 'PUT:projects/:projectId/content/:contentId':
			return beyondwords_mock_update_content( $params, $parsed_args );

		case 'DELETE:projects/:projectId/content/:contentId':
			return beyondwords_mock_delete_content( $params );

		case 'POST:projects/:projectId/content/batch_delete':
			return beyondwords_mock_batch_delete_content( $params );

		case 'GET:projects/:projectId/player_settings':
			return beyondwords_mock_get_player_settings( $params );

		case 'PUT:projects/:projectId/player_settings':
			return beyondwords_mock_update_player_settings( $params, $parsed_args );

		case 'GET:projects/:projectId/video_settings':
			return beyondwords_mock_get_video_settings( $params );

		case 'GET:organization/languages':
			return beyondwords_mock_get_languages();

		case 'GET:organization/voices':
			return beyondwords_mock_get_voices();

		case 'PUT:organization/voices/:voiceId':
			return beyondwords_mock_update_voice( $params, $parsed_args );

		default:
			// Unknown endpoint - return 404.
			return beyondwords_mock_response(
				array(
					'type'    => 'not_found',
					'message' => 'Not Found',
					'context' => array(),
				),
				404
			);
	}
}

/**
 * Parse endpoint to extract route pattern and parameters.
 *
 * @param string $endpoint The API endpoint.
 *
 * @return array Array with 'route' pattern and extracted parameters.
 */
function beyondwords_parse_endpoint_params( $endpoint ) {
	$params = array( 'route' => $endpoint );

	// Match projects/:projectId.
	if ( preg_match( '#^projects/([^/]+)$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId';
		$params['projectId'] = $matches[1];
		return $params;
	}

	// Match projects/:projectId/content.
	if ( preg_match( '#^projects/([^/]+)/content$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId/content';
		$params['projectId'] = $matches[1];
		return $params;
	}

	// Match projects/:projectId/content/batch_delete.
	if ( preg_match( '#^projects/([^/]+)/content/batch_delete$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId/content/batch_delete';
		$params['projectId'] = $matches[1];
		return $params;
	}

	// Match projects/:projectId/content/:contentId.
	if ( preg_match( '#^projects/([^/]+)/content/([^/]+)$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId/content/:contentId';
		$params['projectId'] = $matches[1];
		$params['contentId'] = $matches[2];
		return $params;
	}

	// Match projects/:projectId/player_settings.
	if ( preg_match( '#^projects/([^/]+)/player_settings$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId/player_settings';
		$params['projectId'] = $matches[1];
		return $params;
	}

	// Match projects/:projectId/video_settings.
	if ( preg_match( '#^projects/([^/]+)/video_settings$#', $endpoint, $matches ) ) {
		$params['route']     = 'projects/:projectId/video_settings';
		$params['projectId'] = $matches[1];
		return $params;
	}

	// Match organization/languages.
	if ( $endpoint === 'organization/languages' ) {
		$params['route'] = 'organization/languages';
		return $params;
	}

	// Match organization/voices.
	if ( $endpoint === 'organization/voices' ) {
		$params['route'] = 'organization/voices';
		return $params;
	}

	// Match organization/voices/:voiceId.
	if ( preg_match( '#^organization/voices/([^/]+)$#', $endpoint, $matches ) ) {
		$params['route']   = 'organization/voices/:voiceId';
		$params['voiceId'] = $matches[1];
		return $params;
	}

	return $params;
}

/**
 * Create a mock HTTP response.
 *
 * @param mixed $body        The response body (will be JSON encoded if array).
 * @param int   $status_code The HTTP status code.
 * @param array $headers     Additional headers.
 *
 * @return array The mock HTTP response in WordPress format.
 */
function beyondwords_mock_response( $body, $status_code = 200, $headers = array() ) {
	$default_headers = array(
		'content-type' => 'application/json; charset=utf-8',
	);

	return array(
		'headers'  => array_merge( $default_headers, $headers ),
		'body'     => is_array( $body ) ? wp_json_encode( $body ) : $body,
		'response' => array(
			'code'    => $status_code,
			'message' => beyondwords_get_status_message( $status_code ),
		),
		'cookies'  => array(),
		'filename' => null,
	);
}

/**
 * Get HTTP status message for code.
 *
 * @param int $code The HTTP status code.
 *
 * @return string The status message.
 */
function beyondwords_get_status_message( $code ) {
	$messages = array(
		200 => 'OK',
		204 => 'No Content',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		404 => 'Not Found',
		500 => 'Internal Server Error',
	);

	return $messages[ $code ] ?? 'Unknown';
}

/**
 * Mock: GET projects/:projectId
 */
function beyondwords_mock_get_project( $params ) {
	// Special case: project ID 401 returns an auth error (for testing invalid creds).
	if ( '401' === $params['projectId'] ) {
		return beyondwords_mock_response(
			array(
				'type'    => 'permission_error',
				'message' => 'Authentication token was not recognized.',
				'context' => array(),
			),
			401
		);
	}

	return beyondwords_mock_response(
		array(
			'id'                   => (int) $params['projectId'],
			'name'                 => 'Mock Test Project',
			'language'             => 'en_US',
			'auto_publish_enabled' => true,
			'title'                => array(
				'enabled' => true,
				'voice'   => array(
					'id'            => 2517,
					'name'          => 'Ava (Multilingual)',
					'speaking_rate' => 90,
				),
			),
			'body'                 => array(
				'enabled' => true,
				'voice'   => array(
					'id'            => 2517,
					'name'          => 'Ava (Multilingual)',
					'speaking_rate' => 95,
				),
			),
			'summary'              => array(
				'enabled' => true,
				'voice'   => array(
					'id'            => 2517,
					'name'          => 'Ava (Multilingual)',
					'speaking_rate' => 100,
				),
			),
			'time_zone'            => 'London',
			'created'              => '2020-09-05T11:52:14Z',
			'updated'              => '2023-03-30T12:14:37Z',
			'background_track'     => null,
			'language_data'        => array(
				'code'   => 'en_US',
				'name'   => 'English',
				'accent' => 'American',
			),
		)
	);
}

/**
 * Mock: PUT projects/:projectId
 */
function beyondwords_mock_update_project( $params, $parsed_args ) {
	$body = json_decode( $parsed_args['body'] ?? '{}', true ) ?: array();

	return beyondwords_mock_response(
		array_merge(
			array(
				'id'                   => (int) $params['projectId'],
				'name'                 => 'Mock Test Project',
				'language'             => 'en_US',
				'auto_publish_enabled' => true,
				'title'                => array(
					'enabled' => true,
					'voice'   => array(
						'id'            => 2517,
						'name'          => 'Ava (Multilingual)',
						'speaking_rate' => 90,
					),
				),
				'body'                 => array(
					'enabled' => true,
					'voice'   => array(
						'id'            => 2517,
						'name'          => 'Ava (Multilingual)',
						'speaking_rate' => 95,
					),
				),
				'summary'              => array(
					'enabled' => true,
					'voice'   => array(
						'id'            => 2517,
						'name'          => 'Ava (Multilingual)',
						'speaking_rate' => 100,
					),
				),
				'time_zone'            => 'London',
				'created'              => '2020-09-05T11:52:14Z',
				'updated'              => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'background_track'     => null,
				'language_data'        => array(
					'code'   => 'en_US',
					'name'   => 'English',
					'accent' => 'American',
				),
			),
			$body
		)
	);
}

/**
 * Mock: POST projects/:projectId/content
 */
function beyondwords_mock_create_content( $params, $parsed_args ) {
	$body = json_decode( $parsed_args['body'] ?? '{}', true ) ?: array();

	return beyondwords_mock_response(
		array(
			'id'                           => '9279c9e0-e0b5-4789-9040-f44478ed3e9e',
			'title'                        => $body['title'] ?? 'Title',
			'type'                         => 'auto_segment',
			'source_id'                    => $body['source_id'] ?? '90e4cbff-6382-4a88-adc5-1eb3ffa16c6d',
			'source_url'                   => $body['source_url'] ?? 'https://example.com',
			'author'                       => $body['author'] ?? 'Jane Smith',
			'image_url'                    => $body['image'] ?? 'https://example.com/image.jpg',
			'audio'                        => array(
				array(
					'id'           => 12192819,
					'content_type' => 'application/x-mpegURL',
					'url'          => 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/audio/projects/9969/podcasts/3161419/media/e8219ee2f3465d6834984f9ae607a81e.m3u8',
					'duration'     => 2685,
					'base64_file'  => null,
					'variant'      => 'article',
				),
				array(
					'id'           => 12192811,
					'content_type' => 'audio/mpeg',
					'url'          => 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/audio/projects/9969/podcasts/3161419/media/fd7108e13a7c7fee6820a1b07bb676e0_compiled.mp3',
					'duration'     => 2712,
					'base64_file'  => null,
					'variant'      => 'article',
				),
			),
			'video'                        => array(),
			'ads_enabled'                  => true,
			'is_copy'                      => false,
			'title_voice_id'               => 2517,
			'summary_voice_id'             => 2517,
			'body_voice_id'                => 2517,
			'title_enabled'                => true,
			'body_enabled'                 => true,
			'summary_enabled'              => true,
			'summary_title_enabled'        => false,
			'summarization'                => array(
				'audio' => array(),
				'video' => array(),
			),
			'background_track'             => null,
			'language'                     => $body['language'] ?? 'en_US',
			'preview_token'                => 'd9ce36ea-ddc4-4611-b60c-4f90ed0fc082',
			'status'                       => 'processed',
			'metadata'                     => array(
				'categories' => array( 'News', 'Audio' ),
			),
			'created'                      => '2022-01-02T23:59:59Z',
			'updated'                      => '2022-03-04T00:00:00Z',
			'published'                    => true,
			'publish_date'                 => '2099-12-31T23:59:59Z',
			'auto_segment_updates_enabled' => true,
			'ai_summary_updates_enabled'   => true,
			'summary'                      => $body['summary'] ?? 'Summary',
			'body'                         => '<p>Test.</p>',
			'summarization_settings'       => null,
			'video_settings'               => null,
		)
	);
}

/**
 * Mock: PUT projects/:projectId/content/:contentId
 */
function beyondwords_mock_update_content( $params, $parsed_args ) {
	$body = json_decode( $parsed_args['body'] ?? '{}', true ) ?: array();

	return beyondwords_mock_response(
		array(
			'id'                           => $params['contentId'],
			'title'                        => $body['title'] ?? 'Title',
			'type'                         => 'auto_segment',
			'source_id'                    => $body['source_id'] ?? '90e4cbff-6382-4a88-adc5-1eb3ffa16c6d',
			'source_url'                   => $body['source_url'] ?? 'https://example.com',
			'author'                       => $body['author'] ?? 'Jane Smith',
			'image_url'                    => $body['image'] ?? 'https://example.com/image.jpg',
			'audio'                        => array(
				array(
					'id'           => 12192819,
					'content_type' => 'application/x-mpegURL',
					'url'          => 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/audio/projects/9969/podcasts/3161419/media/e8219ee2f3465d6834984f9ae607a81e.m3u8',
					'duration'     => 2685,
					'base64_file'  => null,
					'variant'      => 'article',
				),
				array(
					'id'           => 12192811,
					'content_type' => 'audio/mpeg',
					'url'          => 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/audio/projects/9969/podcasts/3161419/media/fd7108e13a7c7fee6820a1b07bb676e0_compiled.mp3',
					'duration'     => 2712,
					'base64_file'  => null,
					'variant'      => 'article',
				),
			),
			'video'                        => array(),
			'ads_enabled'                  => true,
			'is_copy'                      => false,
			'title_voice_id'               => 2517,
			'summary_voice_id'             => 2517,
			'body_voice_id'                => 2517,
			'title_enabled'                => true,
			'body_enabled'                 => true,
			'summary_enabled'              => true,
			'summary_title_enabled'        => false,
			'summarization'                => array(
				'audio' => array(),
				'video' => array(),
			),
			'background_track'             => null,
			'language'                     => $body['language'] ?? 'en_US',
			'preview_token'                => 'd9ce36ea-ddc4-4611-b60c-4f90ed0fc082',
			'status'                       => 'processed',
			'metadata'                     => array(
				'categories' => array( 'News', 'Audio' ),
			),
			'created'                      => '2022-01-02T23:59:59Z',
			'updated'                      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'published'                    => true,
			'publish_date'                 => '2099-12-31T23:59:59Z',
			'auto_segment_updates_enabled' => true,
			'ai_summary_updates_enabled'   => true,
			'summary'                      => $body['summary'] ?? 'Summary',
			'body'                         => '<p>Test.</p>',
			'summarization_settings'       => null,
			'video_settings'               => null,
		)
	);
}

/**
 * Mock: DELETE projects/:projectId/content/:contentId
 */
function beyondwords_mock_delete_content( $params ) {
	return beyondwords_mock_response( '', 204 );
}

/**
 * Mock: POST projects/:projectId/content/batch_delete
 */
function beyondwords_mock_batch_delete_content( $params ) {
	return beyondwords_mock_response( '', 204 );
}

/**
 * Mock: GET projects/:projectId/player_settings
 */
function beyondwords_mock_get_player_settings( $params ) {
	return beyondwords_mock_response(
		array(
			'enabled'                  => true,
			'player_version'           => '1',
			'player_style'             => 'standard',
			'player_title'             => 'G B 7118 Amy, Brian',
			'call_to_action'           => 'Listen to this article',
			'image_url'                => '',
			'theme'                    => 'light',
			'dark_theme'               => array(
				'background_color' => 'transparent',
				'icon_color'       => '#fff',
				'text_color'       => '#fff',
				'highlight_color'  => '#444',
			),
			'light_theme'              => array(
				'background_color' => '#f5f5f5',
				'icon_color'       => '#000',
				'text_color'       => '#111',
				'highlight_color'  => '#eee',
			),
			'video_theme'              => array(
				'background_color' => '#f5f5f5',
				'icon_color'       => '#000',
				'text_color'       => '#111',
			),
			'title_enabled'            => false,
			'image_enabled'            => true,
			'persistent_ad_image'      => false,
			'widget_style'             => 'standard',
			'widget_position'          => 'auto',
			'segment_playback_enabled' => true,
			'skip_button_style'        => 'auto',
			'intros_outros'            => array(),
			'intro_url'                => null,
			'outro_url'                => null,
			'intro_outro_enabled'      => false,
			'paywall_type'             => 'none',
			'paywall_url'              => null,
			'download_button_enabled'  => false,
			'share_button_enabled'     => false,
			'voice_icon_enabled'       => false,
			'logo_icon_enabled'        => true,
			'analytics_enabled'        => true,
			'analytics_uuid_enabled'   => true,
			'analytics_url'            => 'https://metrics.beyondwords.io/events',
			'analytics_id'             => 3814,
			'analytics_tag_enabled'    => false,
			'analytics_tag'            => null,
			'analytics_custom_url'     => null,
			'updated'                  => '2023-06-22T12:16:11Z',
		)
	);
}

/**
 * Mock: PUT projects/:projectId/player_settings
 */
function beyondwords_mock_update_player_settings( $params, $parsed_args ) {
	$body = json_decode( $parsed_args['body'] ?? '{}', true ) ?: array();

	$defaults = array(
		'enabled'                  => true,
		'player_version'           => '1',
		'player_style'             => 'standard',
		'player_title'             => 'G B 7118 Amy, Brian',
		'call_to_action'           => 'Listen to this article',
		'image_url'                => '',
		'theme'                    => 'light',
		'dark_theme'               => array(
			'background_color' => 'transparent',
			'icon_color'       => '#fff',
			'text_color'       => '#fff',
			'highlight_color'  => '#444',
		),
		'light_theme'              => array(
			'background_color' => '#f5f5f5',
			'icon_color'       => '#000',
			'text_color'       => '#111',
			'highlight_color'  => '#eee',
		),
		'video_theme'              => array(
			'background_color' => '#f5f5f5',
			'icon_color'       => '#000',
			'text_color'       => '#111',
		),
		'title_enabled'            => false,
		'image_enabled'            => true,
		'persistent_ad_image'      => false,
		'widget_style'             => 'standard',
		'widget_position'          => 'auto',
		'segment_playback_enabled' => true,
		'skip_button_style'        => 'auto',
		'intros_outros'            => array(),
		'intro_url'                => null,
		'outro_url'                => null,
		'intro_outro_enabled'      => false,
		'paywall_type'             => 'none',
		'paywall_url'              => null,
		'download_button_enabled'  => false,
		'share_button_enabled'     => false,
		'voice_icon_enabled'       => false,
		'logo_icon_enabled'        => true,
		'analytics_enabled'        => true,
		'analytics_uuid_enabled'   => true,
		'analytics_url'            => 'https://metrics.beyondwords.io/events',
		'analytics_id'             => 3814,
		'analytics_tag_enabled'    => false,
		'analytics_tag'            => null,
		'analytics_custom_url'     => null,
		'updated'                  => gmdate( 'Y-m-d\TH:i:s\Z' ),
	);

	return beyondwords_mock_response( array_merge( $defaults, $body ) );
}

/**
 * Mock: GET projects/:projectId/video_settings
 */
function beyondwords_mock_get_video_settings( $params ) {
	return beyondwords_mock_response(
		array(
			'enabled'                  => true,
			'logo_image_url'           => null,
			'logo_image_position'      => 'top-right',
			'background_color'         => 'white',
			'text_background_color'    => 'rgba(255, 255, 255, 0.88)',
			'text_color'               => 'black',
			'text_highlight_color'     => 'linear-gradient(to right, #933AFB, #FB3A41)',
			'waveform_color'           => 'linear-gradient(to right, #933AFB, #FB3A41)',
			'content_image_enabled'    => true,
			'image_extraction_enabled' => true,
			'pan_and_zoom_enabled'     => true,
		)
	);
}

/**
 * Mock: GET organization/languages
 *
 * Returns an array of 148 languages loaded from the mock-api-languages.json fixture file.
 */
function beyondwords_mock_get_languages() {
	// Load languages from fixture file.
	$fixture_file = dirname( dirname( __DIR__ ) ) . '/mock-api-languages.json';

	if ( file_exists( $fixture_file ) ) {
		$languages = json_decode( file_get_contents( $fixture_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	} else {
		// Fallback for PHPUnit tests where the fixture path may be different.
		$alt_fixture_file = dirname( __DIR__ ) . '/mock-api-languages.json';
		if ( file_exists( $alt_fixture_file ) ) {
			$languages = json_decode( file_get_contents( $alt_fixture_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} else {
			// Ultimate fallback - return minimal set for tests.
			$languages = array(
				array(
					'code'           => 'en_US',
					'name'           => 'English',
					'accent'         => 'American',
					'id'             => 58,
					'created'        => '2022-01-25T07:40:27Z',
					'default_voices' => array(
						'title'   => array( 'id' => 2517, 'name' => 'Ava (Multilingual)', 'speaking_rate' => 100 ),
						'body'    => array( 'id' => 2517, 'name' => 'Ava (Multilingual)', 'speaking_rate' => 100 ),
						'summary' => array( 'id' => 2517, 'name' => 'Ava (Multilingual)', 'speaking_rate' => 100 ),
					),
				),
			);
		}
	}

	return beyondwords_mock_response( $languages );
}

/**
 * Mock: GET organization/voices
 *
 * Note: Each voice has a 'language' key that is an object with 'code' property,
 * not just a string.
 */
function beyondwords_mock_get_voices() {
	return beyondwords_mock_response(
		array(
			array(
				'id'                  => 3555,
				'name'                => 'Ada (Multilingual)',
				'speaking_rate'       => 100,
				'language'            => 'en_US',
				'languages'           => array(
					array(
						'code'   => 'en_US',
						'name'   => 'English',
						'accent' => 'American',
					),
					array(
						'code'   => 'en_GB',
						'name'   => 'English',
						'accent' => 'British',
					),
					array(
						'code'   => 'cy_GB',
						'name'   => 'Welsh',
						'accent' => 'Welsh',
					),
				),
				'created'             => '2023-03-01T08:31:11Z',
				'updated'             => '2023-03-01T08:31:12Z',
				'secondary_languages' => array( 'en_US', 'en_GB', 'cy_GB' ),
			),
			array(
				'id'                  => 2517,
				'name'                => 'Ava (Multilingual)',
				'speaking_rate'       => 100,
				'language'            => 'en_US',
				'languages'           => array(
					array(
						'code'   => 'en_US',
						'name'   => 'English',
						'accent' => 'American',
					),
					array(
						'code'   => 'en_GB',
						'name'   => 'English',
						'accent' => 'British',
					),
					array(
						'code'   => 'cy_GB',
						'name'   => 'Welsh',
						'accent' => 'Welsh',
					),
				),
				'created'             => '2023-03-01T08:31:11Z',
				'updated'             => '2023-03-01T08:31:12Z',
				'secondary_languages' => array( 'en_US', 'en_GB', 'cy_GB' ),
			),
			array(
				'id'                  => 3558,
				'name'                => 'Ollie (Multilingual)',
				'speaking_rate'       => 100,
				'language'            => 'en_US',
				'languages'           => array(
					array(
						'code'   => 'en_US',
						'name'   => 'English',
						'accent' => 'American',
					),
					array(
						'code'   => 'en_GB',
						'name'   => 'English',
						'accent' => 'British',
					),
					array(
						'code'   => 'cy_GB',
						'name'   => 'Welsh',
						'accent' => 'Welsh',
					),
				),
				'created'             => '2023-03-01T08:31:11Z',
				'updated'             => '2023-03-01T08:31:12Z',
				'secondary_languages' => array( 'en_US', 'en_GB', 'cy_GB' ),
			),
		)
	);
}

/**
 * Mock: PUT organization/voices/:voiceId
 */
function beyondwords_mock_update_voice( $params, $parsed_args ) {
	$body = json_decode( $parsed_args['body'] ?? '{}', true ) ?: array();

	return beyondwords_mock_response(
		array_merge(
			array(
				'id'                  => (int) $params['voiceId'],
				'name'                => 'Ada (Multilingual)',
				'speaking_rate'       => 100,
				'language'            => 'en_US',
				'languages'           => array(
					array(
						'code'   => 'en_US',
						'name'   => 'English',
						'accent' => 'American',
					),
					array(
						'code'   => 'en_GB',
						'name'   => 'English',
						'accent' => 'British',
					),
					array(
						'code'   => 'cy_GB',
						'name'   => 'Welsh',
						'accent' => 'Welsh',
					),
				),
				'created'             => '2023-03-01T08:31:11Z',
				'updated'             => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'secondary_languages' => array( 'en_US', 'en_GB', 'cy_GB' ),
			),
			$body
		)
	);
}
