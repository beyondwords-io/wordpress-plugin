<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Core\Core;

class CoreTest extends WP_UnitTestCase
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

        $this->assertEquals(1,  has_action('enqueue_block_editor_assets', array(Core::class, 'enqueueBlockEditorAssets')));
        $this->assertEquals(99, has_action('init', array(Core::class, 'registerMeta')));
        $this->assertEquals(99, has_action('wp_after_insert_post', array(Core::class, 'onAddOrUpdatePost')));
        $this->assertEquals(10, has_action('before_delete_post', array(Core::class, 'onDeletePost')));
        $this->assertEquals(10, has_action('is_protected_meta', array(Core::class, 'isProtectedMeta')));
        $this->assertEquals(10, has_action('get_post_metadata', array(Core::class, 'getLangCodeFromJsonIfEmpty')));
    }

    /**
     * @test
     * @dataProvider successResponse
     */
    public function metaGenerateAudioWillCreateAudio($response)
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

        $this->assertNotFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @dataProvider successResponse
     */
    public function metaContentIdAndMetaProjectIdWillUpdateAudio($response)
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

        $this->assertNotFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @dataProvider successResponse
     */
    public function metaContentIdAndSettingsProjectIdWillUpdateAudio($response)
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

        $this->assertNotFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function enqueueBlockEditorAssets()
    {
        global $post, $wp_scripts;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'CoreTest::enqueueBlockEditorAssets',
            'post_type' => 'post',
        ]);

        setup_postdata($post);

        set_current_screen( 'edit-post' );
        $current_screen = get_current_screen();
        $current_screen->is_block_editor( true );

        $this->assertNull($wp_scripts);

        /**
         * Enqueuing without a valid API connection should do nothing
         */
        Core::enqueueBlockEditorAssets();

        $this->assertNull($wp_scripts);

        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        /**
         * Enqueuing with a valid API connection should succeed
         */
        Core::enqueueBlockEditorAssets();

        $this->assertContains('beyondwords-block-js', $wp_scripts->queue);

        $wp_scripts = null;

        delete_option('beyondwords_valid_api_connection');

        wp_delete_post($post->ID, true);
    }

    public function successResponse()
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
    public function emptyPostMetaWillNotCreateAudio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::emptyPostMetaWillNotCreateAudio',
        ]);

        $this->assertFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function missingProjectIdWillNotCreateAudio()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::missingProjectIdWillNotCreateAudio',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
            ],
        ]);

        $this->assertFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function revisionWillNotCreateAudio()
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

        $this->assertFalse(Core::generateAudioForPost($revisionId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group post-statuses
     *
     * @dataProvider shouldProcessPostStatusProvider
     */
    public function shouldProcessPostStatus($expect, $status)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Add 'my_custom_status' to the list of statuses we process
        $filter = function($statuses) {
            array_push($statuses, 'my_custom_status');
            return array_unique($statuses);
        };

        add_filter('beyondwords_settings_post_statuses', $filter);

        $this->assertEquals($expect, Core::shouldProcessPostStatus($status));

        remove_filter('beyondwords_settings_post_statuses', $filter);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     *
     */
    public function shouldProcessPostStatusProvider() {
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
     * @dataProvider postStatusesToExcludeProvider
     */
    public function excludedPostStatusWillNotCreateAudio($status)
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

        $this->assertFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function onDeletePost()
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

        Core::onDeletePost($postId);

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
    public function onTrashPost()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashPost',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        Core::onTrashPost($postId);

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
     * @dataProvider postStatusesToExcludeProvider
     */
    public function postStatusesFilterProcessesAddedStatuses($status)
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

        $this->assertNotFalse(Core::generateAudioForPost($postId));

        remove_filter('beyondwords_settings_post_statuses', $filter);

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * @dataProvider postStatusesToProcessProvider
     */
    public function postStatusesFilterExcludesRemovedStatuses($status)
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

        $this->assertFalse(Core::generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        remove_filter('beyondwords_settings_post_statuses', $filter);
    }

    /**
     *
     */
    public function postStatusesToProcessProvider() {
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
    public function postStatusesToExcludeProvider() {
        return [
            'draft' => ['draft'],
            'trash' => ['trash'],
            'my_custom_status' => ['my_custom_status'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider generateAudioForPostPreservesIntegrationMethodCustomFieldProvider
     */
    public function generateAudioForPostPreservesIntegrationMethodCustomField($expect, $optionValue, $metaInput)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_integration_method', $optionValue);

        $postId = self::factory()->post->create([
            'meta_input' => $metaInput,
        ]);

        $this->assertNotFalse(Core::generateAudioForPost($postId));
        $this->assertEquals($expect, get_post_meta($postId, 'beyondwords_integration_method', true));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_integration_method');
    }

    public function generateAudioForPostPreservesIntegrationMethodCustomFieldProvider() {
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
     * @dataProvider processResponseProvider
     */
    public function processResponse($response, $projectId, $contentId, $language, $summaryVoiceId, $titleVoiceId, $bodyVoiceId) {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::processResponse',
        ]);

        Core::processResponse($response, BEYONDWORDS_TESTS_PROJECT_ID, $postId);

        $this->assertSame($projectId, get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame($contentId, get_post_meta($postId, 'beyondwords_content_id', true));
        $this->assertSame($language, get_post_meta($postId, 'beyondwords_language_code', true));
        $this->assertSame($summaryVoiceId, get_post_meta($postId, 'beyondwords_summary_voice_id', true));
        $this->assertSame($titleVoiceId, get_post_meta($postId, 'beyondwords_title_voice_id', true));
        $this->assertSame($bodyVoiceId, get_post_meta($postId, 'beyondwords_body_voice_id', true));

        wp_delete_post($postId, true);
    }

    public function processResponseProvider() {
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
    public function registerMeta()
    {
        Core::registerMeta();

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

    function removeDeleteActions($core) {
        // Actions for deleting/trashing/restoring posts
        remove_action('before_delete_post', array($core, 'onDeletePost'));
    }

    /**
     * @test
     * @dataProvider langCodes
     */
    public function getLangCodeFromJsonIfEmpty($language_id, $language_code) {
        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::getLangCodeFromJsonIfEmpty',
            'meta_input' => [
                'beyondwords_language_id' => $language_id,
            ],
        ]);

        $this->assertSame('foo', Core::getLangCodeFromJsonIfEmpty('foo', $postId, 'beyondwords_language_foo', true));
        $this->assertSame('bar', Core::getLangCodeFromJsonIfEmpty('bar', $postId, 'beyondwords_language_code', true));
        $this->assertSame(["$language_code"], Core::getLangCodeFromJsonIfEmpty('', $postId, 'beyondwords_language_code', true));
    }

    public function langCodes()
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
     * @dataProvider shouldGenerateAudioForPostProvider
     */
    public function shouldGenerateAudioForPost($expect, $post_status, $meta_input) {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::shouldGenerateAudioForPost',
            'post_status' => $post_status,
            'meta_input' => $meta_input,
        ]);

        $this->assertSame($expect, Core::shouldGenerateAudioForPost($postId));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        wp_delete_post($postId, true);
    }

    public function shouldGenerateAudioForPostProvider()
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
}
