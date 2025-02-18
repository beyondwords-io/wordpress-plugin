<?php

declare(strict_types=1);

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
        $core = new Core();
        $core->init();

        // Actions
        $this->assertEquals(1,  has_action('enqueue_block_editor_assets', array($core, 'enqueueBlockEditorAssets')));
        $this->assertEquals(10, has_action('init', array($core, 'loadPluginTextdomain')));
        $this->assertEquals(99, has_action('init', array($core, 'registerMeta')));

        // Actions for adding/updating posts
        $this->assertEquals(99, has_action('wp_after_insert_post', array($core, 'onAddOrUpdatePost')));

        // Actions for deleting/trashing/restoring posts
        $this->assertEquals(10, has_action('before_delete_post', array($core, 'onTrashOrDeletePost')));
        $this->assertEquals(10, has_action('trashed_post', array($core, 'onTrashOrDeletePost')));
        $this->assertEquals(10, has_action('untrashed_post', array($core, 'onUntrashPost')));

        $this->assertEquals(10, has_action('is_protected_meta', array($core, 'isProtectedMeta')));
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

        $core = new Core();

        $this->assertNotFalse($core->generateAudioForPost($postId));

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
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $core = new Core();

        $this->assertNotFalse($core->generateAudioForPost($postId));

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
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $core = new Core();

        $this->assertNotFalse($core->generateAudioForPost($postId));

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

        $core = new Core();

        set_current_screen( 'edit-post' );
        $current_screen = get_current_screen();
        $current_screen->is_block_editor( true );

        $this->assertNull($wp_scripts);

        /**
         * Enqueuing without a valid API connection should do nothing
         */
        $core->enqueueBlockEditorAssets();

        $this->assertNull($wp_scripts);

        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        /**
         * Enqueuing with a valid API connection should succeed
         */
        $core->enqueueBlockEditorAssets();

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

    public function deleteResponse()
    {
        return [
            'delete response' => [
                [
                    'id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    'external_id' => 42,
                    'state' => 'unprocessed',
                    'media' => [],
                    'image_url' => '',
                    'deleted' => true,
                    'access_key' => 'abcd9969abcd9969abcd9969abcd9969',
                    'metadata' => [],
                ]
            ],
        ];
    }

    public function notFoundResponse()
    {
        return [
            'Not found response' => [
                [
                    'type' => 'application_error',
                    'message' => 'Record not found',
                    'context' => new StdClass(),
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

        $core = new Core();

        $this->assertFalse($core->generateAudioForPost($postId));

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

        $core = new Core();

        $this->assertFalse($core->generateAudioForPost($postId));

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

        $core = new Core();

        $this->assertFalse($core->generateAudioForPost($revisionId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
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

        $core = new Core();

        $this->assertFalse($core->generateAudioForPost($postId));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group trash
     * @dataProvider deleteResponse
     */
    public function onTrashOrDeletePost($expectedResponse)
    {
        $this->markTestIncomplete();

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashOrDeletePost',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $core = new Core();

        $response = $core->onTrashOrDeletePost($postId, 'publish');

        $this->assertSame('', get_post_meta($postId, 'beyondwords_error_message', true));
        $this->assertSame(BEYONDWORDS_TESTS_PROJECT_ID, get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID, get_post_meta($postId, 'beyondwords_content_id', true));

        // Cleanup test data without affecting the expects($this->once())
        $this->removeDeleteActions($core);
        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group trash
     * @dataProvider successResponse
     */
    public function onTrashOrDeletePostHandlesInvalidResponse($expectedResponse)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashOrDeletePostHandlesInvalidResponse',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $core = new Core();

        $response = $core->onTrashOrDeletePost($postId, 'publish');

        $this->assertSame('Unable to delete audio from BeyondWords dashboard', get_post_meta($postId, 'beyondwords_error_message', true));

        // Cleanup test data without affecting the expects($this->once())
        $this->removeDeleteActions($core);
        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group trash
     * @dataProvider notFoundResponse
     */
    public function onTrashOrDeletePostWithoutBeyondwordsData($expectedResponse)
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::onTrashOrDeletePostWithoutBeyondwordsData',
        ]);

        $core = new Core();

        $response = $core->onTrashOrDeletePost($postId, 'publish');

        $this->assertSame('', get_post_meta($postId, 'beyondwords_error_message', true));

        // Cleanup test data without affecting the expects($this->once())
        $this->removeDeleteActions($core);
        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
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

        $core = new Core();

        $this->assertNotFalse($core->generateAudioForPost($postId));

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

        $core = new Core();

        $this->assertFalse($core->generateAudioForPost($postId));

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
     * @dataProvider processResponseProvider
     */
    public function processResponse($response, $expectProjectId, $expectContentId) {
        $core = new Core();

        $postId = self::factory()->post->create([
            'post_title' => 'CoreTest::processResponse',
        ]);

        $core->processResponse($response, BEYONDWORDS_TESTS_PROJECT_ID, $postId);

        $this->assertSame($expectProjectId, get_post_meta($postId, 'beyondwords_project_id', true));
        $this->assertSame($expectContentId, get_post_meta($postId, 'beyondwords_content_id', true));

        wp_delete_post($postId, true);
    }

    public function processResponseProvider() {
        return [
            'Response includes Content ID' => [
                'response' => ['id' => BEYONDWORDS_TESTS_CONTENT_ID],
                'expectProjectId' => BEYONDWORDS_TESTS_PROJECT_ID,
                'expectContentId' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
            'Response is not an array' => [
                'response' => new StdClass(),
                'expectProjectId' => '',
                'expectContentId' => '',
            ],
        ];
    }

    /**
     * @test
     */
    public function registerMeta()
    {
        $core = new Core();

        $core->registerMeta();

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
                'publish_post_to_speechkit'         => 'publish_post_to_speechkit',
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

        $this->assertArrayHasKey('publish_post_to_speechkit', $meta);
        $this->assertSame('publish_post_to_speechkit', get_post_meta($postId, 'publish_post_to_speechkit', true));

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
        remove_action('before_delete_post', array($core, 'onTrashOrDeletePost'));
        remove_action('trashed_post', array($core, 'onTrashOrDeletePost'));
        remove_action('untrashed_post', array($core, 'onUntrashPost'), 10, 2);
    }
}
