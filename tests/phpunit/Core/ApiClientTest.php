<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\ApiClient;
use Beyondwords\Wordpress\Core\Request;

class ApiClientTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.

        // Clear existing admin notices, so we can test notices in isolation
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        // Clear existing admin notices, so we can test notices in isolation
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function createAudioWithoutProjectIdSetting()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::createAudioWithoutProjectIdSetting',
        ]);

        $response = ApiClient::createAudio($postId);

        $this->assertFalse($response);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     * @group current
     */
    public function createAudio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::createAudio',
        ]);

        $response = ApiClient::createAudio($postId);

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
    public function updateAudio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::updateAudio',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $response = ApiClient::updateAudio($postId);

        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID,  $response['id']);
        $this->assertSame('processed',  $response['status']);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function deleteAudio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::deleteAudio',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $response = ApiClient::deleteAudio($postId);

        // Response body is empty for 201 Deleted responses
        $this->assertNull($response);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function batchDeleteAudio()
    {
        $numPosts = 20;

        $postIds = self::factory()->post->create_many($numPosts, [
            'post_title' => 'ApiClientTest::batchDeleteAudio %d',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $deleted = ApiClient::batchDeleteAudio($postIds);
        $this->assertEquals([], $deleted);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $deleted = ApiClient::batchDeleteAudio($postIds);

        $this->assertEquals($deleted, array_values($postIds));

        foreach ($deleted as $postId) {
            wp_delete_post($postId);
        }

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group voices
     */
    public function getLanguages()
    {
        $response = ApiClient::getLanguages();
        $this->assertSame('Authentication token was not recognized.', $response['message']);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = ApiClient::getLanguages();

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
    public function getVoices()
    {
        $response = ApiClient::getVoices('en_US');
        $this->assertSame('Authentication token was not recognized.', $response['message']);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = ApiClient::getVoices('en_US');

        $this->assertSame(3555, $response[0]['id']);
        $this->assertSame(2517, $response[1]['id']);
        $this->assertSame(3558, $response[2]['id']);

        $this->assertSame('Ada (Multilingual)', $response[0]['name']);
        $this->assertSame('Ava (Multilingual)', $response[1]['name']);
        $this->assertSame('Ollie (Multilingual)', $response[2]['name']);

        $this->assertSame('en_US', $response[0]['language']);
        $this->assertSame('en_US', $response[1]['language']);
        $this->assertSame('en_US', $response[2]['language']);

        $this->assertSame(100, $response[0]['speaking_rate']);
        $this->assertSame(100, $response[1]['speaking_rate']);
        $this->assertSame(100, $response[2]['speaking_rate']);
    }

    /**
     * @test
     * @group settings
     */
    public function getProject()
    {
        $response = ApiClient::getProject();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = ApiClient::getProject();

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
    public function getPlayerSettings()
    {
        $response = ApiClient::getPlayerSettings();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = ApiClient::getPlayerSettings();

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
    public function getVideoSettings()
    {
        $response = ApiClient::getVideoSettings();
        $this->assertFalse($response);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $response = ApiClient::getVideoSettings();

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
     * 401 Invalid authentication token
     */
    public function callApiWithoutAuthHeader()
    {
        $postId = $this->factory->post->create([
            'post_title' => 'ApiClientTest::callApiWithoutAuthHeader',
        ]);

        $request = new Request('POST', \BEYONDWORDS_API_URL . '/projects/1234/content', '{"body":"Hello"}');

        // Unset Auth header
        $headers = $request->getHeaders();
        unset($headers['X-Api-Key']);

        $request->setHeaders($headers);
        $response = ApiClient::callApi($request, $postId);

        $this->assertSame(401, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $error = sprintf(ApiClient::ERROR_FORMAT, 401, 'Authentication token was not recognized.');
        $this->assertSame($error, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * 401 Invalid authentication token
     */
    public function callApiWithEmptyAuthHeader()
    {
        $postId = $this->factory->post->create([
            'post_title' => 'ApiClientTest::callApiWithEmptyAuthHeader',
        ]);

        $request = new Request('POST', \BEYONDWORDS_API_URL . '/projects/1234/content', '{"body":"Hello"}');

        // Unset Auth header
        $headers = $request->getHeaders();
        $headers['X-Api-Key'] = 'AN INVALID API KEY';

        $request->setHeaders($headers);
        $response = ApiClient::callApi($request, $postId);

        $this->assertSame(401, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $error = sprintf(ApiClient::ERROR_FORMAT, 401, 'Authentication token was not recognized.');
        $this->assertSame($error, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * 401 Invalid authentication token
     */
    public function callApiWithInvalidContentType()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::callApiWithInvalidContentTypeHeader',
        ]);

        $request = new Request('POST', \BEYONDWORDS_API_URL . '/projects/1234/content', '{"body":"Hello"}');

        // Set an invalid Content-Type header
        $headers = $request->getHeaders();
        $headers['Content-Type'] = 'text/html';

        $request->setHeaders($headers);

        $response = ApiClient::callApi($request, $postId);

        $this->assertSame(401, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $error = sprintf(ApiClient::ERROR_FORMAT, 401, 'Authentication token was not recognized.');
        $this->assertSame($error, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * Invalid URL which should get error message using is_wp_error().
     */
    public function callApiWithInvalidEndpoint()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::callApiWithInvalidEndpoint',
        ]);

        $request = new Request('POST', \BEYONDWORDS_API_URL . '/foo/1234/bar', '{"body":"Hello"}');

        $response = ApiClient::callApi($request, $postId);

        $this->assertSame(404, wp_remote_retrieve_response_code($response));

        // We should find the error code & message in the post_meta table
        $this->assertSame('#404: Not Found', get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * Invalid URL which should get error message using is_wp_error().
     */
    public function callApiWithInvalidDomain()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::callApiWithInvalidDomain',
        ]);

        $request = new Request('POST', 'http://localhost:5678/foo', '{"body":"Hello"}');

        $response = ApiClient::callApi($request, $postId);

        $this->assertTrue(is_a($response, 'WP_Error'));

        $errorMessage = get_post_meta($postId, 'beyondwords_error_message', true);

        $this->assertStringStartsWith('#500:', $errorMessage);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @dataProvider saveErrorMessageProvider
     */
    public function saveErrorMessage(string $message, int $code, string $expect)
    {
        $postId = self::factory()->post->create([
            'post_title' => 'ApiClientTest::error::' . $code,
        ]);

        ApiClient::saveErrorMessage($postId, $message, $code);

        $this->assertEquals($expect, get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);
    }

    public function saveErrorMessageProvider()
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
     */
    public function errorMessageFromResponse()
    {
        $response = [
            'body' => wp_json_encode(['message' => 'Foo'])
        ];

        $result = ApiClient::errorMessageFromResponse($response);

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

        $result = ApiClient::errorMessageFromResponse($response);

        $this->assertEquals('500 Foo, 501 Bar', $result);
    }
}