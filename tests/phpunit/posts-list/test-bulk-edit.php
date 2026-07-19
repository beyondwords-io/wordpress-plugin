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
        parent::setUp();
        unset($_POST, $_REQUEST);
    }

    public function tearDown(): void
    {
        unset($_POST, $_REQUEST);

        // Several tests log a user in; reset so state cannot leak into sibling test classes.
        wp_set_current_user(0);

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

        $this->assertEquals(10, has_filter('bulk_actions-edit-post', array(BulkEdit::class, 'bulk_actions_edit')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array(BulkEdit::class, 'handle_bulk_delete_action')));
        $this->assertEquals(10, has_filter('handle_bulk_actions-edit-post', array(BulkEdit::class, 'handle_bulk_generate_action')));

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
        // The handler filters $object_ids by current_user_can('edit_post'), so run as an admin.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postIds = [
            // Skip (because we slice array below)
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::skip-1',
                'post_content' => 'Test content for skip-1.',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-1',
                'post_content' => 'Test content for create-1.',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-2',
                'post_content' => 'Test content for create-2.',
            ]),
            self::factory()->post->create([
                'post_title' => 'BulkEditTest::handle_bulk_generate_action::create-3',
                'post_content' => 'Test content for create-3.',
            ]),
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

        $redirect = add_query_arg('beyondwords_bulk_edit_result_nonce', $nonce, $redirect);

        $redirect = BulkEdit::handle_bulk_generate_action($redirect, 'beyondwords_generate_audio', $selectedPostIds);

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);

        $this->assertArrayHasKey('beyondwords_bulk_edit_result_nonce', $args);
        $this->assertArrayHasKey('beyondwords_bulk_generated', $args);
        $this->assertArrayHasKey('beyondwords_bulk_failed', $args);

        $total = (int)$args['beyondwords_bulk_generated'] + (int)$args['beyondwords_bulk_failed'];
        $this->assertEquals(count($selectedPostIds), $total);

        foreach ($selectedPostIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

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
        // The handler filters $object_ids by current_user_can('edit_post'), so run as an admin.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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

        $this->assertEquals(0, $args['beyondwords_bulk_generated']);
        $this->assertEquals(count($postIds), $args['beyondwords_bulk_failed']);

        // The generate_audio flag is set even though generation failed.
        foreach ($postIds as $postId) {
            $this->assertEquals('1', get_post_meta($postId, 'beyondwords_generate_audio', true));
        }

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * Core runs custom bulk actions after only the coarse edit_posts gate, so the handler
     * itself must drop posts the user cannot edit. Mirrors test-bulk-edit-ajax.php.
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

        // Benign success stub so generation completes without a real network call.
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
     * On delete the per-post `edit_post` filter must drop uneditable posts, so the API
     * batch-delete only carries the editable post's content — and only its meta clears.
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

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('own-content-aaa', $requests[0]);
        $this->assertStringNotContainsString('other-content-bbb', $requests[0]);

        $this->assertSame('', get_post_meta($ownPostId, 'beyondwords_content_id', true));
        $this->assertSame('other-content-bbb', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        $query = parse_url($redirect, PHP_URL_QUERY);
        parse_str($query, $args);
        $this->assertEquals(1, $args['beyondwords_bulk_deleted']);

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * When the per-post filter removes every post, delete must be a clean no-op: zero
     * count, no API request, and no BULK-NO-RESPONSE error from an empty batch.
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

        $this->assertEquals(0, $args['beyondwords_bulk_deleted']);
        $this->assertArrayNotHasKey('beyondwords_bulk_error', $args);
        $this->assertFalse($httpAttempted, 'No API delete should be attempted when no editable posts remain.');
        $this->assertSame('noop-content-id', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($otherPostId, true);
    }
}
