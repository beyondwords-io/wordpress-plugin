<?php

use BeyondWords\AdminPosts\BulkEdit;
use BeyondWords\Core\Plugin;

final class BulkEditTest extends TestCase
{
    /**
     * @var \BeyondWords\AdminPosts\BulkEdit
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        unset($_POST, $_REQUEST);

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_REQUEST);

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        BulkEdit::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('bulk_edit_custom_box', array(BulkEdit::class, 'bulk_edit_custom_box')));
        $this->assertEquals(10, has_action('wp_ajax_save_bulk_edit_beyondwords', array(BulkEdit::class, 'save_bulk_edit')));

        // Post type: post
        $this->assertEquals(10, has_filter('bulk_actions-edit-post', array(BulkEdit::class, 'bulk_actions_edit')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array(BulkEdit::class, 'handle_bulk_delete_action')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array(BulkEdit::class, 'handle_bulk_generate_action')));

        // Post type: page
        $this->assertEquals(10, has_filter('bulk_actions-edit-page', array(BulkEdit::class, 'bulk_actions_edit')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-page', array(BulkEdit::class, 'handle_bulk_delete_action')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-page', array(BulkEdit::class, 'handle_bulk_generate_action')));
    }

    /**
     * @test
     * @dataProvider bulk_edit_custom_boxprovider
     */
    public function bulk_edit_custom_box($columnName, $postType, $expectCustomBox)
    {
        $output = $this->capture_output(function () use ($columnName, $postType) {
            BulkEdit::bulk_edit_custom_box($columnName, $postType);
        });

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

    public function bulk_edit_custom_boxprovider() {
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
    public function save_bulk_edit_without_nonce()
    {
        $this->expectException(\WPDieException::class);

        BulkEdit::save_bulk_edit();
    }

    /**
     * @test
     */
    public function save_bulk_edit_with_invalid_nonce()
    {
        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';

        $this->expectException(\WPDieException::class);

        BulkEdit::save_bulk_edit();
    }

    /**
     * @test
     */
    public function save_bulk_edit_without_bulk_edit_action()
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

        $updatedPostIds = BulkEdit::save_bulk_edit();

        $this->assertSame([], $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function save_bulk_edit_with_invalid_bulk_edit_action()
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

        $updatedPostIds = BulkEdit::save_bulk_edit();

        $this->assertSame([], $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function save_bulk_edit_without_post_ids()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_type'] = 'post';

        $updatedPostIds = BulkEdit::save_bulk_edit();

        $this->assertSame([], $updatedPostIds);
    }

    /**
     * @test
     */
    public function save_bulk_edit()
    {
        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::save_bulk_edit::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::save_bulk_edit::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::save_bulk_edit::3',
            ]),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = $postIds;

        BulkEdit::save_bulk_edit();

        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));

            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function generate_audio_for_posts()
    {
        $updatedPostIds = BulkEdit::generate_audio_for_posts(null);

        $this->assertSame([], $updatedPostIds);

        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generate_audio_for_posts::1',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generate_audio_for_posts::2',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::generate_audio_for_posts::3',
            ]),
        ];

        $updatedPostIds = BulkEdit::generate_audio_for_posts($postIds);

        $this->assertSame($postIds, $updatedPostIds);

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function bulk_actions_edit()
    {
        $bulkArray = [];

        $newBulkArray = BulkEdit::bulk_actions_edit($bulkArray);

        $this->assertContains('Generate audio', $newBulkArray);
        $this->assertContains('Delete audio', $newBulkArray);
        $this->assertCount(count($bulkArray) + 2, $newBulkArray);
    }

    /**
     * @test
     * @group integration
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action()
    {
        // Set up API credentials for integration test
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postIds = [
            // Skip (because we slice array below)
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::skip-1',
                'post_content' => 'Test content for skip-1.',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-1',
                'post_content' => 'Test content for create-1.',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-2',
                'post_content' => 'Test content for create-2.',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-3',
                'post_content' => 'Test content for create-3.',
            ]),
            // Create
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-4',
                'post_content' => 'Test content for create-4.',
            ]),
            // Skip (because we slice array below)
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::skip-2',
                'post_content' => 'Test content for skip-2.',
            ]),
        ];

        // Simulate the middle 4 posts being checked in the UI
        $selectedPostIds = array_slice($postIds, 1, 4);

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');

        // beyondwords_bulk_generated should be updated with the no. of posts processed (so 99 becomes 4)
        $redirect = 'https://example.com/wp-admin/posts?beyondwords_bulk_generated=99';

        // Add nonce into redirect
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = BulkEdit::handle_bulk_generate_action($redirect, 'beyondwords_generate_audio', $selectedPostIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertArrayHasKey('beyondwords_bulk_failed', $args);

        // Verify that generated + failed equals the number of selected posts
        $total = (int)$args['beyondwords_bulk_generated'] + (int)$args['beyondwords_bulk_failed'];
        $this->assertEquals(count($selectedPostIds), $total);

        // Verify all selected posts have the generate_audio flag set
        foreach ($selectedPostIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        // Clean up
        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @group integration
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action_with_no_api_credentials()
    {
        // Test error handling when API credentials are missing
        // This tests the failure path where posts cannot be generated

        $postIds = [
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoApiCredentials::1',
                'post_content' => 'Test content 1.',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handleBulkGenerateActionWithNoApiCredentials::2',
                'post_content' => 'Test content 2.',
            ]),
        ];

        // Ensure no API credentials are set
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');
        $redirect = 'https://example.com/wp-admin/posts?beyondwords_bulk_generated=99';
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = BulkEdit::handle_bulk_generate_action($redirect, 'beyondwords_generate_audio', $postIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertArrayHasKey('beyondwords_bulk_failed', $args);

        // When API credentials are missing, all posts should fail
        $this->assertEquals(0, $args['beyondwords_bulk_generated']);
        $this->assertEquals(count($postIds), $args['beyondwords_bulk_failed']);

        // Verify all posts have the generate_audio flag set (even though they failed)
        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        // Clean up
        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }
}
