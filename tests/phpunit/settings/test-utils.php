<?php

declare(strict_types=1);

use BeyondWords\Settings\Utils;

class SettingsUtilsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');
    }

    public function tearDown(): void
    {
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');
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

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('Settings/ValidApiConnection', $errors);

        remove_filter('pre_http_request', $filter, 10);
    }

    /**
     * @test
     */
    public function add_settings_error_message_with_explicit_id()
    {
        Utils::add_settings_error_message('First error', 'Settings/Test');

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
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

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
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

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
        $this->assertCount(1, $errors);

        $key = array_key_first($errors);
        $this->assertNotSame('', $key);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', (string) $key);
    }
}
