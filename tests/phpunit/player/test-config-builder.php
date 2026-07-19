<?php

use BeyondWords\Settings\Fields;
use BeyondWords\Editor\Components\PlayerStyle;
use BeyondWords\Player\ConfigBuilder;

/**
 * Class ConfigBuilderTest
 */
class ConfigBuilderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function build()
    {
        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = ConfigBuilder::build($post);

        $this->assertEquals($params->projectId, BEYONDWORDS_TESTS_PROJECT_ID);
        $this->assertEquals($params->contentId, BEYONDWORDS_TESTS_CONTENT_ID);
    }

    /**
     * @test
     */
    public function build_with_player_sdk_params_filter()
    {
        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($params) {
            $params['projectId']     = 4321;
            $params['contentId']     = 87654321;
            $params['playerStyle']   = 'my custom player style';
            $params['playerContent'] = 'custom content value';
            $params['myCustomParam'] = 'my custom param';

            return $params;
        };

        add_filter('beyondwords_player_sdk_params', $filter, 10);

        $params = ConfigBuilder::build($post);

        remove_filter('beyondwords_player_sdk_params', $filter, 10);

        $this->assertEquals($params->projectId, 4321);
        $this->assertEquals($params->contentId, 87654321);
        $this->assertEquals($params->playerStyle, 'my custom player style');
        $this->assertEquals($params->playerContent, 'custom content value');
        $this->assertEquals($params->myCustomParam, 'my custom param');

        wp_delete_post($post->ID, true);
    }


    /**
     * @test
     */
    public function merge_post_settings_includes_headless_setting()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_HEADLESS);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertEquals($params['showUserInterface'], false);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_PLAYER_UI);
    }

    /**
     * @test
     */
    public function merge_post_settings_rest_api_setting()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_REST_API);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('clientSideEnabled', $params);
        $this->assertArrayNotHasKey('sourceId', $params);

        $this->assertEquals($params['contentId'], BEYONDWORDS_TESTS_CONTENT_ID);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_INTEGRATION_METHOD);
    }

    /**
     * @test
     */
    public function merge_post_settings_rest_api_custom_field_overrides_setting()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_integration_method' => Fields::INTEGRATION_REST_API,
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('clientSideEnabled', $params);
        $this->assertArrayNotHasKey('sourceId', $params);

        $this->assertEquals($params['contentId'], BEYONDWORDS_TESTS_CONTENT_ID);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_INTEGRATION_METHOD);
    }

    /**
     * @test
     */
    public function merge_post_settings_client_side_setting_uses_rest_api_for_legacy_posts()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('clientSideEnabled', $params);
        $this->assertArrayNotHasKey('sourceId', $params);

        $this->assertEquals($params['contentId'], BEYONDWORDS_TESTS_CONTENT_ID);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_INTEGRATION_METHOD);
    }

    /**
     * @test
     */
    public function merge_post_settings_client_side_custom_field_overrides_setting()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_REST_API);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_integration_method' => Fields::INTEGRATION_CLIENT_SIDE,
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('showUserInterface', $params);
        $this->assertArrayNotHasKey('contentId', $params);

        // playerStyle is only added when set as post meta — global default no longer applies.
        $this->assertArrayNotHasKey('playerStyle', $params);
        $this->assertEquals($params['clientSideEnabled'], true);
        $this->assertEquals($params['sourceId'], (string) $post->ID);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_INTEGRATION_METHOD);
    }

    /**
     * Create a post with the given Settings Fields meta.
     *
     * @param array $meta Extra post meta to merge in.
     */
    private function createEmbedPost(array $meta)
    {
        return self::factory()->post->create_and_get([
            'meta_input' => array_merge([
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ], $meta),
        ]);
    }

    /**
     * @test
     */
    public function merge_post_settings_embed_audio_post_adds_no_asset_params()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'audio',
            'beyondwords_embed'  => 'audio_post',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('video', $params);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function merge_post_settings_embed_audio_script_sets_summary()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'script',
            'beyondwords_output' => 'audio',
            'beyondwords_embed'  => 'audio_script',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertTrue($params['summary']);
        $this->assertArrayNotHasKey('video', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function merge_post_settings_embed_video_post_sets_video()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'video',
            'beyondwords_embed'  => 'video_post',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertTrue($params['video']);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function merge_post_settings_embed_video_script_sets_video_and_summary()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'script',
            'beyondwords_output' => 'video',
            'beyondwords_embed'  => 'video_script',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertTrue($params['video']);
        $this->assertTrue($params['summary']);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function merge_post_settings_embed_none_adds_no_asset_params()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'audio',
            'beyondwords_embed'  => 'none',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('video', $params);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * With no Embed chosen, Source=Post × Output=Audio defaults to "audio_post" (no asset params).
     *
     * @test
     */
    public function merge_post_settings_embed_empty_defaults_to_audio_post()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'audio',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('video', $params);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * With no Embed chosen, Output=Video defaults to "video_post", setting video:true.
     *
     * @test
     */
    public function merge_post_settings_embed_empty_defaults_to_first_asset()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'video',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertTrue($params['video']);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * A stored Embed that no longer fits the current Source × Output falls back to None.
     *
     * @test
     */
    public function merge_post_settings_embed_invalid_for_source_output_falls_back_to_none()
    {
        $post = $this->createEmbedPost([
            'beyondwords_source' => 'post',
            'beyondwords_output' => 'audio',
            'beyondwords_embed'  => 'video_script',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertArrayNotHasKey('video', $params);
        $this->assertArrayNotHasKey('summary', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * Embed asset params compose with headless mode.
     *
     * @test
     */
    public function merge_post_settings_embed_composes_with_headless()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_HEADLESS);

        $post = $this->createEmbedPost([
            'beyondwords_source' => 'script',
            'beyondwords_output' => 'video',
            'beyondwords_embed'  => 'video_script',
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertFalse($params['showUserInterface']);
        $this->assertTrue($params['video']);
        $this->assertTrue($params['summary']);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_PLAYER_UI);
    }

    /**
     * Embed asset params compose with Magic Embed — no content ID, so the SDK fetches by source ID.
     *
     * @test
     */
    public function merge_post_settings_embed_composes_with_client_side()
    {
        update_option(Fields::OPTION_INTEGRATION_METHOD, Fields::INTEGRATION_CLIENT_SIDE);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_source'     => 'script',
                'beyondwords_output'     => 'audio',
                'beyondwords_embed'      => 'audio_script',
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertTrue($params['clientSideEnabled']);
        $this->assertEquals($params['sourceId'], (string) $post->ID);
        $this->assertArrayNotHasKey('contentId', $params);
        $this->assertTrue($params['summary']);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_INTEGRATION_METHOD);
    }
}
