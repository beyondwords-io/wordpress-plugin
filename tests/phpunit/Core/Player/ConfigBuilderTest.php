<?php

use BeyondWords\Settings\Fields;
use BeyondWords\Post\PlayerStyle;
use BeyondWords\Player\ConfigBuilder;

/**
 * Class ConfigBuilderTest
 *
 * Constructs the parameters object for the BeyondWords JS SDK.
 */
class ConfigBuilderTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_project_id');

        // Then...
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
    public function buildWithPlayerSdkParamsFilter()
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
    public function mergePostSettingsIncludesHeadlessSetting()
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
    public function mergePostSettingsIncludesPlayerStyleCustomField()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_HEADLESS);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_player_style' => "video",
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertEquals($params['playerStyle'], "video");

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_PLAYER_UI);
    }

    /**
     * @test
     */
    public function mergePostSettingsIncludesPlayerContentCustomField()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_HEADLESS);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_player_content' => 'summary',
            ],
        ]);

        $params = ConfigBuilder::merge_post_settings($post, []);

        $this->assertEquals($params['loadContentAs'], ['summary']);

        wp_delete_post($post->ID, true);

        delete_option(Fields::OPTION_PLAYER_UI);
    }

    /**
     * @test
     */
    public function mergePostSettingsRestApiSetting()
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
    public function mergePostSettingsRestApiCustomFieldOverridesSetting()
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
    public function mergePostSettingsClientSideSettingUsesRestApiForLegacyPosts()
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
    public function mergePostSettingsClientSideCustomFieldOverridesSetting()
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
}
