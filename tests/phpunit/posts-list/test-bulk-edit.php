<?php

use BeyondWords\PostsList\BulkEdit;
use BeyondWords\Core\Plugin;

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
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
     * A nonce proves intent, not authorisation. A logged-in user without the
     * `edit_posts` capability (e.g. a Subscriber) must be rejected with a 403
     * before any post meta is touched.
     *
     * @test
     */
    public function save_bulk_edit_rejects_users_without_edit_posts_capability()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $postId = self::factory()->post->create([
            'post_title' => 'BulkEditTest::save_bulk_edit_rejects_users_without_edit_posts_capability',
        ]);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [$postId];

        $blocked = false;

        try {
            BulkEdit::save_bulk_edit();
        } catch (\WPDieException $e) {
            $blocked = true;
        }

        $this->assertTrue($blocked, 'A Subscriber should be blocked with wp_die() before any mutation.');
        $this->assertEmpty(get_post_meta($postId, 'beyondwords_generate_audio', true));

        wp_delete_post($postId, true);
    }

    /**
     * A user with the `edit_posts` capability may still lack `edit_post` for an
     * individual post they do not own. Those posts must be skipped, while posts
     * the user can edit are still processed.
     *
     * @test
     */
    public function save_bulk_edit_skips_posts_the_user_cannot_edit()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        // The author owns this post, so they may edit it.
        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::save_bulk_edit_skips_posts_the_user_cannot_edit::own',
        ]);

        // Owned by another author — this author lacks `edit_others_posts`.
        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::save_bulk_edit_skips_posts_the_user_cannot_edit::other',
        ]);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [$ownPostId, $otherPostId];

        $updatedPostIds = BulkEdit::save_bulk_edit();

        // Only the author's own post is processed; the other is filtered out.
        $this->assertSame([$ownPostId], $updatedPostIds);
        $this->assertEquals('1', get_post_meta($ownPostId, 'beyondwords_generate_audio', true));
        $this->assertEmpty(get_post_meta($otherPostId, 'beyondwords_generate_audio', true));

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * The `delete` branch is the most destructive path (live API delete + clearing
     * all BeyondWords meta). A Subscriber must be rejected with a 403 before
     * delete_audio_for_posts() — and therefore any API request — is reached.
     *
     * @test
     */
    public function save_bulk_edit_delete_rejects_users_without_edit_posts_capability()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $postId = self::factory()->post->create([
            'post_title' => 'BulkEditTest::save_bulk_edit_delete_rejects_subscriber',
        ]);
        update_post_meta($postId, 'beyondwords_project_id', 1);
        update_post_meta($postId, 'beyondwords_content_id', 'delete-subscriber-content-id');

        // Fail the test loudly if any HTTP request is attempted.
        $httpAttempted = false;
        $filter = function ($preempt) use (&$httpAttempted) {
            $httpAttempted = true;
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 10, 1);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = [$postId];

        $blocked = false;
        try {
            BulkEdit::save_bulk_edit();
        } catch (\WPDieException $e) {
            $blocked = true;
        }

        remove_filter('pre_http_request', $filter, 10);

        $this->assertTrue($blocked, 'A Subscriber should be blocked before the delete branch runs.');
        $this->assertFalse($httpAttempted, 'No API delete should be attempted for an unauthorized user.');
        // The content id must survive — nothing was deleted.
        $this->assertSame('delete-subscriber-content-id', get_post_meta($postId, 'beyondwords_content_id', true));

        wp_delete_post($postId, true);
    }

    /**
     * On the `delete` branch the per-post `edit_post` filter must drop posts the
     * user cannot edit, so the API batch-delete request only ever carries the
     * editable post's content — and only that post's meta is cleared.
     *
     * @test
     */
    public function save_bulk_edit_delete_skips_posts_the_user_cannot_edit()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        // The author owns this post (editable), with audio data to delete.
        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::save_bulk_edit_delete_skips::own',
        ]);
        update_post_meta($ownPostId, 'beyondwords_project_id', 1);
        update_post_meta($ownPostId, 'beyondwords_content_id', 'own-content-aaa');

        // Owned by another author — this author lacks `edit_others_posts`.
        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::save_bulk_edit_delete_skips::other',
        ]);
        update_post_meta($otherPostId, 'beyondwords_project_id', 1);
        update_post_meta($otherPostId, 'beyondwords_content_id', 'other-content-bbb');

        // Capture every batch-delete request and return a 200 so the helper
        // reports the sent IDs as deleted.
        $requests = [];
        $filter = function ($preempt, $args, $url) use (&$requests) {
            $requests[] = ['url' => $url, 'body' => $args['body'] ?? ''];
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = [$ownPostId, $otherPostId];

        $updatedPostIds = BulkEdit::save_bulk_edit();

        remove_filter('pre_http_request', $filter, 10);

        // Only the editable post is processed.
        $this->assertSame([$ownPostId], $updatedPostIds);

        // Exactly one batch-delete request, carrying only the editable post's content.
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('own-content-aaa', $requests[0]['body']);
        $this->assertStringNotContainsString('other-content-bbb', $requests[0]['body']);

        // The editable post's meta is cleared; the other post is untouched.
        $this->assertEmpty(get_post_meta($ownPostId, 'beyondwords_content_id', true));
        $this->assertSame('other-content-bbb', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * When the per-post filter removes every selected post (an author who picked
     * only posts they cannot edit), the handler must be a clean no-op — no API
     * request, no exception — rather than reaching the throwing empty-batch path.
     *
     * @test
     */
    public function save_bulk_edit_delete_is_a_noop_when_no_editable_posts_remain()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditTest::save_bulk_edit_delete_noop::other',
        ]);
        update_post_meta($otherPostId, 'beyondwords_project_id', 1);
        update_post_meta($otherPostId, 'beyondwords_content_id', 'noop-content-id');

        $httpAttempted = false;
        $filter = function ($preempt) use (&$httpAttempted) {
            $httpAttempted = true;
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 10, 1);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = [$otherPostId];

        $updatedPostIds = BulkEdit::save_bulk_edit();

        remove_filter('pre_http_request', $filter, 10);

        $this->assertSame([], $updatedPostIds);
        $this->assertFalse($httpAttempted, 'No API delete should be attempted when no editable posts remain.');
        $this->assertSame('noop-content-id', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($otherPostId, true);
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
