<?php

declare(strict_types=1);

use BeyondWords\Settings\Fields;
use BeyondWords\Settings\Tabs;
use \Symfony\Component\DomCrawler\Crawler;

class FieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');
    }

    public function tearDown(): void
    {
        delete_option(Fields::OPTION_API_KEY);
        delete_option(Fields::OPTION_PROJECT_ID);
        delete_option(Fields::OPTION_INTEGRATION_METHOD);
        delete_option(Fields::OPTION_PREPEND_EXCERPT);
        delete_option(Fields::OPTION_PLAYER_UI);
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Fields::init();

        $this->assertSame(10, has_action('admin_init', [Fields::class, 'register_authentication_fields']));
        $this->assertSame(10, has_action('admin_init', [Fields::class, 'register_integration_fields']));
        $this->assertSame(10, has_action('admin_init', [Fields::class, 'register_preferences_fields']));
    }

    /**
     * @test
     */
    public function register_authentication_fields()
    {
        global $wp_registered_settings, $wp_settings_fields;

        unregister_setting(Tabs::SETTINGS_GROUP_AUTHENTICATION, Fields::OPTION_API_KEY);
        unregister_setting(Tabs::SETTINGS_GROUP_AUTHENTICATION, Fields::OPTION_PROJECT_ID);
        $wp_settings_fields[Tabs::PAGE_AUTHENTICATION] = [];

        Fields::register_authentication_fields();

        $this->assertArrayHasKey(Fields::OPTION_API_KEY, $wp_registered_settings);
        $this->assertArrayHasKey(Fields::OPTION_PROJECT_ID, $wp_registered_settings);

        $section = $wp_settings_fields[Tabs::PAGE_AUTHENTICATION][Tabs::SECTION_AUTHENTICATION];
        $this->assertArrayHasKey('beyondwords-api-key', $section);
        $this->assertArrayHasKey('beyondwords-project-id', $section);
    }

    /**
     * @test
     */
    public function register_integration_fields()
    {
        global $wp_registered_settings, $wp_settings_fields;

        unregister_setting(Tabs::SETTINGS_GROUP_INTEGRATION, Fields::OPTION_INTEGRATION_METHOD);
        $wp_settings_fields[Tabs::PAGE_INTEGRATION] = [];

        Fields::register_integration_fields();

        $this->assertArrayHasKey(Fields::OPTION_INTEGRATION_METHOD, $wp_registered_settings);
        $this->assertArrayHasKey(
            'beyondwords-integration-method',
            $wp_settings_fields[Tabs::PAGE_INTEGRATION][Tabs::SECTION_INTEGRATION]
        );
    }

    /**
     * @test
     */
    public function register_preferences_fields()
    {
        global $wp_registered_settings, $wp_settings_fields;

        unregister_setting(Tabs::SETTINGS_GROUP_PREFERENCES, Fields::OPTION_PREPEND_EXCERPT);
        unregister_setting(Tabs::SETTINGS_GROUP_PREFERENCES, Fields::OPTION_PLAYER_UI);
        $wp_settings_fields[Tabs::PAGE_PREFERENCES] = [];

        Fields::register_preferences_fields();

        $this->assertArrayHasKey(Fields::OPTION_PREPEND_EXCERPT, $wp_registered_settings);
        $this->assertArrayHasKey(Fields::OPTION_PLAYER_UI, $wp_registered_settings);

        $section = $wp_settings_fields[Tabs::PAGE_PREFERENCES][Tabs::SECTION_PREFERENCES];
        $this->assertArrayHasKey('beyondwords-include-excerpt', $section);
        $this->assertArrayHasKey('beyondwords-player-ui', $section);
    }

    /**
     * @test
     */
    public function sanitize_api_key_accepts_and_trims_value()
    {
        $this->assertSame('abc123', Fields::sanitize_api_key('abc123'));
    }

    /**
     * @test
     */
    public function sanitize_api_key_queues_error_when_empty()
    {
        Fields::sanitize_api_key('');

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('Settings/ApiKey', $errors);
    }

    /**
     * @test
     */
    public function sanitize_project_id_accepts_and_trims_value()
    {
        $this->assertSame('53391', Fields::sanitize_project_id('53391'));
    }

    /**
     * @test
     */
    public function sanitize_project_id_queues_error_when_empty()
    {
        Fields::sanitize_project_id('');

        $errors = wp_cache_get('beyondwords_settings_errors', 'beyondwords');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('Settings/ProjectId', $errors);
    }

    /**
     * @test
     */
    public function sanitize_integration_method_accepts_known_values()
    {
        $this->assertSame(Fields::INTEGRATION_REST_API, Fields::sanitize_integration_method(Fields::INTEGRATION_REST_API));
        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::sanitize_integration_method(Fields::INTEGRATION_CLIENT_SIDE));
    }

    /**
     * @test
     */
    public function sanitize_integration_method_falls_back_for_unknown_or_non_string()
    {
        $this->assertSame(Fields::INTEGRATION_REST_API, Fields::sanitize_integration_method('garbage'));
        $this->assertSame(Fields::INTEGRATION_REST_API, Fields::sanitize_integration_method(null));
        $this->assertSame(Fields::INTEGRATION_REST_API, Fields::sanitize_integration_method(42));
    }

    /**
     * @test
     */
    public function sanitize_player_ui_accepts_known_values()
    {
        $this->assertSame(Fields::PLAYER_UI_ENABLED, Fields::sanitize_player_ui(Fields::PLAYER_UI_ENABLED));
        $this->assertSame(Fields::PLAYER_UI_HEADLESS, Fields::sanitize_player_ui(Fields::PLAYER_UI_HEADLESS));
        $this->assertSame(Fields::PLAYER_UI_DISABLED, Fields::sanitize_player_ui(Fields::PLAYER_UI_DISABLED));
    }

    /**
     * @test
     */
    public function sanitize_player_ui_falls_back_for_unknown_or_non_string()
    {
        $this->assertSame(Fields::PLAYER_UI_ENABLED, Fields::sanitize_player_ui('garbage'));
        $this->assertSame(Fields::PLAYER_UI_ENABLED, Fields::sanitize_player_ui([]));
    }

    /**
     * @test
     */
    public function render_api_key_outputs_text_input_with_stored_value()
    {
        update_option(Fields::OPTION_API_KEY, 'test-key-123');

        $html = $this->capture_output(function () {
            Fields::render_api_key();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input[type="text"][name="' . Fields::OPTION_API_KEY . '"]');

        $this->assertCount(1, $input);
        $this->assertSame('test-key-123', $input->attr('value'));
    }

    /**
     * @test
     */
    public function render_project_id_outputs_text_input_with_stored_value()
    {
        update_option(Fields::OPTION_PROJECT_ID, '53391');

        $html = $this->capture_output(function () {
            Fields::render_project_id();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input[type="text"][name="' . Fields::OPTION_PROJECT_ID . '"]');

        $this->assertCount(1, $input);
        $this->assertSame('53391', $input->attr('value'));
    }

    /**
     * @test
     */
    public function render_integration_method_outputs_select_with_both_options()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $html = $this->capture_output(function () {
            Fields::render_integration_method();
        });

        $crawler = new Crawler($html);
        $select = $crawler->filter('select[name="' . Fields::OPTION_INTEGRATION_METHOD . '"]');

        $this->assertCount(1, $select);
        $this->assertCount(2, $select->filter('option'));

        $selected = $select->filter('option[selected]');
        $this->assertCount(1, $selected);
        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, $selected->attr('value'));
    }

    /**
     * @test
     */
    public function render_prepend_excerpt_outputs_checkbox_reflecting_stored_value()
    {
        update_option(Fields::OPTION_PREPEND_EXCERPT, true);

        $html = $this->capture_output(function () {
            Fields::render_prepend_excerpt();
        });

        $crawler = new Crawler($html);
        $checkbox = $crawler->filter('input[type="checkbox"][name="' . Fields::OPTION_PREPEND_EXCERPT . '"]');

        $this->assertCount(1, $checkbox);
        $this->assertSame('checked', $checkbox->attr('checked'));
    }

    /**
     * @test
     */
    public function render_prepend_excerpt_is_unchecked_when_false()
    {
        update_option(Fields::OPTION_PREPEND_EXCERPT, false);

        $html = $this->capture_output(function () {
            Fields::render_prepend_excerpt();
        });

        $crawler = new Crawler($html);
        $checkbox = $crawler->filter('input[type="checkbox"][name="' . Fields::OPTION_PREPEND_EXCERPT . '"]');

        $this->assertCount(1, $checkbox);
        $this->assertNull($checkbox->attr('checked'));
    }

    /**
     * @test
     */
    public function render_player_ui_outputs_select_with_three_options()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_HEADLESS);

        $html = $this->capture_output(function () {
            Fields::render_player_ui();
        });

        $crawler = new Crawler($html);
        $select = $crawler->filter('select[name="' . Fields::OPTION_PLAYER_UI . '"]');

        $this->assertCount(1, $select);
        $this->assertCount(3, $select->filter('option'));

        $selected = $select->filter('option[selected]');
        $this->assertCount(1, $selected);
        $this->assertSame(Fields::PLAYER_UI_HEADLESS, $selected->attr('value'));
    }

    /**
     * @test
     */
    public function get_integration_method_returns_site_default()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::get_integration_method());
    }

    /**
     * @test
     */
    public function get_integration_method_prefers_post_meta_when_present()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_REST_API);

        $postId = self::factory()->post->create();
        update_post_meta($postId, Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $post = get_post($postId);

        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::get_integration_method($post));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_integration_method_falls_back_to_option_when_post_meta_empty()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $postId = self::factory()->post->create();
        $post = get_post($postId);

        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::get_integration_method($post));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_integration_method_ignores_invalid_post_arg()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::get_integration_method(false));
        $this->assertSame(Fields::INTEGRATION_CLIENT_SIDE, Fields::get_integration_method('not-a-post'));
    }

    /**
     * @test
     */
    public function get_integration_method_options_returns_expected_shape()
    {
        $options = Fields::get_integration_method_options();

        $this->assertArrayHasKey(Fields::INTEGRATION_REST_API, $options);
        $this->assertArrayHasKey(Fields::INTEGRATION_CLIENT_SIDE, $options);
        $this->assertCount(2, $options);
    }

    /**
     * @test
     */
    public function get_player_ui_options_returns_expected_shape()
    {
        $options = Fields::get_player_ui_options();

        $this->assertArrayHasKey(Fields::PLAYER_UI_ENABLED, $options);
        $this->assertArrayHasKey(Fields::PLAYER_UI_HEADLESS, $options);
        $this->assertArrayHasKey(Fields::PLAYER_UI_DISABLED, $options);
        $this->assertCount(3, $options);
    }
}
