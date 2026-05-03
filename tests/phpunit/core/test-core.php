<?php

declare(strict_types=1);

use BeyondWords\Settings\Fields;
use BeyondWords\Core\Core;

class CoreTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Core::init();

        $this->assertEquals(99, has_action('init', array(Core::class, 'register_meta')));
        $this->assertEquals(99, has_action('wp_after_insert_post', array(Core::class, 'on_add_or_update_post')));
        $this->assertEquals(10, has_action('wp_trash_post', array(Core::class, 'on_trash_post')));
        $this->assertEquals(10, has_action('before_delete_post', array(Core::class, 'on_delete_post')));
        $this->assertEquals(10, has_action('is_protected_meta', array(Core::class, 'is_protected_meta')));
        $this->assertEquals(10, has_action('get_post_metadata', array(Core::class, 'get_lang_code_from_json_if_empty')));
    }

    /**
     * @test
     * @dataProvider success_response
     */
    public function meta_generate_audio_will_create_audio($response)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_type' => 'post',
            'post_title' => 'CoreTest::metaGenerateAudioWillCreateAudio',
            'post_content' => '<p>The body.</p>',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $this->assertNotFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @dataProvider success_response
     */
    public function meta_content_id_and_meta_project_id_will_update_audio($response)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = $this->factory->post->create([
            'post_title' => 'CoreTest::metaContentIdAndMetaProjectIdWillUpdateAudio',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertNotFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @dataProvider success_response
     */
    public function meta_content_id_and_settings_project_id_will_update_audio($response)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = $this->factory->post->create([
            'post_title' => 'CoreTest::metaContentIdAndSettingsProjectIdWillUpdateAudio',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertNotFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    public function success_response()
    {
        return [
            'success response' => [
                [
                    'id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    'external_id' => 42,
                    'state' => 'unprocessed',
                    'media' => [],
                    'image_url' => '',
                    'deleted' => false,
                    'access_key' => 'abcd9969abcd9969abcd9969abcd9969',
                    'metadata' => [],
                ]
            ],
        ];
    }

    /**
     * @test
     */
    public function empty_post_meta_will_not_create_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::emptyPostMetaWillNotCreateAudio',
        ]);

        $this->assertFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function missing_project_id_will_not_create_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::missingProjectIdWillNotCreateAudio',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $this->assertFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function revision_will_not_create_audio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::revisionWillNotCreateAudio',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $revisionId = wp_save_post_revision($postId);

        $this->assertFalse(Core::generate_audio_for_post($revisionId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group post-statuses
     *
     * @dataProvider should_process_post_status_provider
     */
    public function should_process_post_status($expect, $status)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Add 'my_custom_status' to the list of statuses we process
        $filter = function($statuses) {
            array_push($statuses, 'my_custom_status');
            return array_unique($statuses);
        };

        add_filter('beyondwords_settings_post_statuses', $filter);

        $this->assertEquals($expect, Core::should_process_post_status($status));

        remove_filter('beyondwords_settings_post_statuses', $filter);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     *
     */
    public function should_process_post_status_provider() {
        return [
            'draft' => [
                'expect' => false,
                'status' => 'draft'
            ],
            'pending' => [
                'expect' => true,
                'status' => 'pending'
            ],
            'publish' => [
                'expect' => true,
                'status' => 'publish'
            ],
            'private' => [
                'expect' => true,
                'status' => 'private'
            ],
            'future'  => [
                'expect' => true,
                'status' => 'future'
            ],
            'trash' => [
                'expect' => false,
                'status' => 'trash'
            ],
            'my_custom_status' => [
                'expect' => true,
                'status' => 'my_custom_status'
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider post_statuses_to_exclude_provider
     */
    public function excluded_post_status_will_not_create_audio($status)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::excludedPostStatusWillNotCreateAudio',
            'post_status' => $status,
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $this->assertFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function on_delete_post()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onDeletePost',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        Core::on_delete_post($postId);

        wp_delete_post($postId, true);

        $this->assertSame('', get_post_meta($postId, 'beyondwords_error_message', true));
        $this->assertSame('', get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame('', get_post_meta($postId, 'beyondwords_content_id', true));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function on_trash_post()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashPost',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        Core::on_trash_post($postId);

        wp_trash_post($postId);

        $this->assertSame('', get_post_meta($postId, 'beyondwords_error_message', true));
        $this->assertSame('', get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame('', get_post_meta($postId, 'beyondwords_content_id', true));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * @dataProvider post_statuses_to_exclude_provider
     */
    public function post_statuses_filter_processes_added_statuses($status)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::excludePostStatusesFilter',
            'post_status' => $status,
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        // Add $status to the list of statuses we process
        $filter = function($statuses) use ($status) {
            array_push($statuses, $status);
            return array_unique($statuses);
        };

        add_filter('beyondwords_settings_post_statuses', $filter);

        $this->assertNotFalse(Core::generate_audio_for_post($postId));

        remove_filter('beyondwords_settings_post_statuses', $filter);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * @dataProvider post_statuses_to_process_provider
     */
    public function post_statuses_filter_excludes_removed_statuses($status)
    {
        // Empty the list of statuses we process
        $filter = function($statuses) {
            return [];
        };

        add_filter('beyondwords_settings_post_statuses', $filter);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::excludePostStatusesFilter',
            'post_status' => $status,
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $this->assertFalse(Core::generate_audio_for_post($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        remove_filter('beyondwords_settings_post_statuses', $filter);
    }

    /**
     *
     */
    public function post_statuses_to_process_provider() {
        return [
            'pending' => ['pending'],
            'publish' => ['publish'],
            'private' => ['private'],
            'future'  => ['future'],
        ];
    }

    /**
     *
     */
    public function post_statuses_to_exclude_provider() {
        return [
            'draft' => ['draft'],
            'trash' => ['trash'],
            'my_custom_status' => ['my_custom_status'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider generate_audio_for_post_preserves_integration_method_custom_field_provider
     */
    public function generate_audio_for_post_preserves_integration_method_custom_field($expect, $optionValue, $metaInput)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_integration_method', $optionValue);

        $postId = self::factory()->post->create([
            'meta_input' => $metaInput,
        ]);

        $this->assertNotFalse(Core::generate_audio_for_post($postId));
        $this->assertEquals($expect, get_post_meta($postId, 'beyondwords_integration_method', true));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_integration_method');
    }

    public function generate_audio_for_post_preserves_integration_method_custom_field_provider() {
        return [
            'Sets rest-api custom field' => [
                'expect'       => 'rest-api',
                'option_value' => 'rest-api',
                'meta_input'   => [
                    'beyondwords_generate_audio' => '1',
                ],
            ],
            'Preserves rest-api custom field' => [
                'expect'       => 'rest-api',
                'option_value' => 'client-side',
                'meta_input'   => [
                    'beyondwords_generate_audio' => '1',
                    'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                    'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    'beyondwords_integration_method' => 'rest-api',
                ],
            ],
            'Sets client-side custom field' => [
                'expect'       => 'client-side',
                'option_value' => 'client-side',
                'meta_input'   => [
                    'beyondwords_generate_audio' => '1',
                ],
            ],
            'Preserves client-side custom field' => [
                'expect'       => 'client-side',
                'option_value' => 'rest-api',
                'meta_input'   => [
                    'beyondwords_generate_audio' => '1',
                    'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                    'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    'beyondwords_integration_method' => 'client-side',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider process_response_provider
     */
    public function process_response($response, $projectId, $contentId, $language, $summaryVoiceId, $titleVoiceId, $bodyVoiceId) {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::processResponse',
        ]);

        Core::process_response($response, BEYONDWORDS_TESTS_PROJECT_ID, $postId);

        $this->assertSame($projectId, get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame($contentId, get_post_meta($postId, 'beyondwords_content_id', true));
        $this->assertSame($language, get_post_meta($postId, 'beyondwords_language_code', true));
        $this->assertSame($summaryVoiceId, get_post_meta($postId, 'beyondwords_summary_voice_id', true));
        $this->assertSame($titleVoiceId, get_post_meta($postId, 'beyondwords_title_voice_id', true));
        $this->assertSame($bodyVoiceId, get_post_meta($postId, 'beyondwords_body_voice_id', true));

        wp_delete_post($postId, true);
    }

    public function process_response_provider() {
        return [
            'Response includes Content ID' => [
                'response' => [
                    'id'               => BEYONDWORDS_TESTS_CONTENT_ID,
                    'language'         => 'en_US',
                    'summary_voice_id' => '3555',
                    'title_voice_id'   => '2517',
                    'body_voice_id'    => '3558',
                ],
                'projectId'      => BEYONDWORDS_TESTS_PROJECT_ID,
                'contentId'      => BEYONDWORDS_TESTS_CONTENT_ID,
                'language'       => 'en_US',
                'summaryVoiceId' => '3555',
                'titleVoiceId'   => '2517',
                'bodyVoiceId'    => '3558',
            ],
            'Response is not an array' => [
                'response'       => new StdClass(),
                'projectId'      => '',
                'contentId'      => '',
                'language'       => '',
                'summaryVoiceId' => '',
                'titleVoiceId'   => '',
                'bodyVoiceId'    => '',
            ],
        ];
    }

    /**
     * @test
     */
    public function register_meta()
    {
        Core::register_meta();

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::registerMeta',
            'meta_input' => [
                'beyondwords_generate_audio'        => 'beyondwords_generate_audio',
                'beyondwords_disabled'              => 'beyondwords_disabled',
                'beyondwords_error_message'         => 'beyondwords_error_message',
                'beyondwords_content_id'            => 'beyondwords_content_id',
                'beyondwords_podcast_id'            => 'beyondwords_podcast_id',
                'beyondwords_preview_token'         => 'beyondwords_preview_token',
                'beyondwords_project_id'            => 'beyondwords_project_id',
                'speechkit_info'                    => 'speechkit_info',
                'speechkit_response'                => 'speechkit_response',
                'speechkit_retries'                 => 'speechkit_retries',
                'speechkit_status'                  => 'speechkit_status',
                '_speechkit_link'                   => '_speechkit_link',
                '_speechkit_text'                   => '_speechkit_text',
            ],
        ]);

        $meta = get_registered_metadata('post', $postId);

        $this->assertArrayHasKey('beyondwords_generate_audio', $meta);
        $this->assertSame('beyondwords_generate_audio', get_post_meta($postId, 'beyondwords_generate_audio', true));

        $this->assertArrayHasKey('beyondwords_disabled', $meta);
        $this->assertSame('beyondwords_disabled', get_post_meta($postId, 'beyondwords_disabled', true));

        $this->assertArrayHasKey('beyondwords_error_message', $meta);
        $this->assertSame('beyondwords_error_message', get_post_meta($postId, 'beyondwords_error_message', true));

        $this->assertArrayHasKey('beyondwords_content_id', $meta);
        $this->assertSame('beyondwords_content_id', get_post_meta($postId, 'beyondwords_content_id', true));

        $this->assertArrayHasKey('beyondwords_podcast_id', $meta);
        $this->assertSame('beyondwords_podcast_id', get_post_meta($postId, 'beyondwords_podcast_id', true));

        $this->assertArrayHasKey('beyondwords_preview_token', $meta);
        $this->assertSame('beyondwords_preview_token', get_post_meta($postId, 'beyondwords_preview_token', true));

        $this->assertArrayHasKey('beyondwords_project_id', $meta);
        $this->assertSame('beyondwords_project_id', get_post_meta($postId, 'beyondwords_project_id', true));

        $this->assertArrayHasKey('speechkit_info', $meta);
        $this->assertSame('speechkit_info', get_post_meta($postId, 'speechkit_info', true));

        $this->assertArrayHasKey('speechkit_response', $meta);
        $this->assertSame('speechkit_response', get_post_meta($postId, 'speechkit_response', true));

        $this->assertArrayHasKey('speechkit_retries', $meta);
        $this->assertSame('speechkit_retries', get_post_meta($postId, 'speechkit_retries', true));

        $this->assertArrayHasKey('speechkit_status', $meta);
        $this->assertSame('speechkit_status', get_post_meta($postId, 'speechkit_status', true));

        $this->assertArrayHasKey('_speechkit_link', $meta);
        $this->assertSame('_speechkit_link', get_post_meta($postId, '_speechkit_link', true));

        $this->assertArrayHasKey('_speechkit_text', $meta);
        $this->assertSame('_speechkit_text', get_post_meta($postId, '_speechkit_text', true));

        wp_delete_post($postId, true);
    }

    function remove_delete_actions($core) {
        // Actions for deleting/trashing/restoring posts
        remove_action('before_delete_post', array($core, 'onDeletePost'));
    }

    /**
     * @test
     * @dataProvider is_protected_meta_provider
     */
    public function is_protected_meta($expect, $protected, $metaKey)
    {
        $this->assertSame($expect, Core::is_protected_meta($protected, $metaKey));
    }

    public function is_protected_meta_provider()
    {
        return [
            'BeyondWords meta key with protected=true' => [
                'expect' => true,
                'protected' => true,
                'metaKey' => 'beyondwords_project_id',
            ],
            'BeyondWords meta key with protected=false' => [
                'expect' => true,
                'protected' => false,
                'metaKey' => 'beyondwords_content_id',
            ],
            'BeyondWords meta key with protected=null' => [
                'expect' => true,
                'protected' => null,
                'metaKey' => 'beyondwords_generate_audio',
            ],
            'Deprecated meta key with protected=null' => [
                'expect' => true,
                'protected' => null,
                'metaKey' => 'speechkit_status',
            ],
            'Non-BeyondWords meta key with protected=true' => [
                'expect' => true,
                'protected' => true,
                'metaKey' => 'some_other_meta_key',
            ],
            'Non-BeyondWords meta key with protected=false' => [
                'expect' => false,
                'protected' => false,
                'metaKey' => 'some_other_meta_key',
            ],
            'Non-BeyondWords meta key with protected=null' => [
                'expect' => false,
                'protected' => null,
                'metaKey' => 'wp_persisted_preferences',
            ],
            'Null meta key with protected=true' => [
                'expect' => true,
                'protected' => true,
                'metaKey' => null,
            ],
            'Null meta key with protected=false' => [
                'expect' => false,
                'protected' => false,
                'metaKey' => null,
            ],
            'Null meta key with protected=null' => [
                'expect' => false,
                'protected' => null,
                'metaKey' => null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider lang_codes
     */
    public function get_lang_code_from_json_if_empty($language_id, $language_code) {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::getLangCodeFromJsonIfEmpty',
            'meta_input' => [
                'beyondwords_language_id' => $language_id,
            ],
        ]);

        $this->assertSame('foo', Core::get_lang_code_from_json_if_empty('foo', $postId, 'beyondwords_language_foo', true));
        $this->assertSame('bar', Core::get_lang_code_from_json_if_empty('bar', $postId, 'beyondwords_language_code', true));
        $this->assertSame(["$language_code"], Core::get_lang_code_from_json_if_empty('', $postId, 'beyondwords_language_code', true));
    }

    /**
     * @test
     */
    public function get_lang_code_from_json_if_empty_handles_null_meta_key() {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::getLangCodeFromJsonIfEmptyHandlesNullMetaKey',
            'meta_input' => [
                'beyondwords_language_id' => '50',
            ],
        ]);

        // When meta_key is null (e.g. when get_post_meta is called without a key),
        // the function should return the value unchanged
        $this->assertNull(Core::get_lang_code_from_json_if_empty(null, $postId, null));
        $this->assertSame('foo', Core::get_lang_code_from_json_if_empty('foo', $postId, null));
        $this->assertSame(['bar'], Core::get_lang_code_from_json_if_empty(['bar'], $postId, null));

        wp_delete_post($postId, true);
    }

    public function lang_codes()
    {
        return [
            'en_GB' => [
                'language_id' => "50",
                'language_code' => "en_GB",
            ],
            'en_US' => [
                'language_id' => "58",
                'language_code' => "en_US",
            ],
            'ar_SA' => [
                'language_id' => "10",
                'language_code' => "ar_SA",
            ],
            'zh_CN_shandong' => [
                'language_id' => "273",
                'language_code' => "zh_CN_shandong",
            ],
            'zh_CN_liaoning' => [
                'language_id' => "269",
                'language_code' => "zh_CN_liaoning",
            ],
            'zh_TW' => [
                'language_id' => "234",
                'language_code' => "zh_TW",
            ],
        ];
    }

    /**
     * @test
     * @group generateAudio
     * @dataProvider should_generate_audio_for_post_provider
     */
    public function should_generate_audio_for_post($expect, $post_status, $meta_input) {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::shouldGenerateAudioForPost',
            'post_status' => $post_status,
            'meta_input' => $meta_input,
        ]);

        $this->assertSame($expect, Core::should_generate_audio_for_post($postId));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        wp_delete_post($postId, true);
    }

    public function should_generate_audio_for_post_provider()
    {
        return [
            'no post meta' => [
                'expect' => false,
                'post_status' => 'publish',
                'meta_input' => [],
            ],
            'beyondwords_generate_audio = ""' => [
                'expect' => false,
                'post_status' => 'publish',
                'meta_input' => [
                    'beyondwords_generate_audio' => '',
                ],
            ],
            'beyondwords_generate_audio = 0' => [
                'expect' => false,
                'post_status' => 'publish',
                'meta_input' => [
                    'beyondwords_generate_audio' => '0',
                ],
            ],
            'beyondwords_generate_audio = 1' => [
                'expect' => true,
                'post_status' => 'publish',
                'meta_input' => [
                    'beyondwords_generate_audio' => '1',
                ],
            ],
            'draft' => [
                'expect' => false,
                'post_status' => 'draft',
                'meta_input' => [
                    'beyondwords_generate_audio' => '1',
                ],
            ],
            'trash' => [
                'expect' => false,
                'post_status' => 'trash',
                'meta_input' => [
                    'beyondwords_generate_audio' => '1',
                ],
            ],
        ];
    }

    /**
     * @test
     * @group deduplication
     */
    public function on_add_or_update_post_skips_meta_box_loader_request()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onAddOrUpdatePostSkipsMetaBoxLoaderRequest',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        // Save the original state to restore later
        $hadMetaBoxLoader = isset($_REQUEST['meta-box-loader']);
        $originalMetaBoxLoader = $hadMetaBoxLoader ? $_REQUEST['meta-box-loader'] : null;

        try {
            // Simulate Gutenberg's meta box compat request
            $_REQUEST['meta-box-loader'] = '1';

            $result = Core::on_add_or_update_post($postId);

            // Should return false without making any API calls
            $this->assertFalse($result);
        } finally {
            // Restore original state
            if ($hadMetaBoxLoader) {
                $_REQUEST['meta-box-loader'] = $originalMetaBoxLoader;
            } else {
                unset($_REQUEST['meta-box-loader']);
            }
        }

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group deduplication
     */
    public function on_add_or_update_post_runs_without_meta_box_loader()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onAddOrUpdatePostRunsWithoutMetaBoxLoader',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        // Save the original state to restore later
        $hadMetaBoxLoader = isset($_REQUEST['meta-box-loader']);
        $originalMetaBoxLoader = $hadMetaBoxLoader ? $_REQUEST['meta-box-loader'] : null;

        try {
            // Ensure meta-box-loader is not set
            unset($_REQUEST['meta-box-loader']);

            $result = Core::on_add_or_update_post($postId);

            // Should not be false — the method should proceed to generateAudioForPost
            $this->assertTrue($result);
        } finally {
            // Restore original state
            if ($hadMetaBoxLoader) {
                $_REQUEST['meta-box-loader'] = $originalMetaBoxLoader;
            } else {
                unset($_REQUEST['meta-box-loader']);
            }
        }

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group delete
     */
    public function on_delete_post_skips_revisions()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onDeletePostSkipsRevisions',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $revisionId = wp_save_post_revision($postId);

        // Intercept any HTTP request — if deleteAudio fires, this will record it
        $apiCalled = false;
        $filter = function () use (&$apiCalled) {
            $apiCalled = true;
            return ['response' => ['code' => 204, 'message' => 'No Content'], 'body' => '', 'headers' => [], 'cookies' => []];
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::on_delete_post($revisionId);

        remove_filter('pre_http_request', $filter);

        // The revision has no BeyondWords content, so no API call should have been made
        $this->assertFalse($apiCalled);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group delete
     */
    public function on_delete_post_skips_posts_without_content()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Post with no BeyondWords meta (e.g. a Jetpack sitemap post, auto-draft, etc.)
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onDeletePostSkipsPostsWithoutContent',
        ]);

        $apiCalled = false;
        $filter = function () use (&$apiCalled) {
            $apiCalled = true;
            return ['response' => ['code' => 204, 'message' => 'No Content'], 'body' => '', 'headers' => [], 'cookies' => []];
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::on_delete_post($postId);

        remove_filter('pre_http_request', $filter);

        $this->assertFalse($apiCalled);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group trash
     */
    public function on_trash_post_skips_revisions()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashPostSkipsRevisions',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $revisionId = wp_save_post_revision($postId);

        $apiCalled = false;
        $filter = function () use (&$apiCalled) {
            $apiCalled = true;
            return ['response' => ['code' => 204, 'message' => 'No Content'], 'body' => '', 'headers' => [], 'cookies' => []];
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::on_trash_post($revisionId);

        remove_filter('pre_http_request', $filter);

        $this->assertFalse($apiCalled);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group trash
     */
    public function on_trash_post_skips_posts_without_content()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Post with no BeyondWords meta
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashPostSkipsPostsWithoutContent',
        ]);

        $apiCalled = false;
        $filter = function () use (&$apiCalled) {
            $apiCalled = true;
            return ['response' => ['code' => 204, 'message' => 'No Content'], 'body' => '', 'headers' => [], 'cookies' => []];
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::on_trash_post($postId);

        remove_filter('pre_http_request', $filter);

        $this->assertFalse($apiCalled);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group 404-recovery
     */
    public function generate_audio_for_post_recovers_from404()
    {
        $staleContentId = '00000000-0000-0000-0000-000000000000';

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::generateAudioForPostRecoversFrom404',
            'post_content' => '<p>Test content for 404 recovery.</p>',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => $staleContentId,
            ],
        ]);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $filter = $this->add_not_found_filter($staleContentId, ['PUT']);

        $response = Core::generate_audio_for_post($postId);

        remove_filter('pre_http_request', $filter);

        // Should have recovered by creating new content
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);

        // The stale content ID should have been replaced with a new one
        $newContentId = get_post_meta($postId, 'beyondwords_content_id', true);
        $this->assertNotEmpty($newContentId);
        $this->assertNotEquals($staleContentId, $newContentId);

        // No error message should remain
        $this->assertEmpty(get_post_meta($postId, 'beyondwords_error_message', true));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group 404-recovery
     */
    public function generate_audio_for_post_clears_legacy_ids_on404()
    {
        $staleContentId = '00000000-0000-0000-0000-000000000000';

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::generateAudioForPostClearsLegacyIdsOn404',
            'post_content' => '<p>Test content.</p>',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => $staleContentId,
                'beyondwords_podcast_id' => 'stale-podcast-id',
                'speechkit_podcast_id' => 'stale-speechkit-id',
            ],
        ]);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $filter = $this->add_not_found_filter($staleContentId, ['PUT']);

        Core::generate_audio_for_post($postId);

        remove_filter('pre_http_request', $filter);

        // Legacy ID fields should be cleared
        $this->assertEmpty(get_post_meta($postId, 'beyondwords_podcast_id', true));
        $this->assertEmpty(get_post_meta($postId, 'speechkit_podcast_id', true));

        // New content ID should be set
        $newContentId = get_post_meta($postId, 'beyondwords_content_id', true);
        $this->assertNotEmpty($newContentId);
        $this->assertNotEquals($staleContentId, $newContentId);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function delete_audio_for_post_delegates_to_api_client()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $captured_method = null;
        $filter = function ($preempt, $args, $url) use (&$captured_method) {
            if (str_contains($url, '/content/')) {
                $captured_method = $args['method'] ?? null;
                return [
                    'response' => ['code' => 204, 'message' => 'No Content'],
                    'body'     => '',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::delete_audio_for_post($postId);

        remove_filter('pre_http_request', $filter, 1);

        $this->assertSame('DELETE', $captured_method);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function batch_delete_audio_for_posts_delegates_to_api_client()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postIds = [];
        for ($i = 0; $i < 2; $i++) {
            $postIds[] = self::factory()->post->create([
                'meta_input' => [
                    'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                    'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                ],
            ]);
        }

        $hit = false;
        $filter = function ($preempt, $args, $url) use (&$hit) {
            if (str_contains($url, '/content')) {
                $hit = true;
                return [
                    'response' => ['code' => 204, 'message' => 'No Content'],
                    'body'     => '',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        Core::batch_delete_audio_for_posts($postIds);

        remove_filter('pre_http_request', $filter, 1);

        $this->assertTrue($hit, 'batch_delete should issue an HTTP request to /content');

        foreach ($postIds as $id) {
            wp_delete_post($id, true);
        }

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }
}
