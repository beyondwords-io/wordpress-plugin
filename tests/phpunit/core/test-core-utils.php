<?php

declare(strict_types=1);

use BeyondWords\Core\CoreUtils;

class CoreUtilsTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * Does the current page (screen) use the Gutenberg editor?
     *
     * @test
     * @dataProvider is_gutenberg_page_provider
     *
     * @param boolean $expected Expected method return value
     * @param int     $screen   WordPress Screen
     */
    public function is_gutenberg_page($expected, $screen)
    {
        set_current_screen($screen);

        $this->assertEquals($expected, CoreUtils::is_gutenberg_page());
    }

    /**
     *
     */
    public function is_gutenberg_page_provider()
    {
        return [
            'options.php' => [false, 'options.php'],
            'edit.php'    => [false, 'edit.php'],
            'post.php'    => [true,  'post.php'],
        ];
    }

    /**
     * Get the BeyondWords post meta keys.
     *
     * @since 4.1.0
     *
     * @test
     */
    public function get_post_meta_keys()
    {
        $keys = [
            // Current
            'beyondwords_generate_audio',
            'beyondwords_integration_method',
            'beyondwords_project_id',
            'beyondwords_content_id',
            'beyondwords_preview_token',
            'beyondwords_player_content',
            'beyondwords_player_style',
            'beyondwords_language_code',
            'beyondwords_language_id',
            'beyondwords_title_voice_id',
            'beyondwords_body_voice_id',
            'beyondwords_summary_voice_id',
            'beyondwords_error_message',
            'beyondwords_disabled',
            'beyondwords_delete_content',
        ];

        $this->assertEquals($keys, CoreUtils::get_post_meta_keys());
        $this->assertEquals($keys, CoreUtils::get_post_meta_keys('current'));
    }

    /**
     * Get the BeyondWords post meta keys.
     *
     * @since 4.1.0
     *
     * @test
     */
    public function get_post_meta_keys_deprecated()
    {
        $keys = [
            // Deprecated
            'beyondwords_podcast_id',
            'beyondwords_hash',
            'publish_post_to_speechkit',
            'speechkit_hash',
            'speechkit_generate_audio',
            'speechkit_project_id',
            'speechkit_podcast_id',
            'speechkit_error_message',
            'speechkit_disabled',
            'speechkit_access_key',
            'speechkit_error',
            'speechkit_info',
            'speechkit_response',
            'speechkit_retries',
            'speechkit_status',
            'speechkit_updated_at',
            '_speechkit_link',
            '_speechkit_text',
        ];

        $this->assertEquals($keys, CoreUtils::get_post_meta_keys('deprecated'));
    }

    /**
     * Get the BeyondWords post meta keys.
     *
     * @since 4.1.0
     *
     * @test
     */
    public function get_post_meta_keys_all()
    {
        $keys = [
            // Current
            'beyondwords_generate_audio',
            'beyondwords_integration_method',
            'beyondwords_project_id',
            'beyondwords_content_id',
            'beyondwords_preview_token',
            'beyondwords_player_content',
            'beyondwords_player_style',
            'beyondwords_language_code',
            'beyondwords_language_id',
            'beyondwords_title_voice_id',
            'beyondwords_body_voice_id',
            'beyondwords_summary_voice_id',
            'beyondwords_error_message',
            'beyondwords_disabled',
            'beyondwords_delete_content',
            // Deprecated
            'beyondwords_podcast_id',
            'beyondwords_hash',
            'publish_post_to_speechkit',
            'speechkit_hash',
            'speechkit_generate_audio',
            'speechkit_project_id',
            'speechkit_podcast_id',
            'speechkit_error_message',
            'speechkit_disabled',
            'speechkit_access_key',
            'speechkit_error',
            'speechkit_info',
            'speechkit_response',
            'speechkit_retries',
            'speechkit_status',
            'speechkit_updated_at',
            '_speechkit_link',
            '_speechkit_text',
        ];

        $this->assertEquals($keys, CoreUtils::get_post_meta_keys('all'));
    }
}
