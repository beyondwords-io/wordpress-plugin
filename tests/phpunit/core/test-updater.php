<?php

declare(strict_types=1);

use BeyondWords\Core\Updater;

class UpdaterTest extends TestCase
{
    /**
     * @var \BeyondWords\Core\Updater
     */
    private $_instance;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function migrate_settings()
    {
        delete_option('speechkit_settings');
        delete_option('speechkit_api_key');
        delete_option('speechkit_project_id');
        delete_option('speechkit_preselect');
        delete_option('speechkit_merge_excerpt');

        Updater::migrate_settings();

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

        Updater::migrate_settings();

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
    public function rename_plugin_settings()
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

        Updater::rename_plugin_settings();

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

    /**
     * @test
     */
    public function migrate_preselect_format_converts_legacy_shapes()
    {
        update_option('beyondwords_preselect', [
            'post'  => '1',                          // legacy whole post type
            'page'  => ['category' => ['1', '2']],   // legacy term-gated
            'cpt'   => ['mode' => 'all'],            // already new format
            'empty' => [],                           // dropped
        ]);

        Updater::migrate_preselect_format();

        $this->assertSame([
            'post' => ['mode' => 'all'],
            'page' => ['mode' => 'terms', 'terms' => ['category' => [1, 2]]],
            'cpt'  => ['mode' => 'all'],
        ], get_option('beyondwords_preselect'));

        delete_option('beyondwords_preselect');
    }

    /**
     * @test
     */
    public function migrate_preselect_format_is_idempotent()
    {
        $value = [
            'post' => ['mode' => 'all'],
            'page' => ['mode' => 'terms', 'terms' => ['category' => [3]]],
        ];

        update_option('beyondwords_preselect', $value);

        Updater::migrate_preselect_format();

        $this->assertSame($value, get_option('beyondwords_preselect'));

        delete_option('beyondwords_preselect');
    }

    /**
     * @test
     */
    public function migrate_preselect_format_is_a_noop_when_option_is_not_an_array()
    {
        update_option('beyondwords_preselect', 'corrupt');

        Updater::migrate_preselect_format();

        $this->assertSame('corrupt', get_option('beyondwords_preselect'));

        delete_option('beyondwords_preselect');
    }

    /**
     * @test
     */
    public function migrate_disabled_to_embed_none()
    {
        // A post that opted out of the player via the legacy flag.
        $disabledPost = self::factory()->post->create();
        update_post_meta($disabledPost, 'beyondwords_disabled', '1');

        // A post that already has an Embed choice — must not be overwritten.
        $embedPost = self::factory()->post->create();
        update_post_meta($embedPost, 'beyondwords_disabled', '1');
        update_post_meta($embedPost, 'beyondwords_embed', 'audio_post');

        // A post with no legacy flag — must be left untouched.
        $untouchedPost = self::factory()->post->create();

        Updater::migrate_disabled_to_embed_none();

        $this->assertSame('none', get_post_meta($disabledPost, 'beyondwords_embed', true));
        $this->assertSame('', get_post_meta($disabledPost, 'beyondwords_disabled', true));

        $this->assertSame('audio_post', get_post_meta($embedPost, 'beyondwords_embed', true));
        $this->assertSame('', get_post_meta($embedPost, 'beyondwords_disabled', true));

        $this->assertSame('', get_post_meta($untouchedPost, 'beyondwords_embed', true));

        wp_delete_post($disabledPost, true);
        wp_delete_post($embedPost, true);
        wp_delete_post($untouchedPost, true);
    }

    /**
     * The gate must close once the recorded version matches this build, else the pre-release `< 7.0.0` comparison re-fires v7 migrations every request.
     *
     * @test
     */
    public function run_is_a_noop_once_the_recorded_version_matches_this_build()
    {
        update_option('beyondwords_version', BEYONDWORDS__PLUGIN_VERSION);

        // Sentinel: if run() re-executed the v7 block it would strip this flag and set Embed = None.
        $post = self::factory()->post->create();
        update_post_meta($post, 'beyondwords_disabled', '1');

        Updater::run();

        $this->assertSame('1', get_post_meta($post, 'beyondwords_disabled', true));
        $this->assertSame('', get_post_meta($post, 'beyondwords_embed', true));

        wp_delete_post($post, true);
        delete_option('beyondwords_version');
    }

    /**
     * When the recorded version predates a migration, run() must execute it and record this build's version.
     *
     * @test
     */
    public function run_migrates_and_records_the_version_when_outdated()
    {
        update_option('beyondwords_version', '6.0.0');

        $post = self::factory()->post->create();
        update_post_meta($post, 'beyondwords_disabled', '1');

        Updater::run();

        $this->assertSame('none', get_post_meta($post, 'beyondwords_embed', true));
        $this->assertSame('', get_post_meta($post, 'beyondwords_disabled', true));

        // Version normalised to this build, so the next run() is a no-op.
        $this->assertSame(BEYONDWORDS__PLUGIN_VERSION, get_option('beyondwords_version'));

        wp_delete_post($post, true);
        delete_option('beyondwords_version');
        delete_option('beyondwords_date_activated');
    }
}
