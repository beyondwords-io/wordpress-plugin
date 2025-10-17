<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Sync;

class SyncTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Sync
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        wp_cache_delete('beyondwords_sync_to_wordpress', 'beyondwords');
        wp_cache_delete('beyondwords_sync_to_dashboard', 'beyondwords');

        // Then...
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
}
