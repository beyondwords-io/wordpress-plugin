<?php

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;

/**
 * Class ConfigBuilderTest
 *
 * Constructs the parameters object for the BeyondWords JS SDK.
 */
class ConfigBuilderTest
{
    /**
     * @test
     */
    public function build()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    /**
     * @test
     */
    public function mergePluginSettings()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    /**
     * @test
     */
    public function mergePostSettings()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    // /**
    //  * @test
    //  */
    // public function jsPlayerParams()
    // {
    //     $post = self::factory()->post->create_and_get([
    //         'post_title' => 'PlayerTest::jsPlayerParams',
    //         'meta_input' => [
    //             'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
    //             'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
    //         ],
    //     ]);

    //     $params = Player::jsPlayerParams($post);

    //     $this->assertEquals($params->projectId, BEYONDWORDS_TESTS_PROJECT_ID);
    //     $this->assertEquals($params->contentId, BEYONDWORDS_TESTS_CONTENT_ID);
    //     $this->assertEquals($params->playerStyle, 'standard');

    //     $this->assertObjectNotHasProperty('playerType', $params);
    //     $this->assertObjectNotHasProperty('skBackend', $params);
    //     $this->assertObjectNotHasProperty('processingStatus', $params);
    //     $this->assertObjectNotHasProperty('apiWriteKey', $params);

    //     wp_delete_post($post->ID, true);
    // }

    // /**
    //  * @test
    //  */
    // public function playerSdkParamsFilter()
    // {
    //     $post = self::factory()->post->create_and_get([
    //         'post_title' => 'PlayerTest::playerSdkParamsFilter',
    //         'meta_input' => [
    //             'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
    //             'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
    //         ],
    //     ]);

    //     $filter = function($params) {
    //         $params['projectId']     = 4321;
    //         $params['contentId']     = 87654321;
    //         $params['playerStyle']   = 'screen';
    //         $params['playerContent'] = 'custom content value';
    //         $params['myCustomParam'] = 'my custom value';

    //         return $params;
    //     };

    //     add_filter('beyondwords_player_sdk_params', $filter, 10);

    //     $params = Player::jsPlayerParams($post);

    //     remove_filter('beyondwords_player_sdk_params', $filter, 10);

    //     $this->assertEquals($params->projectId, 4321);
    //     $this->assertEquals($params->contentId, 87654321);
    //     $this->assertEquals($params->playerStyle, 'screen');
    //     $this->assertEquals($params->playerContent, 'custom content value');
    //     $this->assertEquals($params->myCustomParam, 'my custom value');

    //     wp_delete_post($post->ID, true);
    // }
}
