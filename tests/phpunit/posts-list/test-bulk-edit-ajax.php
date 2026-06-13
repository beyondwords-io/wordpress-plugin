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
    }

    public function tear_down(): void
    {
        $_POST = [];

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
}
