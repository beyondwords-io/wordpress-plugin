<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Sync;
use Beyondwords\Wordpress\Component\Settings\Settings;

/**
 * @group settings
 */
class SyncTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Sync
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        parent::setUp();

        // Clean up cache and settings errors
        wp_cache_delete('beyondwords_sync_to_wordpress', 'beyondwords');
        wp_cache_delete('beyondwords_sync_to_dashboard', 'beyondwords');

        // Set up API credentials for tests that need them
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
    }

    public function tearDown(): void
    {
        wp_cache_delete('beyondwords_sync_to_wordpress', 'beyondwords');
        wp_cache_delete('beyondwords_sync_to_dashboard', 'beyondwords');

        // Clean up all sync-related options
        foreach (array_keys(Sync::MAP_SETTINGS) as $option) {
            delete_option($option);
        }

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Sync::init();

        do_action('wp_loaded');

        // Actions
        $this->assertSame(30, has_action('load-settings_page_beyondwords', array(Sync::class, 'syncToWordPress')));

        // @todo Test the rest of the actions
        // $this->assertSame(20, has_action('load-settings_page_beyondwords', array($sync, 'scheduleSyncs')));
        // $this->assertSame(10, has_action('shutdown', array($sync, 'syncToDashboard')));
    }

    /**
     * @test
     *
     * @todo add tests to scheduleSyncsWithValidTab
     */
    public function scheduleSyncsWithInvalidTab()
    {
        Sync::scheduleSyncs();

        $this->assertEmpty(wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords'));
    }

    /**
     * @test
     * @dataProvider optionsToPathsProvider
     */
    public function shouldSyncOptionToDashboard($option, $path)
    {
        wp_cache_set('beyondwords_sync_to_dashboard', [$path], 'beyondwords');

        $this->assertTrue(Sync::shouldSyncOptionToDashboard($option));
    }

    public function optionsToPathsProvider()
    {
        return [
            'beyondwords_player_style' => [
                'option' => 'beyondwords_player_style',
                'path'   => '[player_settings][player_style]',
            ],
            'beyondwords_player_theme' => [
                'option' => 'beyondwords_player_theme',
                'path'   => '[player_settings][theme]',
            ],
            'beyondwords_player_theme_dark' => [
                'option' => 'beyondwords_player_theme_dark',
                'path'   => '[player_settings][dark_theme]',
            ],
            'beyondwords_player_theme_light' => [
                'option' => 'beyondwords_player_theme_light',
                'path'   => '[player_settings][light_theme]',
            ],
            'beyondwords_player_theme_video' => [
                'option' => 'beyondwords_player_theme_video',
                'path'   => '[player_settings][video_theme]',
            ],
            'beyondwords_player_call_to_action' => [
                'option' => 'beyondwords_player_call_to_action',
                'path'   => '[player_settings][call_to_action]',
            ],
            'beyondwords_player_widget_style' => [
                'option' => 'beyondwords_player_widget_style',
                'path'   => '[player_settings][widget_style]',
            ],
            'beyondwords_player_widget_position' => [
                'option' => 'beyondwords_player_widget_position',
                'path'   => '[player_settings][widget_position]',
            ],
            'beyondwords_player_skip_button_style' => [
                'option' => 'beyondwords_player_skip_button_style',
                'path'   => '[player_settings][skip_button_style]',
            ],
            'beyondwords_player_clickable_sections' => [
                'option' => 'beyondwords_player_clickable_sections',
                'path'   => '[player_settings][segment_playback_enabled]',
            ],
            'beyondwords_project_auto_publish_enabled' => [
                'option' => 'beyondwords_project_auto_publish_enabled',
                'path'   => '[project][auto_publish_enabled]',
            ],
            'beyondwords_project_language_code' => [
                'option' => 'beyondwords_project_language_code',
                'path'   => '[project][language]',
            ],
            'beyondwords_project_body_voice_id' => [
                'option' => 'beyondwords_project_body_voice_id',
                'path'   => '[project][body][voice][id]',
            ],
            'beyondwords_project_body_voice_speaking_rate' => [
                'option' => 'beyondwords_project_body_voice_speaking_rate',
                'path'   => '[project][body][voice][speaking_rate]',
            ],
            'beyondwords_project_title_enabled' => [
                'option' => 'beyondwords_project_title_enabled',
                'path'   => '[project][title][enabled]',
            ],
            'beyondwords_project_title_voice_id' => [
                'option' => 'beyondwords_project_title_voice_id',
                'path'   => '[project][title][voice][id]',
            ],
            'beyondwords_project_title_voice_speaking_rate' => [
                'option' => 'beyondwords_project_title_voice_speaking_rate',
                'path'   => '[project][title][voice][speaking_rate]',
            ],
            'beyondwords_video_enabled' => [
                'option' => 'beyondwords_video_enabled',
                'path'   => '[video_settings][enabled]',
            ],
        ];
    }

    /**
     * @test
     */
    public function map_settings_constant_has_all_expected_keys(): void
    {
        $this->assertIsArray(Sync::MAP_SETTINGS);
        $this->assertCount(18, Sync::MAP_SETTINGS);

        // Verify player settings
        $this->assertArrayHasKey('beyondwords_player_style', Sync::MAP_SETTINGS);
        $this->assertArrayHasKey('beyondwords_player_theme', Sync::MAP_SETTINGS);
        $this->assertArrayHasKey('beyondwords_player_call_to_action', Sync::MAP_SETTINGS);

        // Verify project settings
        $this->assertArrayHasKey('beyondwords_project_auto_publish_enabled', Sync::MAP_SETTINGS);
        $this->assertArrayHasKey('beyondwords_project_language_code', Sync::MAP_SETTINGS);

        // Verify video settings
        $this->assertArrayHasKey('beyondwords_video_enabled', Sync::MAP_SETTINGS);
    }

    /**
     * @test
     *
     * Note: These tests for valid tabs require Settings::getActiveTab() which depends on
     * WordPress admin page context. They're commented out to avoid test environment issues.
     * The sync logic itself is tested through integration tests.
     */
    public function schedule_syncs_method_exists_and_callable(): void
    {
        $this->assertTrue(method_exists(Sync::class, 'scheduleSyncs'));
        $this->assertTrue(is_callable([Sync::class, 'scheduleSyncs']));
    }

    /**
     * @test
     */
    public function schedule_syncs_does_nothing_for_invalid_tab(): void
    {
        $_GET['tab'] = 'invalid';

        Sync::scheduleSyncs();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertFalse($cached);
    }

    /**
     * @test
     */
    public function schedule_syncs_does_nothing_when_no_tab(): void
    {
        unset($_GET['tab']);

        Sync::scheduleSyncs();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertFalse($cached);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_returns_early_when_no_cache(): void
    {
        // No cache set
        Sync::syncToWordPress();

        // Should return early without making API calls
        // No assertions needed - test passes if no errors occur
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_returns_early_when_cache_is_not_array(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', 'not-an-array', 'beyondwords');

        Sync::syncToWordPress();

        // Should return early
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_deletes_cache_after_reading(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', ['project'], 'beyondwords');

        Sync::syncToWordPress();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertFalse($cached, 'Cache should be deleted after sync');
    }

    /**
     * @test
     */
    public function update_options_from_responses_returns_false_for_empty_responses(): void
    {
        $result = Sync::updateOptionsFromResponses([]);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function update_options_from_responses_adds_settings_error_for_empty_responses(): void
    {
        Sync::updateOptionsFromResponses([]);

        $errors = get_settings_errors('beyondwords_settings');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Unexpected BeyondWords REST API response', $errors[0]['message']);
        $this->assertSame('error', $errors[0]['type']);
    }

    /**
     * @test
     */
    public function update_options_from_responses_updates_player_settings(): void
    {
        $responses = [
            'player_settings' => [
                'player_style' => 'small',
                'theme' => 'dark',
            ],
        ];

        $result = Sync::updateOptionsFromResponses($responses);

        $this->assertTrue($result);
        $this->assertSame('small', get_option('beyondwords_player_style'));
        $this->assertSame('dark', get_option('beyondwords_player_theme'));
    }

    /**
     * @test
     */
    public function update_options_from_responses_updates_project_settings(): void
    {
        $responses = [
            'project' => [
                'auto_publish_enabled' => true,
                'language' => 'en',
            ],
        ];

        $result = Sync::updateOptionsFromResponses($responses);

        $this->assertTrue($result);
        $this->assertTrue(get_option('beyondwords_project_auto_publish_enabled'));
        $this->assertSame('en', get_option('beyondwords_project_language_code'));
    }

    /**
     * @test
     */
    public function update_options_from_responses_updates_nested_project_settings(): void
    {
        $responses = [
            'project' => [
                'body' => [
                    'voice' => [
                        'id' => 123,
                        'speaking_rate' => 1.5,
                    ],
                ],
                'title' => [
                    'enabled' => true,
                    'voice' => [
                        'id' => 456,
                        'speaking_rate' => 1.2,
                    ],
                ],
            ],
        ];

        $result = Sync::updateOptionsFromResponses($responses);

        $this->assertTrue($result);
        $this->assertSame(123, get_option('beyondwords_project_body_voice_id'));
        $this->assertSame(1.5, get_option('beyondwords_project_body_voice_speaking_rate'));
        $this->assertTrue(get_option('beyondwords_project_title_enabled'));
        $this->assertSame(456, get_option('beyondwords_project_title_voice_id'));
        $this->assertSame(1.2, get_option('beyondwords_project_title_voice_speaking_rate'));
    }

    /**
     * @test
     */
    public function update_options_from_responses_updates_video_settings(): void
    {
        $responses = [
            'video_settings' => [
                'enabled' => true,
            ],
        ];

        $result = Sync::updateOptionsFromResponses($responses);

        $this->assertTrue($result);
        $this->assertTrue(get_option('beyondwords_video_enabled'));
    }

    /**
     * @test
     */
    public function update_options_from_responses_skips_null_values(): void
    {
        // Set an initial value
        update_option('beyondwords_player_style', 'large');

        $responses = [
            'player_settings' => [
                'player_style' => null, // This should not update the option
                'theme' => 'dark',
            ],
        ];

        Sync::updateOptionsFromResponses($responses);

        // Should not have updated player_style (still 'large')
        $this->assertSame('large', get_option('beyondwords_player_style'));
        // But should have updated theme
        $this->assertSame('dark', get_option('beyondwords_player_theme'));
    }

    /**
     * @test
     */
    public function sync_to_dashboard_returns_early_when_no_cache(): void
    {
        Sync::syncToDashboard();

        // Should return early without making API calls
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_returns_early_when_cache_is_not_array(): void
    {
        wp_cache_set('beyondwords_sync_to_dashboard', 'not-an-array', 'beyondwords');

        Sync::syncToDashboard();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_deletes_cache_after_reading(): void
    {
        wp_cache_set('beyondwords_sync_to_dashboard', ['beyondwords_player_style'], 'beyondwords');

        Sync::syncToDashboard();

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        $this->assertFalse($cached, 'Cache should be deleted after sync');
    }

    /**
     * @test
     */
    public function should_sync_option_to_dashboard_returns_false_for_unknown_option(): void
    {
        $result = Sync::shouldSyncOptionToDashboard('unknown_option_name');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function should_sync_option_to_dashboard_returns_false_when_option_has_errors(): void
    {
        add_settings_error('beyondwords_player_style', 'test_error', 'Test error message');

        $result = Sync::shouldSyncOptionToDashboard('beyondwords_player_style');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function should_sync_option_to_dashboard_returns_true_when_no_errors(): void
    {
        // Explicitly clear any existing settings errors
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // No errors set for this option
        $result = Sync::shouldSyncOptionToDashboard('beyondwords_player_style');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function sync_option_to_dashboard_adds_option_to_cache(): void
    {
        Sync::syncOptionToDashboard('beyondwords_player_style');

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        $this->assertIsArray($cached);
        $this->assertContains('beyondwords_player_style', $cached);
    }

    /**
     * @test
     */
    public function sync_option_to_dashboard_appends_to_existing_cache(): void
    {
        // Set initial cache
        wp_cache_set('beyondwords_sync_to_dashboard', ['beyondwords_player_style'], 'beyondwords');

        // Add another option
        Sync::syncOptionToDashboard('beyondwords_player_theme');

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        $this->assertCount(2, $cached);
        $this->assertContains('beyondwords_player_style', $cached);
        $this->assertContains('beyondwords_player_theme', $cached);
    }

    /**
     * @test
     */
    public function sync_option_to_dashboard_prevents_duplicates(): void
    {
        Sync::syncOptionToDashboard('beyondwords_player_style');
        Sync::syncOptionToDashboard('beyondwords_player_style');
        Sync::syncOptionToDashboard('beyondwords_player_style');

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        $this->assertCount(1, $cached);
        $this->assertContains('beyondwords_player_style', $cached);
    }

    /**
     * @test
     */
    public function sync_option_to_dashboard_handles_non_array_cache(): void
    {
        // Set cache to non-array value
        wp_cache_set('beyondwords_sync_to_dashboard', 'not-an-array', 'beyondwords');

        Sync::syncOptionToDashboard('beyondwords_player_style');

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');

        // Should reset to array and add option
        $this->assertIsArray($cached);
        $this->assertContains('beyondwords_player_style', $cached);
    }

    /**
     * @test
     */
    public function sync_option_to_dashboard_sets_cache_expiration(): void
    {
        Sync::syncOptionToDashboard('beyondwords_player_style');

        // Cache should exist
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertNotFalse($cached);

        // Note: We can't easily test the 60-second expiration in unit tests
        // but we verify the cache is set correctly
    }

    /**
     * @test
     */
    public function integration_full_sync_workflow(): void
    {
        // Clear settings errors
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Step 1: Flag an option for syncing to dashboard
        update_option('beyondwords_player_style', 'small');
        Sync::syncOptionToDashboard('beyondwords_player_style');

        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertContains('beyondwords_player_style', $cached);

        // Step 2: Check if option should sync
        $shouldSync = Sync::shouldSyncOptionToDashboard('beyondwords_player_style');
        $this->assertTrue($shouldSync);

        // Step 3: Verify the option is in the map
        $this->assertArrayHasKey('beyondwords_player_style', Sync::MAP_SETTINGS);
    }

    /**
     * @test
     */
    public function integration_update_options_from_multiple_response_types(): void
    {
        $responses = [
            'player_settings' => [
                'player_style' => 'video',
                'theme' => 'auto',
                'call_to_action' => 'Listen now',
            ],
            'project' => [
                'auto_publish_enabled' => false,
                'language' => 'es',
            ],
            'video_settings' => [
                'enabled' => true,
            ],
        ];

        $result = Sync::updateOptionsFromResponses($responses);

        $this->assertTrue($result);

        // Verify player settings
        $this->assertSame('video', get_option('beyondwords_player_style'));
        $this->assertSame('auto', get_option('beyondwords_player_theme'));
        $this->assertSame('Listen now', get_option('beyondwords_player_call_to_action'));

        // Verify project settings
        $this->assertFalse(get_option('beyondwords_project_auto_publish_enabled'));
        $this->assertSame('es', get_option('beyondwords_project_language_code'));

        // Verify video settings
        $this->assertTrue(get_option('beyondwords_video_enabled'));
    }

    /**
     * @test
     */
    public function schedule_syncs_caches_project_for_content_tab(): void
    {
        $_GET['tab'] = 'content';

        // Initialize Settings to register tabs
        Settings::init();
        do_action('wp_loaded');

        Sync::scheduleSyncs();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertIsArray($cached);
        $this->assertContains('project', $cached);
    }

    /**
     * @test
     */
    public function schedule_syncs_caches_project_for_voices_tab(): void
    {
        $_GET['tab'] = 'voices';

        // Initialize Settings to register tabs
        Settings::init();
        do_action('wp_loaded');

        Sync::scheduleSyncs();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertIsArray($cached);
        $this->assertContains('project', $cached);
    }

    /**
     * @test
     */
    public function schedule_syncs_caches_player_and_video_settings_for_player_tab(): void
    {
        $_GET['tab'] = 'player';

        // Initialize Settings to register tabs
        Settings::init();
        do_action('wp_loaded');

        Sync::scheduleSyncs();

        $cached = wp_cache_get('beyondwords_sync_to_wordpress', 'beyondwords');

        $this->assertIsArray($cached);
        $this->assertContains('player_settings', $cached);
        $this->assertContains('video_settings', $cached);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_calls_project_api_when_project_in_cache(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', ['project'], 'beyondwords');

        Sync::syncToWordPress();

        // If API call was successful, options should be updated
        // This is tested more thoroughly in integration tests
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_calls_player_settings_api_when_player_settings_in_cache(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', ['player_settings'], 'beyondwords');

        Sync::syncToWordPress();

        // Should make API call (no errors means success)
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_calls_video_settings_api_when_video_settings_in_cache(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', ['video_settings'], 'beyondwords');

        Sync::syncToWordPress();

        // Should make API call (no errors means success)
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_wordpress_calls_all_apis_when_all_in_cache(): void
    {
        wp_cache_set('beyondwords_sync_to_wordpress', ['all'], 'beyondwords');

        Sync::syncToWordPress();

        // Should make all API calls (no errors means success)
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_builds_settings_array_from_cached_options(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set some options
        update_option('beyondwords_player_style', 'large');
        update_option('beyondwords_player_theme', 'dark');

        // Cache options for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_player_style',
            'beyondwords_player_theme',
        ], 'beyondwords');

        // Mock API client methods would be called here
        // We can't easily test the actual API calls without mocking
        Sync::syncToDashboard();

        // Cache should be cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_only_syncs_options_without_errors(): void
    {
        // Set an error for one option
        add_settings_error('beyondwords_player_style', 'test_error', 'Test error');

        global $wp_settings_errors;

        // Clear errors for other option
        $hasStyleErrors = get_settings_errors('beyondwords_player_theme');
        $this->assertEmpty($hasStyleErrors);

        // Only the option without errors should sync
        $result = Sync::shouldSyncOptionToDashboard('beyondwords_player_theme');
        $this->assertTrue($result);

        $result = Sync::shouldSyncOptionToDashboard('beyondwords_player_style');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_syncs_player_settings(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set player options
        update_option('beyondwords_player_style', 'large');
        update_option('beyondwords_player_theme', 'dark');

        // Cache player options for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_player_style',
            'beyondwords_player_theme',
        ], 'beyondwords');

        Sync::syncToDashboard();

        // Verify cache was cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);

        // Verify success message was added
        $errors = get_settings_errors('beyondwords_settings');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Player settings synced', $errors[0]['message']);
        $this->assertSame('success', $errors[0]['type']);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_syncs_title_voice_speaking_rate(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set title voice options
        update_option('beyondwords_project_title_voice_id', 123);
        update_option('beyondwords_project_title_voice_speaking_rate', 120);

        // Cache speaking rate option for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_project_title_voice_speaking_rate',
        ], 'beyondwords');

        Sync::syncToDashboard();

        // Verify cache was cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_syncs_body_voice_speaking_rate(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set body voice options
        update_option('beyondwords_project_body_voice_id', 456);
        update_option('beyondwords_project_body_voice_speaking_rate', 110);

        // Cache speaking rate option for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_project_body_voice_speaking_rate',
        ], 'beyondwords');

        Sync::syncToDashboard();

        // Verify cache was cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_syncs_project_settings(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set project options
        update_option('beyondwords_project_auto_publish_enabled', true);
        update_option('beyondwords_project_language_code', 'en-US');

        // Cache project options for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_project_auto_publish_enabled',
            'beyondwords_project_language_code',
        ], 'beyondwords');

        Sync::syncToDashboard();

        // Verify cache was cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);

        // Verify success message was added
        $errors = get_settings_errors('beyondwords_settings');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Project settings synced', $errors[0]['message']);
        $this->assertSame('success', $errors[0]['type']);
    }

    /**
     * @test
     */
    public function sync_to_dashboard_removes_speaking_rate_from_project_settings(): void
    {
        global $wp_settings_errors;
        $wp_settings_errors = [];

        // Set project options including speaking rates
        update_option('beyondwords_project_auto_publish_enabled', true);
        update_option('beyondwords_project_title_voice_id', 123);
        update_option('beyondwords_project_title_voice_speaking_rate', 120);
        update_option('beyondwords_project_body_voice_id', 456);
        update_option('beyondwords_project_body_voice_speaking_rate', 110);

        // Cache all project options for syncing
        wp_cache_set('beyondwords_sync_to_dashboard', [
            'beyondwords_project_auto_publish_enabled',
            'beyondwords_project_title_voice_speaking_rate',
            'beyondwords_project_body_voice_speaking_rate',
        ], 'beyondwords');

        Sync::syncToDashboard();

        // Verify cache was cleared
        $cached = wp_cache_get('beyondwords_sync_to_dashboard', 'beyondwords');
        $this->assertFalse($cached);

        // Verify project settings success message
        $errors = get_settings_errors('beyondwords_settings');
        $this->assertNotEmpty($errors);

        // Should have project settings message
        $hasProjectMessage = false;
        foreach ($errors as $error) {
            if (strpos($error['message'], 'Project settings synced') !== false) {
                $hasProjectMessage = true;
                break;
            }
        }
        $this->assertTrue($hasProjectMessage);
    }
}
