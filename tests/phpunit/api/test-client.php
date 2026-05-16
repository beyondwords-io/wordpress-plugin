<?php

declare(strict_types=1);

use BeyondWords\Api\Client;
use BeyondWords\Core\Urls;

class ClientTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Register the http_request_args filter that production runs from
        // Plugin::init() — without it API calls go out without an auth header.
        Client::init();

        // Clear existing admin notices, so we can test notices in isolation
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        remove_filter('http_request_args', [Client::class, 'filter_http_request_args'], 10);

        // Clear existing admin notices, so we can test notices in isolation
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_http_request_args_filter()
    {
        $this->assertEquals(10, has_filter('http_request_args', [Client::class, 'filter_http_request_args']));
    }

    /**
     * @test
     */
    public function filter_http_request_args_skips_non_beyondwords_urls()
    {
        update_option('beyondwords_api_key', 'SECRET-API-KEY');

        $args = Client::filter_http_request_args(
            ['method' => 'GET', 'headers' => ['Existing' => 'value']],
            'https://example.com/some/other/api'
        );

        $this->assertSame(['Existing' => 'value'], $args['headers']);
        $this->assertArrayNotHasKey('X-Api-Key', $args['headers']);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function filter_http_request_args_injects_api_key_for_beyondwords_urls()
    {
        update_option('beyondwords_api_key', 'SECRET-API-KEY');

        $args = Client::filter_http_request_args(
            ['method' => 'GET', 'headers' => []],
            Urls::get_api_url() . '/projects/1234'
        );

        $this->assertSame('SECRET-API-KEY', $args['headers']['X-Api-Key']);
        $this->assertArrayNotHasKey('Content-Type', $args['headers']);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function filter_http_request_args_injects_content_type_for_write_methods()
    {
        update_option('beyondwords_api_key', 'SECRET-API-KEY');

        foreach (['POST', 'PUT', 'DELETE'] as $method) {
            $args = Client::filter_http_request_args(
                ['method' => $method, 'headers' => []],
                Urls::get_api_url() . '/projects/1234/content'
            );

            $this->assertSame('application/json', $args['headers']['Content-Type'], "Content-Type should be set for {$method}");
        }

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function filter_http_request_args_respects_caller_supplied_headers()
    {
        update_option('beyondwords_api_key', 'SECRET-API-KEY');

        $args = Client::filter_http_request_args(
            [
                'method'  => 'POST',
                'headers' => [
                    'X-Api-Key'    => 'CALLER-OVERRIDE',
                    'Content-Type' => 'text/html',
                ],
            ],
            Urls::get_api_url() . '/projects/1234/content'
        );

        $this->assertSame('CALLER-OVERRIDE', $args['headers']['X-Api-Key']);
        $this->assertSame('text/html', $args['headers']['Content-Type']);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function create_audio_without_project_id_setting()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::createAudioWithoutProjectIdSetting',
        ]);

        $response = Client::create_audio($postId);

        $this->assertFalse($response);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     * @group current
     */
    public function create_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::createAudio',
        ]);

        $response = Client::create_audio($postId);

        $this->assertIsArray($response);
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID,  $response['id']);
        $this->assertSame('processed',  $response['status']);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function update_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::updateAudio',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $response = Client::update_audio($postId);

        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID,  $response['id']);
        $this->assertSame('processed',  $response['status']);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function delete_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::deleteAudio',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $response = Client::delete_audio($postId);

        // Response body is empty for 201 Deleted responses
        $this->assertNull($response);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function batch_delete_audio()
    {
        $numPosts = 20;

        $postIds = self::factory()->post->create_many($numPosts, [
            'post_title' => 'ClientTest::batchDeleteAudio %d',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $deleted = Client::batch_delete_audio($postIds);
        $this->assertEquals([], $deleted);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $deleted = Client::batch_delete_audio($postIds);

        $this->assertEquals($deleted, array_values($postIds));

        foreach ($deleted as $postId) {
            wp_delete_post($postId, true);
        }

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group voices
     */
    public function get_languages()
    {
        $response = Client::get_languages();
        $this->assertSame('Authentication token was not recognized.', $response['message']);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = Client::get_languages();

        $this->assertSame('en_US', $response[32]['code']);
        $this->assertSame('en_GB', $response[34]['code']);
        $this->assertSame('cy_GB', $response[91]['code']);

        $this->assertSame(58, $response[32]['id']);
        $this->assertSame(50, $response[34]['id']);
        $this->assertSame(39, $response[91]['id']);

        $this->assertSame('English', $response[32]['name']);
        $this->assertSame('English', $response[34]['name']);
        $this->assertSame('Welsh',   $response[91]['name']);

        $this->assertSame('American', $response[32]['accent']);
        $this->assertSame('British',  $response[34]['accent']);
        $this->assertSame('Welsh',  $response[91]['accent']);

        $this->assertSame(2517, $response[32]['default_voices']['title']['id']);
        $this->assertSame(3558, $response[34]['default_voices']['title']['id']);
        $this->assertSame(3555, $response[91]['default_voices']['title']['id']);

        $this->assertSame('Ava (Multilingual)',  $response[32]['default_voices']['title']['name']);
        $this->assertSame('Ollie (Multilingual)',  $response[34]['default_voices']['title']['name']);
        $this->assertSame('Ada (Multilingual)', $response[91]['default_voices']['title']['name']);

        $this->assertSame(100,  $response[32]['default_voices']['title']['speaking_rate']);
        $this->assertSame(100,  $response[34]['default_voices']['title']['speaking_rate']);
        $this->assertSame(100, $response[91]['default_voices']['title']['speaking_rate']);

        $this->assertSame(2517, $response[32]['default_voices']['body']['id']);
        $this->assertSame(3558, $response[34]['default_voices']['body']['id']);
        $this->assertSame(3555, $response[91]['default_voices']['body']['id']);

        $this->assertSame('Ava (Multilingual)',  $response[32]['default_voices']['body']['name']);
        $this->assertSame('Ollie (Multilingual)',  $response[34]['default_voices']['body']['name']);
        $this->assertSame('Ada (Multilingual)', $response[91]['default_voices']['body']['name']);

        $this->assertSame(100,  $response[32]['default_voices']['body']['speaking_rate']);
        $this->assertSame(100, $response[34]['default_voices']['body']['speaking_rate']);
        $this->assertSame(100, $response[91]['default_voices']['body']['speaking_rate']);

        $this->assertSame(2517, $response[32]['default_voices']['summary']['id']);
        $this->assertSame(3558, $response[34]['default_voices']['summary']['id']);
        $this->assertSame(3555, $response[91]['default_voices']['summary']['id']);

        $this->assertSame('Ava (Multilingual)',  $response[32]['default_voices']['summary']['name']);
        $this->assertSame('Ollie (Multilingual)',  $response[34]['default_voices']['summary']['name']);
        $this->assertSame('Ada (Multilingual)', $response[91]['default_voices']['summary']['name']);

        $this->assertSame(100, $response[32]['default_voices']['summary']['speaking_rate']);
        $this->assertSame(100, $response[34]['default_voices']['summary']['speaking_rate']);
        $this->assertSame(100, $response[91]['default_voices']['summary']['speaking_rate']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group voices
     */
    public function get_voices()
    {
        $response = Client::get_voices('en_US');
        $this->assertSame('Authentication token was not recognized.', $response['message']);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = Client::get_voices('en_US');

        $this->assertSame(3555, $response[0]['id']);
        $this->assertSame(2517, $response[1]['id']);
        $this->assertSame(3558, $response[2]['id']);

        $this->assertSame('Ada (Multilingual)', $response[0]['name']);
        $this->assertSame('Ava (Multilingual)', $response[1]['name']);
        $this->assertSame('Ollie (Multilingual)', $response[2]['name']);

        $this->assertSame(array('code' => 'en_US'), $response[0]['language']);
        $this->assertSame(array('code' => 'en_US'), $response[1]['language']);
        $this->assertSame(array('code' => 'en_US'), $response[2]['language']);

        $this->assertSame(100, $response[0]['speaking_rate']);
        $this->assertSame(100, $response[1]['speaking_rate']);
        $this->assertSame(100, $response[2]['speaking_rate']);
    }

    /**
     * @test
     * @group settings
     */
    public function get_project()
    {
        $response = Client::get_project();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = Client::get_project();

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('language', $response);
        $this->assertArrayHasKey('auto_publish_enabled', $response);
        $this->assertArrayHasKey('time_zone', $response);
        $this->assertArrayHasKey('created', $response);
        $this->assertArrayHasKey('updated', $response);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group settings
     */
    public function get_player_settings()
    {
        $response = Client::get_player_settings();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = Client::get_player_settings();

        $this->assertArrayHasKey('enabled', $response);
        $this->assertArrayHasKey('player_version', $response);
        $this->assertArrayHasKey('player_style', $response);
        $this->assertArrayHasKey('player_title', $response);
        $this->assertArrayHasKey('call_to_action', $response);
        $this->assertArrayHasKey('image_url', $response);
        $this->assertArrayHasKey('theme', $response);
        $this->assertArrayHasKey('updated', $response);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group settings
     */
    public function get_video_settings()
    {
        $response = Client::get_video_settings();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = Client::get_video_settings();

        $this->assertArrayHasKey('enabled', $response);
        $this->assertArrayHasKey('logo_image_url', $response);
        $this->assertArrayHasKey('logo_image_position', $response);
        $this->assertArrayHasKey('background_color', $response);
        $this->assertArrayHasKey('text_background_color', $response);
        $this->assertArrayHasKey('text_color', $response);
        $this->assertArrayHasKey('text_highlight_color', $response);
        $this->assertArrayHasKey('waveform_color', $response);
        $this->assertArrayHasKey('content_image_enabled', $response);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * 401 when the option-supplied API key is invalid (covers both the
     * "missing" and "wrong" cases — the BeyondWords API treats them the same).
     */
    public function call_api_with_invalid_api_key()
    {
        update_option('beyondwords_api_key', 'AN INVALID API KEY');

        $postId = $this->factory->post->create([
            'post_title' => 'ClientTest::callApiWithInvalidApiKey',
        ]);

        $url = Urls::get_api_url() . '/projects/1234/content';

        $response = Client::call_api('POST', $url, '{"body":"Hello"}', $postId);

        $this->assertSame(401, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $error = sprintf(Client::ERROR_FORMAT, 401, 'Authentication token was not recognized.');
        $this->assertSame($error, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     *
     * Caller-supplied Content-Type wins over the filter-injected default.
     */
    public function call_api_with_invalid_content_type()
    {
        update_option('beyondwords_api_key', 'AN INVALID API KEY');

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::callApiWithInvalidContentTypeHeader',
        ]);

        $url = Urls::get_api_url() . '/projects/1234/content';

        $response = Client::call_api('POST', $url, '{"body":"Hello"}', $postId, ['Content-Type' => 'text/html']);

        $this->assertSame(401, wp_remote_retrieve_response_code($response));

        $error = sprintf(Client::ERROR_FORMAT, 401, 'Authentication token was not recognized.');
        $this->assertSame($error, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     *
     * Invalid URL which should get error message using is_wp_error().
     */
    public function call_api_with_invalid_endpoint()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::callApiWithInvalidEndpoint',
        ]);

        $url = Urls::get_api_url() . '/foo/1234/bar';

        $response = Client::call_api('POST', $url, '{"body":"Hello"}', $postId);

        $this->assertSame(404, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $this->assertSame('#404: Not Found', get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     *
     * Invalid URL which should get error message using is_wp_error().
     */
    public function call_api_with_invalid_domain()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::callApiWithInvalidDomain',
        ]);

        $response = Client::call_api('POST', 'http://localhost:5678/foo', '{"body":"Hello"}', $postId);

        $this->assertTrue(is_a($response, 'WP_Error'));

        $errorMessage = get_post_meta($postId, 'beyondwords_error_message', true);

        $this->assertStringStartsWith('#500:', $errorMessage);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @dataProvider save_error_message_provider
     */
    public function save_error_message(string $message, int $code, string $expect)
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::error::' . $code,
        ]);

        Client::save_error_message($postId, $message, $code);

        $this->assertEquals($expect, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    public function save_error_message_provider()
    {
        return [
            '401' => [
                'message' => 'Unauthorized',
                'code'    => 401,
                'expect'  => '#401: Unauthorized',
            ],
            '403' => [
                'message' => 'Forbidden',
                'code'    => 403,
                'expect'  => '#403: Forbidden',
            ],
            '500' => [
                'message' => 'Server error',
                'code'    => 500,
                'expect'  => '#500: Server error',
            ],
        ];
    }

    /**
     * @test
     *
     * Test that 404 errors are saved for REST_API posts even when global setting is CLIENT_SIDE.
     * This tests the bug where saveErrorMessage checked the global option instead of post meta.
     */
    public function save_error_message404_for_rest_api_post_when_global_is_client_side()
    {
        // Set global integration method to CLIENT_SIDE
        update_option('beyondwords_integration_method', 'client-side');

        // Create a post with REST_API integration method in post meta
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::saveErrorMessage404ForRestApiPostWhenGlobalIsClientSide',
            'meta_input' => [
                'beyondwords_integration_method' => 'rest-api',
            ],
        ]);

        // Call saveErrorMessage with a 404 error
        Client::save_error_message($postId, 'Not Found', 404);

        // The error SHOULD be saved because the post uses REST_API integration
        // (even though the global setting is CLIENT_SIDE)
        $error = get_post_meta($postId, 'beyondwords_error_message', true);
        $this->assertEquals('#404: Not Found', $error);

        wp_delete_post($postId, true);
        delete_option('beyondwords_integration_method');
    }

    /**
     * @test
     *
     * Test that 404 errors are NOT saved for CLIENT_SIDE posts.
     */
    public function save_error_message404_not_saved_for_client_side_post()
    {
        // Set global integration method to REST_API
        update_option('beyondwords_integration_method', 'rest-api');

        // Create a post with CLIENT_SIDE integration method in post meta
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::saveErrorMessage404NotSavedForClientSidePost',
            'meta_input' => [
                'beyondwords_integration_method' => 'client-side',
            ],
        ]);

        // Call saveErrorMessage with a 404 error
        Client::save_error_message($postId, 'Not Found', 404);

        // The error should NOT be saved because the post uses CLIENT_SIDE integration
        $error = get_post_meta($postId, 'beyondwords_error_message', true);
        $this->assertEmpty($error);

        wp_delete_post($postId, true);
        delete_option('beyondwords_integration_method');
    }

    /**
     * @test
     *
     * Test legacy posts (no integration method meta) with global=REST_API.
     * 404 errors SHOULD be saved because legacy posts default to REST_API.
     */
    public function save_error_message404_for_legacy_post_when_global_is_rest_api()
    {
        // Set global integration method to REST_API
        update_option('beyondwords_integration_method', 'rest-api');

        // Create a legacy post with NO integration method meta (simulating pre-v6.0 post)
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::saveErrorMessage404ForLegacyPostWhenGlobalIsRestApi',
            'meta_input' => [
                'beyondwords_content_id' => 'legacy-content-123', // Has content from REST API
                // Note: NO beyondwords_integration_method meta
            ],
        ]);

        // Call saveErrorMessage with a 404 error
        Client::save_error_message($postId, 'Not Found', 404);

        // The error SHOULD be saved because legacy posts fall back to global (REST_API)
        $error = get_post_meta($postId, 'beyondwords_error_message', true);
        $this->assertEquals('#404: Not Found', $error);

        wp_delete_post($postId, true);
        delete_option('beyondwords_integration_method');
    }

    /**
     * @test
     *
     * Test legacy posts (no integration method meta) with global=CLIENT_SIDE.
     * 404 errors should NOT be saved because the post falls back to global CLIENT_SIDE.
     */
    public function save_error_message404_for_legacy_post_when_global_is_client_side()
    {
        // Set global integration method to CLIENT_SIDE
        update_option('beyondwords_integration_method', 'client-side');

        // Create a legacy post with NO integration method meta (simulating pre-v6.0 post)
        $postId = self::factory()->post->create([
            'post_title' => 'ClientTest::saveErrorMessage404ForLegacyPostWhenGlobalIsClientSide',
            'meta_input' => [
                'beyondwords_content_id' => 'legacy-content-123', // Has content from REST API
                // Note: NO beyondwords_integration_method meta
            ],
        ]);

        // Call saveErrorMessage with a 404 error
        Client::save_error_message($postId, 'Not Found', 404);

        // The error should NOT be saved because legacy posts fall back to global (CLIENT_SIDE)
        $error = get_post_meta($postId, 'beyondwords_error_message', true);
        $this->assertEmpty($error);

        wp_delete_post($postId, true);
        delete_option('beyondwords_integration_method');
    }

    /**
     * @test
     */
    public function error_message_from_response()
    {
        $response = [
            'body' => wp_json_encode(['message' => 'Foo'])
        ];

        $result = Client::error_message_from_response($response);

        $this->assertEquals('Foo', $result);

        $response = [
            'body' => wp_json_encode(
                ['errors' => [
                [
                    'code' => 500,
                    'message' => 'Foo',
                ],
                [
                    'code' => 501,
                    'message' => 'Bar',
                ],
            ]])
        ];

        $result = Client::error_message_from_response($response);

        $this->assertEquals('500 Foo, 501 Bar', $result);
    }

    /**
     * @test
     */
    public function get_content_returns_false_without_project_id()
    {
        delete_option('beyondwords_project_id');

        $this->assertFalse(Client::get_content('abc-123'));
    }

    /**
     * @test
     */
    public function get_content_returns_false_without_content_id()
    {
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->assertFalse(Client::get_content(''));

        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function get_content_calls_expected_url()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $captured_url = null;
        // Priority 1 ensures we capture the URL before the mock plugin (priority 10).
        $filter = function ($preempt, $args, $url) use (&$captured_url) {
            $captured_url = $url;
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        $result = Client::get_content(BEYONDWORDS_TESTS_CONTENT_ID);

        remove_filter('pre_http_request', $filter, 1);

        $this->assertIsArray($result);
        $this->assertStringContainsString(
            '/projects/' . BEYONDWORDS_TESTS_PROJECT_ID . '/content/' . BEYONDWORDS_TESTS_CONTENT_ID,
            (string) $captured_url
        );

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function get_voice_returns_false_without_language_code()
    {
        $this->assertFalse(Client::get_voice(123, false));
    }

    /**
     * @test
     */
    public function get_voice_returns_false_when_no_voices_for_language()
    {
        $filter = function ($preempt, $args, $url) {
            if (str_contains($url, '/voices')) {
                return [
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'body'     => '[]',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertFalse(Client::get_voice(123, 'en'));

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * @test
     */
    public function get_voice_returns_matching_voice()
    {
        $filter = function ($preempt, $args, $url) {
            if (str_contains($url, '/voices')) {
                return [
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'body'     => wp_json_encode([
                        ['id' => 100, 'name' => 'Alpha'],
                        ['id' => 200, 'name' => 'Beta'],
                    ]),
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $voice = Client::get_voice(200, 'en');

        remove_filter('pre_http_request', $filter, 10);

        $this->assertIsArray($voice);
        $this->assertSame('Beta', $voice['name']);
    }

    /**
     * @test
     */
    public function update_voice_returns_false_without_voice_id()
    {
        $this->assertFalse(Client::update_voice(0, ['name' => 'Test']));
    }

    /**
     * @test
     */
    public function update_voice_sends_put_and_returns_decoded_body()
    {
        $captured_method = null;
        $filter = function ($preempt, $args, $url) use (&$captured_method) {
            $captured_method = $args['method'] ?? null;
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":42,"name":"Updated"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $response = Client::update_voice(42, ['name' => 'Updated']);

        remove_filter('pre_http_request', $filter, 10);

        $this->assertSame('PUT', $captured_method);
        $this->assertSame(['id' => 42, 'name' => 'Updated'], $response);
    }

    /**
     * @test
     */
    public function update_project_returns_false_without_project_id()
    {
        delete_option('beyondwords_project_id');

        $this->assertFalse(Client::update_project(['title' => 'Test']));
    }

    /**
     * @test
     */
    public function update_project_sends_put_and_returns_decoded_body()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $captured = ['method' => null, 'url' => null];
        $filter = function ($preempt, $args, $url) use (&$captured) {
            $captured['method'] = $args['method'] ?? null;
            $captured['url']    = $url;
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":' . BEYONDWORDS_TESTS_PROJECT_ID . ',"title":"Updated"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $response = Client::update_project(['title' => 'Updated']);

        remove_filter('pre_http_request', $filter, 10);

        $this->assertSame('PUT', $captured['method']);
        $this->assertStringContainsString('/projects/' . BEYONDWORDS_TESTS_PROJECT_ID, (string) $captured['url']);
        $this->assertStringNotContainsString('/player_settings', (string) $captured['url']);
        $this->assertSame('Updated', $response['title']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function update_player_settings_returns_false_without_project_id()
    {
        delete_option('beyondwords_project_id');

        $this->assertFalse(Client::update_player_settings(['theme' => 'dark']));
    }

    /**
     * @test
     */
    public function update_player_settings_hits_player_settings_endpoint()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $captured_url = null;
        $filter = function ($preempt, $args, $url) use (&$captured_url) {
            $captured_url = $url;
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"theme":"dark"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $response = Client::update_player_settings(['theme' => 'dark']);

        remove_filter('pre_http_request', $filter, 10);

        $this->assertStringContainsString(
            '/projects/' . BEYONDWORDS_TESTS_PROJECT_ID . '/player_settings',
            (string) $captured_url
        );
        $this->assertSame('dark', $response['theme']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }
}