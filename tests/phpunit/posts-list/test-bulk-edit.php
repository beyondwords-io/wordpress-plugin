<?php

use BeyondWords\PostsList\BulkEdit;
use BeyondWords\Core\Plugin;
use BeyondWords\Post\Sync;

final class BulkEditTest extends TestCase
{
    /**
     * @var \BeyondWords\PostsList\BulkEdit
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

        // The redirect handlers gate on current_user_can( 'edit_post', ... ), so
        // several tests log a user in. Reset it so state cannot leak into sibling
        // test classes.
        wp_set_current_user(0);

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
        // The handler now filters $object_ids by current_user_can( 'edit_post' ),
        // so run as an administrator who can edit every selected post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
        // The handler now filters $object_ids by current_user_can( 'edit_post' ),
        // so run as an administrator who can edit every selected post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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

    /**
     * A user with `edit_posts` may still lack `edit_post` for an individual post
     * they do not own. Core routes custom bulk actions through this filter after
     * only the coarse edit_posts gate, so the redirect-based generate handler must
     * itself drop posts the user cannot edit — only the editable post is flagged
     * for generation and counted. Mirrors the AJAX coverage in test-bulk-edit-ajax.php.
     *
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action_skips_posts_the_user_cannot_edit()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::generate-skip::own',
        ]);

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::generate-skip::other',
        ]);

        // Intercept the create-audio request with a benign success so generation
        // completes hermetically, without a real network call.
        $mockHttp = function () {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $mockHttp, 10, 3);

        $redirect = BulkEdit::handle_bulk_generate_action(
            'https://example.com/wp-admin/edit.php',
            'beyondwords_generate_audio',
            [$ownPostId, $otherPostId]
        );

        remove_filter('pre_http_request', $mockHttp, 10);

        // Only the editable post is flagged for generation.
        $this->assertSame('1', get_post_meta($ownPostId, 'beyondwords_generate_audio', true));
        $this->assertEmpty(get_post_meta($otherPostId, 'beyondwords_generate_audio', true));

        // generated + failed counts only the one editable post.
        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);
        $this->assertEquals(
            1,
            (int)$args['beyondwords_bulk_generated'] + (int)$args['beyondwords_bulk_failed']
        );

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * When the per-post filter removes every selected post, the generate handler
     * must be a clean no-op: a zero count, no post mutated, and no API request.
     *
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action_is_a_noop_when_no_editable_posts_remain()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::generate-noop::other',
        ]);

        $httpAttempted = false;
        $filter = function ($preempt) use (&$httpAttempted) {
            $httpAttempted = true;
            return new WP_Error('blocked', 'No HTTP expected in this no-op test.');
        };
        add_filter('pre_http_request', $filter, 10, 1);

        $redirect = BulkEdit::handle_bulk_generate_action(
            'https://example.com/wp-admin/edit.php',
            'beyondwords_generate_audio',
            [$otherPostId]
        );

        remove_filter('pre_http_request', $filter, 10);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertEquals(0, $args['beyondwords_bulk_generated']);
        $this->assertEquals(0, $args['beyondwords_bulk_failed']);
        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertFalse($httpAttempted, 'No API request should be made when no editable posts remain.');
        $this->assertEmpty(get_post_meta($otherPostId, 'beyondwords_generate_audio', true));

        wp_delete_post($otherPostId, true);
    }

    /**
     * On the delete branch the per-post `edit_post` filter must drop posts the
     * user cannot edit, so the API batch-delete request only ever carries the
     * editable post's content — and only that post's meta is cleared.
     *
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_delete_action_skips_posts_the_user_cannot_edit()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::delete-skip::own',
        ]);
        update_post_meta($ownPostId, 'beyondwords_project_id', 12345);
        update_post_meta($ownPostId, 'beyondwords_content_id', 'own-content-aaa');

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::delete-skip::other',
        ]);
        update_post_meta($otherPostId, 'beyondwords_project_id', 12345);
        update_post_meta($otherPostId, 'beyondwords_content_id', 'other-content-bbb');

        $requests = [];
        $mockHttp = function ($preempt, $args, $url) use (&$requests) {
            if (str_contains($url, '/content/batch_delete')) {
                $requests[] = $args['body'] ?? '';
                return [
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'body'     => '{}',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }

            return $preempt;
        };
        add_filter('pre_http_request', $mockHttp, 10, 3);

        $redirect = BulkEdit::handle_bulk_delete_action(
            'https://example.com/wp-admin/edit.php',
            'beyondwords_delete_audio',
            [$ownPostId, $otherPostId]
        );

        remove_filter('pre_http_request', $mockHttp, 10);

        // Exactly one batch-delete request, carrying only the editable post's content.
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('own-content-aaa', $requests[0]);
        $this->assertStringNotContainsString('other-content-bbb', $requests[0]);

        // Only the editable post's meta is cleared; the other post is untouched.
        $this->assertSame('', get_post_meta($ownPostId, 'beyondwords_content_id', true));
        $this->assertSame('other-content-bbb', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        // The redirect reports exactly one deleted post.
        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);
        $this->assertEquals(1, $args['beyondwords_bulk_deleted']);

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * When the per-post filter removes every selected post, the delete handler
     * must be a clean no-op: a zero count, no API request, and — critically — no
     * BULK-NO-RESPONSE error from handing an empty batch to the API.
     *
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_delete_action_is_a_noop_when_no_editable_posts_remain()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::delete-noop::other',
        ]);
        update_post_meta($otherPostId, 'beyondwords_project_id', 12345);
        update_post_meta($otherPostId, 'beyondwords_content_id', 'noop-content-id');

        $httpAttempted = false;
        $filter = function ($preempt) use (&$httpAttempted) {
            $httpAttempted = true;
            return new WP_Error('blocked', 'No HTTP expected in this no-op test.');
        };
        add_filter('pre_http_request', $filter, 10, 1);

        $redirect = BulkEdit::handle_bulk_delete_action(
            'https://example.com/wp-admin/edit.php',
            'beyondwords_delete_audio',
            [$otherPostId]
        );

        remove_filter('pre_http_request', $filter, 10);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        // Zero deleted, no error surfaced, and no API request attempted.
        $this->assertEquals(0, $args['beyondwords_bulk_deleted']);
        $this->assertArrayNotHasKey('beyondwords_bulk_error', $args);
        $this->assertFalse($httpAttempted, 'No API delete should be attempted when no editable posts remain.');
        $this->assertSame('noop-content-id', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($otherPostId, true);
    }

    /**
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action_defers_beyond_sync_cap()
    {
        // The handler gates on current_user_can( 'edit_post', ... ), so run as an
        // administrator who can edit every selected post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        // Run off VIP (default) with no API credentials, so the capped posts fail
        // without a network call and the overflow is deferred. Select two more
        // than the cap and derive the expectations from the constant.
        $overflow = 2;
        $selected = Sync::BULK_GENERATE_SYNC_LIMIT + $overflow;

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $postIds = [];
        for ($i = 0; $i < $selected; $i++) {
            $postIds[] = self::factory()->post->create(['post_status' => 'publish']);
        }

        $nonce = wp_create_nonce('beyondwords_bulk_edit_result');
        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, 'https://example.com/wp-admin/edit.php');

        $redirect = BulkEdit::handle_bulk_generate_action($redirect, 'beyondwords_generate_audio', $postIds);

        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $args);

        // Capped posts processed (all failed, no creds) + overflow deferred.
        $this->assertSame('0', $args['beyondwords_bulk_generated']);
        $this->assertSame((string) Sync::BULK_GENERATE_SYNC_LIMIT, $args['beyondwords_bulk_failed']);
        $this->assertSame((string) $overflow, $args['beyondwords_bulk_deferred']);

        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     *
     * @backupGlobals disabled
     */
    public function handle_bulk_generate_action_ignores_other_actions()
    {
        $redirect = 'https://example.com/wp-admin/edit.php';

        // A non-BeyondWords bulk action must be returned untouched.
        $result = BulkEdit::handle_bulk_generate_action($redirect, 'trash', [1, 2, 3]);

        $this->assertSame($redirect, $result);
    }
}
