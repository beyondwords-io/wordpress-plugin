<?php

use BeyondWords\PostsList\BulkEdit;

/**
 * AJAX response/error handling for BulkEdit::save_bulk_edit().
 *
 * save_bulk_edit() always terminates the request via wp_send_json_success() /
 * wp_send_json_error() (both call wp_die()), so it is exercised through
 * WP_Ajax_UnitTestCase. That base pretends DOING_AJAX is true, routes wp_die()
 * to a handler that captures the JSON body into $this->_last_response and throws
 * a WPAjaxDie*Exception, and suppresses the "headers already sent" warning that
 * wp_send_json() would otherwise raise under PHPUnit.
 *
 * The non-AJAX BulkEdit tests live in test-bulk-edit.php.
 */
final class BulkEditAjaxTest extends WP_Ajax_UnitTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        $_POST = [];
        $this->_last_response = '';

        // save_bulk_edit() requires the edit_posts capability, so default these
        // tests to a capable user. Capability-specific tests override the role.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function tear_down(): void
    {
        $_POST = [];
        wp_set_current_user(0);

        parent::tear_down();
    }

    /**
     * Invoke save_bulk_edit() and return the decoded wp_send_json_* envelope.
     *
     * The handler always ends in a wp_die() (via wp_send_json_*() or
     * wp_nonce_ays()), which the AJAX harness converts into a WPDieException
     * after flushing any JSON body into $this->_last_response.
     *
     * @return array Decoded JSON envelope, or [] when no body was emitted.
     */
    private function getJsonResponse(): array
    {
        $this->_last_response = '';

        ob_start();

        try {
            BulkEdit::save_bulk_edit();
        } catch (\WPDieException $e) {
            // Expected: wp_send_json_*() / wp_nonce_ays() called wp_die().
        }

        return (array) json_decode($this->_last_response, true);
    }

    /**
     * @test
     */
    public function save_bulk_edit_dies_when_nonce_is_missing()
    {
        $this->expectException(\WPDieException::class);

        ob_start();

        BulkEdit::save_bulk_edit();
    }

    /**
     * @test
     */
    public function save_bulk_edit_dies_when_nonce_is_invalid()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = 'not-a-valid-nonce';
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [1, 2, 3];

        $this->expectException(\WPDieException::class);

        ob_start();

        BulkEdit::save_bulk_edit();
    }

    /**
     * @test
     */
    public function save_bulk_edit_returns_error_when_action_is_missing()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['post_ids'] = [1, 2, 3];

        $response = $this->getJsonResponse();

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Missing bulk-edit action or selected posts.',
            $response['data']['message']
        );
    }

    /**
     * @test
     */
    public function save_bulk_edit_returns_error_when_post_ids_are_missing()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';

        $response = $this->getJsonResponse();

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Missing bulk-edit action or selected posts.',
            $response['data']['message']
        );
    }

    /**
     * @test
     */
    public function save_bulk_edit_returns_error_for_an_unrecognised_action()
    {
        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'foo';
        $_POST['post_ids'] = [1, 2, 3];

        $response = $this->getJsonResponse();

        $this->assertFalse($response['success']);
        $this->assertSame('Unrecognised bulk-edit action.', $response['data']['message']);
    }

    /**
     * @test
     */
    public function save_bulk_edit_generate_returns_success_and_flags_posts()
    {
        $postIds = [
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::generate::1']),
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::generate::2']),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = $postIds;

        $response = $this->getJsonResponse();

        $this->assertTrue($response['success']);
        $this->assertSame($postIds, $response['data']);

        foreach ($postIds as $postId) {
            $this->assertSame('1', get_post_meta($postId, 'beyondwords_generate_audio', true));

            wp_delete_post($postId, true);
        }
    }

    /**
     * Regression test for the AJAX delete path.
     *
     * A 'delete' selection where no post has both a project_id and a content_id
     * makes Client::batch_delete_audio() throw. Previously the exception
     * propagated out of save_bulk_edit() uncaught, producing a PHP fatal /
     * HTTP 500 on admin-ajax. It must now be reported as a JSON error response.
     *
     * The exception is thrown before any HTTP request is attempted, so this
     * needs no API credentials or mock.
     *
     * @test
     */
    public function save_bulk_edit_delete_returns_error_when_no_post_has_beyondwords_data()
    {
        $postIds = [
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::delete-no-data::1']),
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::delete-no-data::2']),
        ];

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = $postIds;

        $response = $this->getJsonResponse();

        $this->assertFalse($response['success']);
        $this->assertSame(
            'None of the selected posts had valid BeyondWords audio data.',
            $response['data']['message']
        );

        foreach ($postIds as $postId) {
            wp_delete_post($postId, true);
        }
    }

    /**
     * @test
     */
    public function save_bulk_edit_delete_returns_success_and_clears_meta()
    {
        $postIds = [
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::delete::1']),
            self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::delete::2']),
        ];

        foreach ($postIds as $postId) {
            update_post_meta($postId, 'beyondwords_project_id', 12345);
            update_post_meta($postId, 'beyondwords_content_id', 'content-' . $postId);
        }

        // Mock the batch_delete API call so no real network request is made.
        $mockHttp = function ($preempt, $args, $url) {
            if (str_contains($url, '/content/batch_delete')) {
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

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = $postIds;

        $response = $this->getJsonResponse();

        remove_filter('pre_http_request', $mockHttp, 10);

        $this->assertTrue($response['success']);
        $this->assertSame($postIds, $response['data']);

        foreach ($postIds as $postId) {
            $this->assertSame('', get_post_meta($postId, 'beyondwords_project_id', true));
            $this->assertSame('', get_post_meta($postId, 'beyondwords_content_id', true));

            wp_delete_post($postId, true);
        }
    }

    /**
     * A nonce proves intent, not authorisation. A logged-in user without the
     * `edit_posts` capability (e.g. a Subscriber) must get a 403 JSON error,
     * before any post meta is touched.
     *
     * @test
     */
    public function save_bulk_edit_generate_returns_error_for_users_without_edit_posts_capability()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $postId = self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::generate-no-cap']);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [$postId];

        // Nonce is valid for this user, so the *only* thing that can stop the
        // request is the capability gate — not the nonce check.
        $this->assertNotFalse(wp_verify_nonce($_POST['beyondwords_bulk_edit_nonce'], 'beyondwords_bulk_edit'));

        $response = $this->getJsonResponse();

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('not allowed to bulk edit', $response['data']['message']);
        $this->assertEmpty(get_post_meta($postId, 'beyondwords_generate_audio', true));

        wp_delete_post($postId, true);
    }

    /**
     * The delete branch is the most destructive path. A Subscriber must get a
     * 403 JSON error before delete_audio_for_posts() — and any API request — runs.
     *
     * @test
     */
    public function save_bulk_edit_delete_returns_error_for_users_without_edit_posts_capability()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $postId = self::factory()->post->create(['post_title' => 'BulkEditAjaxTest::delete-no-cap']);
        update_post_meta($postId, 'beyondwords_project_id', 12345);
        update_post_meta($postId, 'beyondwords_content_id', 'content-no-cap');

        $httpAttempted = false;
        $filter = function ($preempt) use (&$httpAttempted) {
            $httpAttempted = true;
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 10, 1);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = [$postId];

        $response = $this->getJsonResponse();

        remove_filter('pre_http_request', $filter, 10);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('not allowed to bulk edit', $response['data']['message']);
        $this->assertFalse($httpAttempted, 'No API delete should be attempted for an unauthorized user.');
        // Nothing was deleted.
        $this->assertSame('content-no-cap', get_post_meta($postId, 'beyondwords_content_id', true));

        wp_delete_post($postId, true);
    }

    /**
     * A user with `edit_posts` may still lack `edit_post` for an individual post
     * they do not own. On generate, those posts are filtered out; editable ones
     * are still processed.
     *
     * @test
     */
    public function save_bulk_edit_generate_skips_posts_the_user_cannot_edit()
    {
        $authorId      = self::factory()->user->create(['role' => 'author']);
        $otherAuthorId = self::factory()->user->create(['role' => 'author']);

        wp_set_current_user($authorId);

        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditAjaxTest::generate-skip::own',
        ]);

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditAjaxTest::generate-skip::other',
        ]);

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [$ownPostId, $otherPostId];

        $response = $this->getJsonResponse();

        $this->assertTrue($response['success']);
        $this->assertSame([$ownPostId], $response['data']);
        $this->assertSame('1', get_post_meta($ownPostId, 'beyondwords_generate_audio', true));
        $this->assertEmpty(get_post_meta($otherPostId, 'beyondwords_generate_audio', true));

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * On the delete branch the per-post `edit_post` filter must drop posts the
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

        $ownPostId = self::factory()->post->create([
            'post_author' => $authorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditAjaxTest::delete-skip::own',
        ]);
        update_post_meta($ownPostId, 'beyondwords_project_id', 12345);
        update_post_meta($ownPostId, 'beyondwords_content_id', 'own-content-aaa');

        $otherPostId = self::factory()->post->create([
            'post_author' => $otherAuthorId,
            'post_status' => 'publish',
            'post_title'  => 'BulkEditAjaxTest::delete-skip::other',
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

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'delete';
        $_POST['post_ids'] = [$ownPostId, $otherPostId];

        $response = $this->getJsonResponse();

        remove_filter('pre_http_request', $mockHttp, 10);

        // Only the editable post is processed.
        $this->assertTrue($response['success']);
        $this->assertSame([$ownPostId], $response['data']);

        // Exactly one batch-delete request, carrying only the editable post's content.
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('own-content-aaa', $requests[0]);
        $this->assertStringNotContainsString('other-content-bbb', $requests[0]);

        // The editable post's meta is cleared; the other post is untouched.
        $this->assertSame('', get_post_meta($ownPostId, 'beyondwords_content_id', true));
        $this->assertSame('other-content-bbb', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($ownPostId, true);
        wp_delete_post($otherPostId, true);
    }

    /**
     * The per-post gate is `edit_post`, not mere ownership. A Contributor holds
     * `edit_posts` (clearing the coarse gate) but lacks `edit_published_posts`,
     * so cannot edit a *published* post even when they own it. Such a post is
     * filtered out, leaving a clean no-op (success with no processed IDs).
     *
     * @test
     */
    public function save_bulk_edit_blocks_contributor_on_own_published_post()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'contributor']));

        $this->assertTrue(current_user_can('edit_posts'), 'Contributor should clear the coarse edit_posts gate.');

        $ownPublishedPostId = self::factory()->post->create([
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'post_title'  => 'BulkEditAjaxTest::contributor-own-published',
        ]);

        $this->assertFalse(
            current_user_can('edit_post', $ownPublishedPostId),
            'Contributor should not be able to edit their own published post.'
        );

        $_POST['beyondwords_bulk_edit_nonce'] = wp_create_nonce('beyondwords_bulk_edit');
        $_POST['beyondwords_bulk_edit'] = 'generate';
        $_POST['post_ids'] = [$ownPublishedPostId];

        $response = $this->getJsonResponse();

        // Filtered out by the per-post capability check — no mutation.
        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertEmpty(get_post_meta($ownPublishedPostId, 'beyondwords_generate_audio', true));

        wp_delete_post($ownPublishedPostId, true);
    }

    /**
     * When the per-post filter removes every selected post, the delete branch
     * must be a clean no-op (success, no IDs) — no API request, and not the
     * empty-batch error the delete helper would otherwise throw.
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
            'post_title'  => 'BulkEditAjaxTest::delete-noop::other',
        ]);
        update_post_meta($otherPostId, 'beyondwords_project_id', 12345);
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

        $response = $this->getJsonResponse();

        remove_filter('pre_http_request', $filter, 10);

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertFalse($httpAttempted, 'No API delete should be attempted when no editable posts remain.');
        $this->assertSame('noop-content-id', get_post_meta($otherPostId, 'beyondwords_content_id', true));

        wp_delete_post($otherPostId, true);
    }
}
