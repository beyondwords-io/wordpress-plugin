<?php

declare(strict_types=1);

use BeyondWords\Settings\Utils;

class SettingsUtilsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        delete_transient('beyondwords_settings_errors');
        delete_transient(Utils::CONNECTION_CHECK_TRANSIENT);
    }

    public function tearDown(): void
    {
        delete_transient('beyondwords_settings_errors');
        delete_transient(Utils::CONNECTION_CHECK_TRANSIENT);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function get_compatible_post_types_filter()
    {
        $postTypes = array_values(get_post_types());

        $this->assertContains('post', $postTypes);
        $this->assertContains('page', $postTypes);
        $this->assertContains('attachment', $postTypes);
        $this->assertContains('revision', $postTypes);

        // Set the filter
        $filter = function($supportedPostTypes) {
            return [
                $supportedPostTypes[1],
                $supportedPostTypes[0],
                'another-post-type',
            ];
        };

        add_filter('beyondwords_settings_post_types', $filter);

        $postTypes = Utils::get_compatible_post_types();

        remove_filter('beyondwords_settings_post_types', $filter);

        $this->assertSame(['page', 'post', 'another-post-type'], $postTypes);
    }

    /**
     * @test
     */
    public function get_compatible_post_types_excludes_skip_list()
    {
        $postTypes = Utils::get_compatible_post_types();

        foreach (Utils::SKIP_POST_TYPES as $skip) {
            $this->assertNotContains($skip, $postTypes);
        }
    }

    /**
     * @test
     */
    public function get_compatible_post_types_drops_registered_types_missing_required_features()
    {
        register_post_type('bw_no_editor', [
            'public'   => true,
            'supports' => ['title'],
        ]);

        $postTypes = Utils::get_compatible_post_types();
        $this->assertNotContains('bw_no_editor', $postTypes);

        unregister_post_type('bw_no_editor');
    }

    /**
     * @test
     */
    public function post_type_supports_required_features_allows_unregistered_types()
    {
        $this->assertTrue(Utils::post_type_supports_required_features('not-registered'));
    }

    /**
     * @test
     */
    public function post_type_supports_required_features_returns_true_for_compliant_type()
    {
        register_post_type('bw_full_support', [
            'public'   => true,
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);

        $this->assertTrue(Utils::post_type_supports_required_features('bw_full_support'));

        unregister_post_type('bw_full_support');
    }

    /**
     * @test
     */
    public function post_type_supports_required_features_returns_false_when_a_feature_missing()
    {
        register_post_type('bw_partial_support', [
            'public'   => true,
            'supports' => ['title', 'editor'],
        ]);

        $this->assertFalse(Utils::post_type_supports_required_features('bw_partial_support'));

        unregister_post_type('bw_partial_support');
    }

    /**
     * @test
     */
    public function has_api_creds_returns_false_when_both_missing()
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $this->assertFalse(Utils::has_api_creds());
    }

    /**
     * @test
     */
    public function has_api_creds_returns_false_when_only_one_present()
    {
        update_option('beyondwords_api_key', 'abc');
        delete_option('beyondwords_project_id');
        $this->assertFalse(Utils::has_api_creds());

        delete_option('beyondwords_api_key');
        update_option('beyondwords_project_id', '53391');
        $this->assertFalse(Utils::has_api_creds());
    }

    /**
     * @test
     */
    public function has_api_creds_returns_true_when_both_present()
    {
        update_option('beyondwords_api_key', 'abc');
        update_option('beyondwords_project_id', '53391');

        $this->assertTrue(Utils::has_api_creds());
    }

    /**
     * @test
     */
    public function has_api_creds_rejects_whitespace_only_values()
    {
        update_option('beyondwords_api_key', "   \t\n");
        update_option('beyondwords_project_id', '   ');

        $this->assertFalse(Utils::has_api_creds());
    }

    /**
     * @test
     */
    public function has_valid_api_connection_mirrors_option()
    {
        delete_option('beyondwords_valid_api_connection');
        $this->assertFalse(Utils::has_valid_api_connection());

        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $this->assertTrue(Utils::has_valid_api_connection());
    }

    /**
     * @test
     */
    public function validate_api_connection_short_circuits_when_creds_missing()
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $this->assertFalse(Utils::validate_api_connection());
        $this->assertFalse(Utils::has_valid_api_connection());
    }

    /**
     * @test
     */
    public function validate_api_connection_stores_flag_on_success()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $filter = function ($preempt, $args, $url) {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":53391}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertTrue(Utils::validate_api_connection());
        $this->assertTrue(Utils::has_valid_api_connection());

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * @test
     */
    public function validate_api_connection_queues_error_on_failure()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $filter = function ($preempt, $args, $url) {
            return [
                'response' => ['code' => 401, 'message' => 'Unauthorized'],
                'body'     => '{"code":401,"message":"Unauthorized"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertFalse(Utils::validate_api_connection());
        $this->assertFalse(Utils::has_valid_api_connection());

        $errors = get_transient('beyondwords_settings_errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('Settings/ValidApiConnection', $errors);

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * A transient failure (5xx, timeout, DNS error) must NOT clear a
     * previously-valid connection flag — otherwise a brief API blip hides the
     * Integration and Preferences tabs and locks the operator out.
     *
     * @test
     */
    public function validate_api_connection_preserves_flag_on_server_error()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $filter = function ($preempt, $args, $url) {
            return [
                'response' => ['code' => 500, 'message' => 'Internal Server Error'],
                'body'     => '{"code":500,"message":"Internal Server Error"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertFalse(Utils::validate_api_connection());
        // The last known-good flag survives the blip.
        $this->assertTrue(Utils::has_valid_api_connection());

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * A transport-level failure returns a WP_Error (no HTTP status). It is
     * transient, so the connection flag must be preserved.
     *
     * @test
     */
    public function validate_api_connection_preserves_flag_on_wp_error()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $filter = function ($preempt, $args, $url) {
            return new \WP_Error('http_request_failed', 'cURL error 28: Operation timed out');
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertFalse(Utils::validate_api_connection());
        $this->assertTrue(Utils::has_valid_api_connection());

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * A 403 is a definitive auth failure (revoked key or wrong project), so it
     * clears the connection flag just like a 401.
     *
     * @test
     */
    public function validate_api_connection_clears_flag_on_forbidden()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $filter = function ($preempt, $args, $url) {
            return [
                'response' => ['code' => 403, 'message' => 'Forbidden'],
                'body'     => '{"code":403,"message":"Forbidden"}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertFalse(Utils::validate_api_connection());
        $this->assertFalse(Utils::has_valid_api_connection());

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * Removing credentials clears the connection flag — no creds, no
     * connection — without issuing an API request.
     *
     * @test
     */
    public function validate_api_connection_clears_flag_when_credentials_removed()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $this->assertFalse(Utils::validate_api_connection());
        $this->assertFalse(Utils::has_valid_api_connection());
    }

    /**
     * Within the throttle window the check is served from the last result
     * without issuing a second API request — this keeps the uncached remote
     * call off every settings-page render.
     *
     * @test
     */
    public function validate_api_connection_throttles_repeat_checks()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $calls  = 0;
        $filter = function ($preempt, $args, $url) use (&$calls) {
            $calls++;
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":' . BEYONDWORDS_TESTS_PROJECT_ID . '}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertTrue(Utils::validate_api_connection());
        // Second call within the window short-circuits — no new API request.
        $this->assertTrue(Utils::validate_api_connection());
        $this->assertSame(1, $calls);

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * Changing credentials busts the throttle so the new creds are validated
     * immediately rather than waiting out the window.
     *
     * @test
     */
    public function validate_api_connection_revalidates_when_credentials_change()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $calls  = 0;
        $filter = function ($preempt, $args, $url) use (&$calls) {
            $calls++;
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":' . BEYONDWORDS_TESTS_PROJECT_ID . '}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        $this->assertTrue(Utils::validate_api_connection());
        $this->assertSame(1, $calls);

        // Rotate the API key: the fingerprint differs, so validation runs again.
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY . '-rotated');
        $this->assertTrue(Utils::validate_api_connection());
        $this->assertSame(2, $calls);

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * @test
     */
    public function add_settings_error_message_with_explicit_id()
    {
        Utils::add_settings_error_message('First error', 'Settings/Test');

        $errors = get_transient('beyondwords_settings_errors');
        $this->assertIsArray($errors);
        $this->assertSame(['Settings/Test' => 'First error'], $errors);
    }

    /**
     * @test
     */
    public function add_settings_error_message_accumulates_multiple()
    {
        Utils::add_settings_error_message('A', 'Settings/A');
        Utils::add_settings_error_message('B', 'Settings/B');

        $errors = get_transient('beyondwords_settings_errors');
        $this->assertCount(2, $errors);
        $this->assertSame('A', $errors['Settings/A']);
        $this->assertSame('B', $errors['Settings/B']);
    }

    /**
     * @test
     */
    public function add_settings_error_message_generates_id_when_blank()
    {
        Utils::add_settings_error_message('Anonymous error');

        $errors = get_transient('beyondwords_settings_errors');
        $this->assertCount(1, $errors);

        $key = array_key_first($errors);
        $this->assertNotSame('', $key);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', (string) $key);
    }

    /**
     * Regression: queued errors must survive a non-persistent object cache.
     *
     * The default WordPress object cache is request-scoped, so the queue lives
     * in a transient (which falls back to the options table), not `wp_cache_*`,
     * to survive the redirect after a settings save. Flushing the object cache
     * models the fresh request the redirect triggers on a host with no
     * persistent cache drop-in; the error must still be readable afterwards.
     *
     * @test
     */
    public function add_settings_error_message_survives_object_cache_flush()
    {
        Utils::add_settings_error_message('Survives the redirect', 'Settings/Redirect');

        wp_cache_flush();

        $errors = get_transient('beyondwords_settings_errors');
        $this->assertIsArray($errors);
        $this->assertSame('Survives the redirect', $errors['Settings/Redirect'] ?? null);
    }
}
