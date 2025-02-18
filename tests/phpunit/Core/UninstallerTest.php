<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Uninstaller;

class UninstallerTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     *
     * @dataProvider optionNamesProvider
     */
    public function cleanupPluginOptions($name)
    {
        update_option($name, 'foo');

        $count = Uninstaller::cleanupPluginOptions();

        $this->assertFalse(get_option($name));
    }

    /**
     * Option names provider.
     */
    public function optionNamesProvider()
    {
        return [
            // v5.3 player content (loadContentAs)
            'beyondwords_player_content' => ['beyondwords_player_content'],
            // v5.0 player settings
            'beyondwords_player_call_to_action'     => ['beyondwords_player_call_to_action'],
            'beyondwords_player_clickable_sections' => ['beyondwords_player_clickable_sections'],
            'beyondwords_player_highlight_sections' => ['beyondwords_player_highlight_sections'],
            'beyondwords_player_skip_button_style'  => ['beyondwords_player_skip_button_style'],
            'beyondwords_player_theme'              => ['beyondwords_player_theme'],
            'beyondwords_player_theme_dark'         => ['beyondwords_player_theme_dark'],
            'beyondwords_player_theme_light'        => ['beyondwords_player_theme_light'],
            'beyondwords_player_theme_video'        => ['beyondwords_player_theme_video'],
            'beyondwords_player_widget_position'    => ['beyondwords_player_widget_position'],
            'beyondwords_player_widget_style'       => ['beyondwords_player_widget_style'],
            // v5.0 project settings
            'beyondwords_project_body_voice_id'             => ['beyondwords_project_body_voice_id'],
            'beyondwords_project_body_voice_speaking_rate'  => ['beyondwords_project_body_voice_speaking_rate'],
            'beyondwords_project_language_code'             => ['beyondwords_project_language_code'],
            'beyondwords_project_language_id'               => ['beyondwords_project_language_id'],
            'beyondwords_project_title_enabled'             => ['beyondwords_project_title_enabled'],
            'beyondwords_project_title_voice_id'            => ['beyondwords_project_title_voice_id'],
            'beyondwords_project_title_voice_speaking_rate' => ['beyondwords_project_title_voice_speaking_rate'],
            // v5.0 video settings
            'beyondwords_video_enabled' => ['beyondwords_video_enabled'],
            // v4.x
            'beyondwords_languages'            => ['beyondwords_languages'],
            'beyondwords_player_ui'            => ['beyondwords_player_ui'],
            'beyondwords_player_style'         => ['beyondwords_player_style'],
            'beyondwords_settings_updated'     => ['beyondwords_settings_updated'],
            'beyondwords_valid_api_connection' => ['beyondwords_valid_api_connection'],
            // v3.7.0 beyondwords_*
            'beyondwords_version'         => ['beyondwords_version'],
            'beyondwords_api_key'         => ['beyondwords_api_key'],
            'beyondwords_project_id'      => ['beyondwords_project_id'],
            'beyondwords_preselect'       => ['beyondwords_preselect'],
            'beyondwords_prepend_excerpt' => ['beyondwords_prepend_excerpt'],
            // v3.0.0 speechkit_*
            'speechkit_version'         => ['speechkit_version'],
            'speechkit_api_key'         => ['speechkit_api_key'],
            'speechkit_project_id'      => ['speechkit_project_id'],
            'speechkit_preselect'       => ['speechkit_preselect'],
            'speechkit_prepend_excerpt' => ['speechkit_prepend_excerpt'],
            // deprecated < v3.0
            'speechkit_settings'             => ['speechkit_settings'],
            'speechkit_enable'               => ['speechkit_enable'],
            'speechkit_id'                   => ['speechkit_id'],
            'speechkit_select_post_types'    => ['speechkit_select_post_types'],
            'speechkit_selected_categories'  => ['speechkit_selected_categories'],
            'speechkit_enable_telemetry'     => ['speechkit_enable_telemetry'],
            'speechkit_rollbar_access_token' => ['speechkit_rollbar_access_token'],
            'speechkit_rollbar_error_notice' => ['speechkit_rollbar_error_notice'],
            'speechkit_merge_excerpt'        => ['speechkit_merge_excerpt'],
            'speechkit_enable_marfeel_comp'  => ['speechkit_enable_marfeel_comp'],
            'speechkit_wordpress_cron'       => ['speechkit_wordpress_cron'],
        ];
    }

    /**
     * @test
     */
    public function cleanupCustomFields()
    {
        $numPosts = 10;

        $customFields = [
            // v4.x New API
            'beyondwords_content_id'       => 'beyondwords_content_id',
            'beyondwords_language_id'      => 'beyondwords_language_id',
            'beyondwords_body_voice_id'    => 'beyondwords_body_voice_id',
            'beyondwords_title_voice_id'   => 'beyondwords_title_voice_id',
            'beyondwords_summary_voice_id' => 'beyondwords_summary_voice_id',
            'beyondwords_preview_token'    => 'beyondwords_preview_token',
            // v3.7.0 beyondwords_*
            'beyondwords_generate_audio' => 'beyondwords_generate_audio',
            'beyondwords_project_id'     => 'beyondwords_project_id',
            'beyondwords_podcast_id'     => 'beyondwords_podcast_id',
            'beyondwords_hash'           => 'beyondwords_hash',
            'beyondwords_error_message'  => 'beyondwords_error_message',
            'beyondwords_disabled'       => 'beyondwords_disabled',
            // v3.0.0 speechkit_*
            'speechkit_generate_audio' => 'speechkit_generate_audio',
            'speechkit_project_id'     => 'speechkit_project_id',
            'speechkit_podcast_id'     => 'speechkit_podcast_id',
            'speechkit_hash'           => 'speechkit_hash',
            'speechkit_access_key'     => 'speechkit_access_key',
            'speechkit_error_message'  => 'speechkit_error_message',
            'speechkit_disabled'       => 'speechkit_disabled',
            'speechkit_updated_at'     => 'speechkit_updated_at',
            // deprecated < v3.0
            'publish_post_to_speechkit' => 'publish_post_to_speechkit',
            'speechkit_error'           => 'speechkit_error',
            'speechkit_info'            => 'speechkit_info',
            'speechkit_response'        => 'speechkit_response',
            'speechkit_retries'         => 'speechkit_retries',
            'speechkit_status'          => 'speechkit_status',
            '_speechkit_link'           => '_speechkit_link',
            '_speechkit_text'           => '_speechkit_text',
        ];

        $postIds = self::factory()->post->create_many($numPosts, [
            'post_title' => 'UninstallerTest::cleanupCustomFields %d',
            'meta_input' => array_merge($customFields, [
                // These custom fields should remain
                'beyondwords_prefixed_field' => 'foo',
                'another_custom_field' => 'bar',
            ]),
        ]);

        $count = Uninstaller::cleanupCustomFields();

        $this->assertEquals($count, $numPosts * count($customFields));

        foreach ($postIds as $postId) {
            clean_post_cache($postId);

            foreach ($customFields as $name => $value) {
                $this->assertFalse(metadata_exists('post', $postId, $name));
            }

            // These custom fields should remain
            $this->assertEquals('foo', get_post_meta($postId, 'beyondwords_prefixed_field', true));
            $this->assertEquals('bar', get_post_meta($postId, 'another_custom_field', true));

            wp_delete_post($postId);
        }
    }
}
