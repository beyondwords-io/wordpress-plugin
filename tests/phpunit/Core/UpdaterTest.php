<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Updater;

class UpdaterTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Core\Updater
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

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function migrateSettings()
    {
        delete_option('speechkit_settings');
        delete_option('speechkit_api_key');
        delete_option('speechkit_project_id');
        delete_option('speechkit_preselect');
        delete_option('speechkit_merge_excerpt');

        Updater::migrateSettings();

        $this->assertFalse(get_option('speechkit_api_key'));
        $this->assertFalse(get_option('speechkit_project_id'));
        $this->assertFalse(get_option('speechkit_preselect'));
        $this->assertFalse(get_option('speechkit_prepend_excerpt'));

        $apiKey = BEYONDWORDS_TESTS_API_KEY;
        $projectId = BEYONDWORDS_TESTS_PROJECT_ID;
        $postTypes = array('post', 'page', 'my_custom_post_type');
        $categories = array('-1', '1', '2', '42');
        $prependExcerpt = '1';

        $oldSettings = [
            // Basic settings
            'speechkit_enable'                 => '1',
            'speechkit_api_key'                => $apiKey,
            'speechkit_id'                     => $projectId,
            'speechkit_select_post_types'      => $postTypes,
            'speechkit_selected_categories'    => $categories,
            'speechkit_enable_telemetry'       => '1',
            'speechkit_rollbar_access_token'   => 'abcdefghijklmnopqrstuvwxyz',
            'speechkit_rollbar_error_notice'   => 'My error message',
            // Advanced Settings
            'speechkit_merge_excerpt'          => $prependExcerpt,
            'speechkit_enable_marfeel_comp'    => '1',
            'speechkit_wordpress_cron'         => '1',
        ];

        update_option('speechkit_settings', $oldSettings);

        Updater::migrateSettings();

        $this->assertSame($apiKey, get_option('speechkit_api_key'));
        $this->assertSame($projectId, get_option('speechkit_project_id'));
        $this->assertSame($prependExcerpt, get_option('speechkit_prepend_excerpt'));

        // Convert 'speechkit_select_post_types' and 'speechkit_selected_categories' into 'speechkit_preselect' format
        $expectedPreselect = [
            'post' => [
                'category' => $categories,
            ],
            'page' => '1',
            'my_custom_post_type' => '1',
        ];

        $this->assertSame($expectedPreselect, get_option('speechkit_preselect'));

        delete_option('speechkit_settings');
    }

    /**
     * @test
     */
    public function renamePluginSettings()
    {
        $this->assertFalse(get_option('beyondwords_api_key'));
        $this->assertFalse(get_option('beyondwords_project_id'));
        $this->assertFalse(get_option('beyondwords_preselect'));
        $this->assertFalse(get_option('beyondwords_prepend_excerpt'));

        $apiKey = BEYONDWORDS_TESTS_API_KEY;
        $projectId = BEYONDWORDS_TESTS_PROJECT_ID;
        $prependExcerpt = '1';
        $preselect = [
            'post' => [
                'category' => ['1', '42'],
            ],
            'page' => '1',
            'my_custom_post_type' => '1',
        ];

        update_option('speechkit_api_key',         $apiKey);
        update_option('speechkit_project_id',      $projectId);
        update_option('speechkit_prepend_excerpt', $prependExcerpt);
        update_option('speechkit_preselect',       $preselect);

        Updater::renamePluginSettings();

        delete_option('speechkit_api_key');
        delete_option('speechkit_project_id');
        delete_option('speechkit_prepend_excerpt');
        delete_option('speechkit_preselect');

        $this->assertSame($apiKey,         get_option('beyondwords_api_key'));
        $this->assertSame($projectId,      get_option('beyondwords_project_id'));
        $this->assertSame($prependExcerpt, get_option('beyondwords_prepend_excerpt'));
        $this->assertSame($preselect,      get_option('beyondwords_preselect'));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_prepend_excerpt');
        delete_option('beyondwords_preselect');
    }
}
