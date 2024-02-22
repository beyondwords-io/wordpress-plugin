<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

class SettingsUtilsTest extends WP_UnitTestCase
{
    /**
     * Sample data from the custom field `speechkit_info`.
     *
     * This was exported from a test site running plugin v2.7.10.
     *
     * @var string
     */
    private $sampleSpeechkitInfo = 'a:16:{s:2:"id";s:2:"49";s:10:"podcast_id";i:9969567;s:3:"url";s:53:"https://speechkit.pressingspace.com/post-from-2-7-10/";s:5:"title";s:16:"Post from 2.7.10";s:6:"author";s:13:"pressingspace";s:7:"summary";s:0:"";s:5:"image";s:1:"f";s:12:"published_at";s:24:"2021-11-17T17:44:58.000Z";s:5:"state";s:9:"processed";s:9:"share_url";s:25:"https://spkt.io/a/9969567";s:13:"share_version";s:2:"v2";s:5:"media";a:2:{i:0;a:10:{s:2:"id";i:11542939;s:4:"role";s:4:"body";s:12:"content_type";s:21:"application/x-mpegURL";s:3:"url";s:118:"https://abcdefghabcdef.cloudfront.net/audio/projects/9969/contents/9969567/media/abcdefghabcdefghabcdefghabcdefgh.m3u8";s:12:"download_url";N;s:10:"created_at";s:24:"2021-11-17T17:45:03.211Z";s:10:"updated_at";s:24:"2021-11-17T17:45:03.211Z";s:5:"state";s:9:"processed";s:8:"duration";i:4;s:5:"voice";N;}i:1;a:10:{s:2:"id";i:11542938;s:4:"role";s:4:"body";s:12:"content_type";s:10:"audio/mpeg";s:3:"url";s:126:"https://abcdefghabcdef.cloudfront.net/audio/projects/9969/contents/9969567/media/abcdefghabcdefghabcdefghabcdefgh_compiled.mp3";s:12:"download_url";N;s:10:"created_at";s:24:"2021-11-17T17:45:02.078Z";s:10:"updated_at";s:24:"2021-11-17T17:45:02.078Z";s:5:"state";s:9:"processed";s:8:"duration";i:4;s:5:"voice";N;}}s:11:"player_type";s:14:"EmbeddedPlayer";s:24:"next_content_external_id";N;s:11:"ad_disabled";b:0;s:10:"project_id";i:9969;}';

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
     * @test
     */
    public function getCompatiblePostTypesFilter()
    {
        $postTypes = array_values(get_post_types());

        $this->assertContains('post', $postTypes);
        $this->assertContains('page', $postTypes);
        $this->assertContains('attachment', $postTypes);
        $this->assertContains('revision', $postTypes);

        // Set the filter to only return array key 1 ("page")
        $filter = function($supportedPostTypes) {
            return [$supportedPostTypes[1], 'attachment'];
        };

        add_filter('beyondwords_settings_post_types', $filter);

        $postTypes = SettingsUtils::getCompatiblePostTypes();

        remove_filter('beyondwords_settings_post_types', $filter);

        $this->assertSame(['page'], $postTypes);
    }
}
