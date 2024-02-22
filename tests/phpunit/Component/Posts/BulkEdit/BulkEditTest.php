<?php

use Beyondwords\Wordpress\Component\Posts\BulkEdit\BulkEdit;
use Beyondwords\Wordpress\Core\Core;
use Beyondwords\Wordpress\Plugin;

final class BulkEditTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Posts\BulkEdit\BulkEdit
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        unset($_POST, $_REQUEST);

        // Your set up methods here.
        $this->_instance = new BulkEdit();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_REQUEST);

        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $bulkEdit = new BulkEdit();
        $bulkEdit->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('bulk_edit_custom_box', array($bulkEdit, 'bulkEditCustomBox')));
        $this->assertEquals(10, has_action('wp_ajax_save_bulk_edit_beyondwords', array($bulkEdit, 'saveBulkEdit')));

        // Post type: post
        $this->assertEquals(10, has_filter('bulk_actions-edit-post', array($bulkEdit, 'bulkActionsEdit')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array($bulkEdit, 'handleBulkDeleteAction')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array($bulkEdit, 'handleBulkGenerateAction')));

        // Post type: page
        $this->assertEquals(10, has_filter('bulk_actions-edit-page', array($bulkEdit, 'bulkActionsEdit')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-page', array($bulkEdit, 'handleBulkDeleteAction')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-page', array($bulkEdit, 'handleBulkGenerateAction')));
    }

    /**
     * @test
     * @dataProvider bulkEditCustomBoxprovider
     */
    public function bulkEditCustomBox($columnName, $postType, $expectCustomBox)
    {
        $this->_instance->bulkEditCustomBox($columnName, $postType);

        $output = $this->getActualOutput();

        if ($expectCustomBox) {
            $this->assertStringContainsString('<span class="title">BeyondWords</span>', $output);
            $this->assertStringContainsString('<select name="beyondwords_generate_audio">', $output);
            $this->assertStringContainsString('<option value="-1">— No change —</option>', $output);
            $this->assertStringContainsString('<option value="generate">Generate audio</option>', $output);
            $this->assertStringContainsString('<option value="delete">Delete audio</option>', $output);
        } else {
            $this->assertEmpty($output);
        }
    }

    public function bulkEditCustomBoxprovider() {
        return [
            'Post' => [
                'columnName' => 'beyondwords',
                'postType' => 'post',
                'expectCustomBox' => true,
            ],
            'Page' => [
                'columnName' => 'beyondwords',
                'postType' => 'page',
                'expectCustomBox' => true,
            ],
            'Custom' => [
                'columnName' => 'beyondwords',
                'postType' => 'custom',
                'expectCustomBox' => false,
            ],
            'Different Column' => [
                'columnName' => 'foo',
                'postType' => 'post',
                'expectCustomBox' => false,
            ],
        ];
    }


    /**
     * @test
     */
    public function saveBulkEditWithoutNonce()
    {
        $this->expectException(\WPDieException::class);

        $updatedPostIds = $this->_instance->saveBulkEdit();
    }

    /**
     * @test
     */
    public function saveBulkEditWithInvalidNonce()
    {
        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';

        $this->expectException(\WPDieException::class);

        $updatedPostIds = $this->_instance->saveBulkEdit();
    }

    /**
     * @test
     */
    public function saveBulkEditWithoutBulkEditAction()
    {
        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithoutGenerateAudio::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithoutGenerateAudio::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithoutGenerateAudio::3',
            ]),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['post_type'] = 'post';
        $_POST['post_ids'] = $postIds;

        $updatedPostIds = $this->_instance->saveBulkEdit();

        $this->assertSame([], $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function saveBulkEditWithInvalidBulkEditAction()
    {
        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithInvalidGenerateAudio::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithInvalidGenerateAudio::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEditWithInvalidGenerateAudio::3',
            ]),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'foo';
        $_POST['post_type'] = 'post';
        $_POST['post_ids'] = $postIds;

        $updatedPostIds = $this->_instance->saveBulkEdit();

        $this->assertSame([], $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function saveBulkEditWithoutPostIds()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_type'] = 'post';

        $updatedPostIds = $this->_instance->saveBulkEdit();

        $this->assertSame([], $updatedPostIds);
    }

    /**
     * @test
     */
    public function saveBulkEdit()
    {
        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEdit::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEdit::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::saveBulkEdit::3',
            ]),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = $postIds;

        $this->_instance->saveBulkEdit();

        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));

            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function generateAudioForPosts()
    {
        $updatedPostIds = $this->_instance->generateAudioForPosts(null);

        $this->assertFalse($updatedPostIds);

        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generateAudioForPosts::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generateAudioForPosts::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generateAudioForPosts::3',
            ]),
        ];

        $updatedPostIds = $this->_instance->generateAudioForPosts($postIds);

        $this->assertSame($postIds, $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function bulkActionsEdit()
    {
        $bulkArray = [];

        $newBulkArray = $this->_instance->bulkActionsEdit($bulkArray);

        $this->assertContains('Generate audio', $newBulkArray);
        $this->assertContains('Delete audio', $newBulkArray);
        $this->assertCount(count($bulkArray) + 2, $newBulkArray);
    }

    /**
     * @test
     *
     * @backupGlobals disabled
     */
    public function handleBulkGenerateAction()
    {
        global $beyondwords_wordpress_plugin;

        $postIds = [
            // Skip (because we slice array below)
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::skip-1',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::create-1',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::create-2',
            ]),
            // Update
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::update-1',
                'meta_input' => [
                    'beyondwords_project_id' => '1234',
                    'beyondwords_content_id' => '12345678',
                ],
            ]),
            // Update
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::update-2',
                'meta_input' => [
                    'beyondwords_project_id' => '1234',
                    'beyondwords_podcast_id' => '12345678',
                ],
            ]),
            // Skip (because we slice array below)
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateAction::skip-2',
                'meta_input' => [
                    'beyondwords_project_id' => '1234',
                    'beyondwords_content_id' => '12345678',
                ],
            ]),
        ];

        // Simulate the middle 4 posts being checked in the UI
        $selectedPostIds = array_slice($postIds, 1, 4);

        // Use mock
        $core = $this->getMockBuilder(Core::class)
            ->onlyMethods(['init', 'generateAudioForPost'])
            ->disableOriginalConstructor()
            ->getMock();

        $core->method('generateAudioForPost')
            ->willReturn(true);

        $core->expects($this->exactly(4))
            ->method('generateAudioForPost')
            ->withConsecutive(
                [$this->equalTo($postIds[1])],
                [$this->equalTo($postIds[2])],
                [$this->equalTo($postIds[3])],
                [$this->equalTo($postIds[4])]
            );

        // Get mock
        $beyondwords_wordpress_plugin = $this->getMockBuilder(Plugin::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $beyondwords_wordpress_plugin->core = $core;

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        // beyondwords_bulk_generated should be updated with the no. of posts processed (so 99 becomes 4)
        $redirect = 'https://example.com/wp-admin/posts?beyondwords_bulk_generated=99';

        // Add nonce into redirect
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = $this->_instance->handleBulkGenerateAction($redirect, 'beyondwords_generate_audio', $selectedPostIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertEquals(count($selectedPostIds), $args['beyondwords_bulk_generated']);
        $this->assertArrayNotHasKey('beyondwords_bulk_error', $args);

        foreach ($selectedPostIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }

        unset($beyondwords_wordpress_plugin);
    }

    /**
     * @test
     *
     * @backupGlobals disabled
     */
    public function handleBulkGenerateActionWithNoPluginError()
    {
        global $beyondwords_wordpress_plugin;

        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoPluginError::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoPluginError::2',
                'meta_input' => [
                    'beyondwords_project_id' => '1234',
                    'beyondwords_content_id' => '12345678',
                ],
            ]),
        ];

        // Use mock, with missing $beyondwords_wordpress_plugin->core
        $beyondwords_wordpress_plugin = $this->getMockBuilder(Plugin::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $beyondwords_wordpress_plugin->core = null;

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        // beyondwords_bulk_generated should be updated with the no. of posts processed (so 99 becomes 0)
        $redirect = 'https://example.com/wp-admin/posts?beyondwords_bulk_generated=99';

        // Add nonce into redirect
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = $this->_instance->handleBulkGenerateAction($redirect, 'beyondwords_generate_audio', $postIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertEquals(0, $args['beyondwords_bulk_generated']);

        $message = 'Error while bulk generating audio. Please contact support with reference BULK-NO-PLUGIN.';

        $this->assertArrayHasKey('beyondwords_bulk_error', $args);
        $this->assertEquals($message, $args['beyondwords_bulk_error']);

        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }

        unset($beyondwords_wordpress_plugin);
    }

    /**
     * @test
     *
     * @backupGlobals disabled
     */
    public function handleBulkGenerateActionWithNoResponseError()
    {
        global $beyondwords_wordpress_plugin;

        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoResponseError::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoResponseError::2',
                'meta_input' => [
                    'beyondwords_project_id' => '1234',
                    'beyondwords_content_id' => '12345678',
                ],
            ]),
        ];

        // Use mock
        $core = $this->getMockBuilder(Core::class)
            ->onlyMethods(['generateAudioForPost'])
            ->disableOriginalConstructor()
            ->getMock();

        $core->method('generateAudioForPost')
            ->willReturn(false);

        $core->expects($this->exactly(2))
            ->method('generateAudioForPost')
            ->withConsecutive(
                [$this->equalTo($postIds[0])],
                [$this->equalTo($postIds[1])]
            );

        // Get mock
        $beyondwords_wordpress_plugin = $this->getMockBuilder(Plugin::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $beyondwords_wordpress_plugin->core = $core;

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        // beyondwords_bulk_generated should be updated with the no. of posts processed (so 99 becomes 0)
        $redirect = 'https://example.com/wp-admin/posts?beyondwords_bulk_generated=99';

        // Add nonce into redirect
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = $this->_instance->handleBulkGenerateAction($redirect, 'beyondwords_generate_audio', $postIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertEquals(0, $args['beyondwords_bulk_generated']);

        $message = 'Error while bulk generating audio. Please contact support with reference BULK-NO-RESPONSE.';

        $this->assertArrayHasKey('beyondwords_bulk_failed', $args);
        $this->assertEquals(count($postIds), $args['beyondwords_bulk_failed']);

        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }

        unset($beyondwords_wordpress_plugin);
    }
}
