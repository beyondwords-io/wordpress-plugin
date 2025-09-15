<?php

use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Player\ConfigBuilder;

/**
 * Class ConfigBuilderTest
 *
 * Constructs the parameters object for the BeyondWords JS SDK.
 */
class ConfigBuilderTest extends WP_UnitTestCase
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
            'post_title' => 'ConfigBuilderTest::build',
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
            'post_title' => 'ConfigBuilderTest::buildWithPlayerSdkParamsFilter',
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
    public function mergePluginSettings()
    {
        update_option('beyondwords_player_style', 'A');
        update_option('beyondwords_player_call_to_action', 'B');
        update_option('beyondwords_player_highlight_sections', 'C');
        update_option('beyondwords_player_widget_style', 'D');
        update_option('beyondwords_player_widget_position', 'E');
        update_option('beyondwords_player_skip_button_style', 'F');
        update_option('beyondwords_player_clickable_sections', '1');

        $params = ConfigBuilder::mergePluginSettings(['foo' => 'bar']);

        $this->assertEquals($params['foo'], 'bar');
        $this->assertEquals($params['playerStyle'], 'A');
        $this->assertEquals($params['callToAction'], 'B');
        $this->assertEquals($params['highlightSections'], 'C');
        $this->assertEquals($params['widgetStyle'], 'D');
        $this->assertEquals($params['widgetPosition'], 'E');
        $this->assertEquals($params['skipButtonStyle'], 'F');
        $this->assertEquals($params['clickableSections'], 'body');

        delete_option('beyondwords_player_style');
        delete_option('beyondwords_player_call_to_action');
        delete_option('beyondwords_player_highlight_sections');
        delete_option('beyondwords_player_widget_style');
        delete_option('beyondwords_player_widget_position');
        delete_option('beyondwords_player_skip_button_style');
        delete_option('beyondwords_player_clickable_sections');
    }

    /**
     * @test
     */
    public function mergePostSettingsRestApiHeadless()
    {
        update_option(PlayerUI::OPTION_NAME, PlayerUI::HEADLESS);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'ConfigBuilderTest::mergePostSettingsRestApi',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_player_style' => PlayerStyle::VIDEO,
                'beyondwords_player_content' => 'summary',
            ],
        ]);

        $params = ConfigBuilder::mergePostSettings($post, ['foo' => 'bar']);

        $this->assertArrayNotHasKey('clientSideEnabled', $params);
        $this->assertArrayNotHasKey('sourceId', $params);

        $this->assertEquals($params['foo'], 'bar');
        $this->assertEquals($params['showUserInterface'], false);
        $this->assertEquals($params['playerStyle'], PlayerStyle::VIDEO);
        $this->assertEquals($params['loadContentAs'], array('summary'));

        delete_option(PlayerUI::OPTION_NAME);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function mergePostSettingsClientSidePluginSetting()
    {
        update_option(IntegrationMethod::OPTION_NAME, IntegrationMethod::CLIENT_SIDE);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'ConfigBuilderTest::mergePostSettingsClientSidePluginSetting',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = ConfigBuilder::mergePostSettings($post, ['foo' => 'bar']);

        $this->assertArrayNotHasKey('showUserInterface', $params);
        $this->assertArrayNotHasKey('loadContentAs', $params);
        $this->assertArrayNotHasKey('contentId', $params);

        $this->assertEquals($params['foo'], 'bar');
        $this->assertEquals($params['playerStyle'], PlayerStyle::STANDARD);
        $this->assertEquals($params['clientSideEnabled'], true);
        $this->assertEquals($params['sourceId'], (string)$post->ID);

        wp_delete_post($post->ID, true);

        delete_option(IntegrationMethod::OPTION_NAME);
    }

    /**
     * @test
     */
    public function mergePostSettingsClientSideCustomField()
    {
        $this->markTestIncomplete('Fails with: Undefined array key "clientSideEnabled".');

        update_option(IntegrationMethod::OPTION_NAME, IntegrationMethod::REST_API);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'ConfigBuilderTest::mergePostSettingsClientSideCustomField',
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_integration_method' => IntegrationMethod::CLIENT_SIDE,
            ],
        ]);

        $params = ConfigBuilder::mergePostSettings($post, ['foo' => 'bar']);

        $this->assertArrayNotHasKey('showUserInterface', $params);
        $this->assertArrayNotHasKey('loadContentAs', $params);
        $this->assertArrayNotHasKey('contentId', $params);

        $this->assertEquals($params['foo'], 'bar');
        $this->assertEquals($params['playerStyle'], PlayerStyle::STANDARD);
        $this->assertEquals($params['clientSideEnabled'], true);
        $this->assertEquals($params['sourceId'], (string)$post->ID);

        wp_delete_post($post->ID, true);

        delete_option(IntegrationMethod::OPTION_NAME);
    }
}
