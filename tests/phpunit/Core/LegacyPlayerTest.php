<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\LegacyPlayer;
use \Symfony\Component\DomCrawler\Crawler;

class LegacyPlayerTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Player\LegacyPlayer
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new LegacyPlayer();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addShortcode()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'LegacyPlayerTest::addShortcode',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
            'post_content' => "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
        ]);

        setup_postdata($post);

        ob_start();
        \the_content();
        $content = trim(ob_get_clean());

        $this->assertSame("<p>Before</p>\n<div data-beyondwords-player=\"true\" contenteditable=\"false\"></div>\n<p>After</p>", $content);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function autoPrependPlayer()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::autoPrependPlayer',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        setup_postdata($post);

        $content = '<p>Test content.</p>';

        $output = $this->_instance->autoPrependPlayer($content);

        $this->assertSame($output, $this->_instance->playerHtml() . $content);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function jsPlayerHtml()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'PlayerTest::jsPlayerHtml',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $html = $this->_instance->jsPlayerHtml($postId, BEYONDWORDS_TESTS_PROJECT_ID, BEYONDWORDS_TESTS_CONTENT_ID);

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function playerHtmlFilter()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::playerHtmlFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($html, $postId, $projectId, $contentId) {
            return sprintf(
                '<div id="wrapper" data-post-id="%d" data-project-id="%d" data-podcast-id="%s">%s</div>',
                $postId,
                $projectId,
                $contentId,
                $html
            );
        };

        add_filter('beyondwords_player_html', $filter, 10, 4);

        $html = $this->_instance->playerHtml($post);

        remove_filter('beyondwords_player_html', $filter, 10, 4);

        $crawler = new Crawler($html);

        // <div id="wrapper">
        $wrapper = $crawler->filter('#wrapper');
        $this->assertCount(1, $wrapper);
        $this->assertSame("$post->ID", $wrapper->attr('data-post-id'));
        $this->assertSame(BEYONDWORDS_TESTS_PROJECT_ID, $wrapper->attr('data-project-id'));
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID, $wrapper->attr('data-podcast-id'));

        $this->assertCount(1, $wrapper->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function deprecatedJsPlayerHtmlFilter()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::deprecatedJsPlayerHtmlFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($html, $postId, $projectId, $contentId) {
            return sprintf(
                '<div id="wrapper" data-post-id="%d" data-project-id="%d" data-podcast-id="%s">%s</div>',
                $postId,
                $projectId,
                $contentId,
                $html
            );
        };

        add_filter('beyondwords_js_player_html', $filter, 10, 4);

        $html = $this->_instance->playerHtml($post);

        remove_filter('beyondwords_js_player_html', $filter, 10, 4);

        $crawler = new Crawler($html);

        // <div id="wrapper">
        $wrapper = $crawler->filter('#wrapper');
        $this->assertCount(1, $wrapper);
        $this->assertSame("$post->ID", $wrapper->attr('data-post-id'));
        $this->assertSame(BEYONDWORDS_TESTS_PROJECT_ID, $wrapper->attr('data-project-id'));
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID, $wrapper->attr('data-podcast-id'));

        $this->assertCount(1, $wrapper->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function ampPlayerHtml() {

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerTest::ampPlayerHtml',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $src = "https://audio.beyondwords.io/amp/" . BEYONDWORDS_TESTS_PROJECT_ID . "?podcast_id=" . BEYONDWORDS_TESTS_CONTENT_ID;

        $html = $this->_instance->ampPlayerHtml($postId, BEYONDWORDS_TESTS_PROJECT_ID, BEYONDWORDS_TESTS_CONTENT_ID);

        $crawler = new Crawler($html);

        // <amp-iframe>
        $iframe = $crawler->filter('amp-iframe');
        $this->assertCount(1, $iframe);
        $this->assertSame('0', $iframe->attr('frameborder'));
        $this->assertSame('43', $iframe->attr('height'));
        $this->assertSame('responsive', $iframe->attr('layout'));
        $this->assertSame('allow-scripts allow-same-origin allow-popups', $iframe->attr('sandbox'));
        $this->assertSame('no', $iframe->attr('scrolling'));
        $this->assertSame($src, $iframe->attr('src'));
        $this->assertSame('295', $iframe->attr('width'));

        // <amp-img>
        $img = $iframe->filter('amp-img');
        $this->assertCount(1, $img);
        $this->assertSame('150', $img->attr('height'));
        $this->assertSame('responsive', $img->attr('layout'));
        $this->assertSame('', $img->attr('placeholder'));
        $this->assertSame(Environment::getAmpImgUrl(), $img->attr('src'));
        $this->assertSame('643', $img->attr('width'));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function isPlayerEnabled()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::isPlayerEnabled',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertFalse($this->_instance->isPlayerEnabled());
        $this->assertFalse($this->_instance->isPlayerEnabled(0));
        $this->assertFalse($this->_instance->isPlayerEnabled(false));

        $this->assertTrue($this->_instance->isPlayerEnabled($post));
        $this->assertTrue($this->_instance->isPlayerEnabled($post->ID));

        update_post_meta($post->ID, 'beyondwords_disabled', 1);

        $this->assertFalse($this->_instance->isPlayerEnabled($post->ID));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @skip
     * @dataProvider scriptLoaderTagProvider
     */
    public function scriptLoaderTag($postArgs, $tag, $handle, $src, $expect)
    {
        global $post;

        set_current_screen('/wp-admin/front');

        $post = self::factory()->post->create_and_get($postArgs);

        setup_postdata($post);

        $output = $this->_instance->scriptLoaderTag($tag, $handle, $src);
        $output = trim($output);

        if ($expect === true) {
            $this->assertStringStartsWith('<script id="beyondwords-sdk" type="module">', $output);
            $this->assertStringContainsString('import BeyondWords from \'https://proxy.beyondwords.io/npm/@beyondwords/beyondwords-audio-player-v2@latest/dist/module/index.js\'', $output);
            $this->assertStringEndsWith('</script>', $output);
            $this->assertStringNotContainsString($tag, $output);
        } else {
            $this->assertEquals($expect, $output);
        }

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    public function scriptLoaderTagProvider()
    {
        $tag    = '<script src="https://example.com/index.js"></script>';
        $handle = 'beyondwords-sdk';
        $src    = 'https://proxy.beyondwords.io/npm/@beyondwords/beyondwords-audio-player-v2@latest/dist/module/index.js';

        $invalidTag    = '<p></p>';
        $invalidHandle = 'some-other-handle';

        return [
            'invalid handle' => [
                'postArgs' => [
                    'post_title' => 'PlayerTest::scriptLoaderTag::1',
                    'meta_input' => [
                        'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                        'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
                'tag'      => $tag,
                'handle'   => 'an-invalid-handle',
                'src'      => $src,
                'expect'   => $tag,
            ],
            'no post' => [
                'postArgs' => null,
                'tag'      => $tag,
                'handle'   => $handle,
                'src'      => $src,
                'expect'   => '',
            ],
            'No Content ID' => [
                'postArgs' => [
                    'post_title' => 'PlayerTest::scriptLoaderTag::2',
                    'meta_input' => [
                        'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                    ],
                ],
                'tag'    => $tag,
                'handle' => $handle,
                'src'    => $src,
                'expect' => '',
            ],
            'No Project ID' => [
                'postArgs' => [
                    'post_title' => 'PlayerTest::scriptLoaderTag::3',
                    'meta_input' => [
                        'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
                'tag'    => $tag,
                'handle' => $handle,
                'src'    => $src,
                'expect' => '',
            ],
            'Post with everything we need' => [
                'postArgs' => [
                    'post_title' => 'PlayerTest::scriptLoaderTag::4',
                    'meta_input' => [
                        'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                        'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
                'tag'      => $tag,
                'handle'   => $handle,
                'src'      => $src,
                'expect'   => true,
            ],
            'Page with everything we need' => [
                'postArgs' => [
                    'post_title' => 'PlayerTest::scriptLoaderTag::5',
                    'post_type' => 'page',
                    'meta_input' => [
                        'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                        'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
                'tag'    => $tag,
                'handle' => $handle,
                'src'    => $src,
                'expect' => true,
            ],
        ];
    }

    /**
     * @test
     */
    public function jsPlayerParams()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::jsPlayerParams',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = $this->_instance->jsPlayerParams($post);

        $this->assertEquals($params['projectId'], BEYONDWORDS_TESTS_PROJECT_ID);
        $this->assertEquals($params['podcastId'], BEYONDWORDS_TESTS_CONTENT_ID);

        $this->assertArrayNotHasKey('playerType', $params);
        $this->assertArrayNotHasKey('skBackend', $params);
        $this->assertArrayNotHasKey('processingStatus', $params);
        $this->assertArrayNotHasKey('apiWriteKey', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function jsPlayerParamsUsePlayerSizeFromPluginSettings()
    {
        $post = $this->factory->post->create_and_get([
            'post_title' => 'PlayerTest::jsPlayerParamsUsePlayerSizeFromPluginSettings',
            'post_name' => 'js-player-params-use-player-size-from-plugin-settings',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        set_current_screen('front');

        update_option('beyondwords_player_size', 'medium');

        $params = $this->_instance->jsPlayerParams($post);

        delete_option('beyondwords_player_size');

        $this->assertEquals(BEYONDWORDS_TESTS_PROJECT_ID, $params['projectId']);
        $this->assertEquals(BEYONDWORDS_TESTS_CONTENT_ID, $params['podcastId']);
        $this->assertEquals('manual', $params['playerType']);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function jsPlayerParamsForEditScreen()
    {
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );
        set_current_screen( 'edit-post' );

        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::jsPlayerParamsForEditScreen',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $params = $this->_instance->jsPlayerParams($post);

        $this->assertEquals($params['projectId'], BEYONDWORDS_TESTS_PROJECT_ID);
        $this->assertEquals($params['podcastId'], BEYONDWORDS_TESTS_CONTENT_ID);
        $this->assertEquals($params['processingStatus'], true);
        $this->assertEquals($params['apiWriteKey'], 'write_XXXXXXXXXXXXXXXX');

        wp_delete_post($post->ID, true);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function jsPlayerParamsFilter()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::deprecatedJsPlayerParamsFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($params) {
            $params['projectId'] = 4321;
            $params['contentId'] = 87654321;
            $params['myCustomParam'] = 'my custom value';

            return $params;
        };

        add_filter('beyondwords_js_player_params', $filter, 10);

        $params = $this->_instance->jsPlayerParams($post);

        remove_filter('beyondwords_js_player_params', $filter, 10);

        $this->assertEquals($params['projectId'], 4321);
        $this->assertEquals($params['contentId'], 87654321);
        $this->assertEquals($params['myCustomParam'], 'my custom value');

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function enqueueScripts()
    {
        global $wp_scripts;

        $wp_scripts->queue = [];

        set_current_screen('/wp-admin/options.php');
        $this->_instance->enqueueScripts();
        $this->assertNotContains('beyondwords-sdk', $wp_scripts->queue);

        set_current_screen('/wp-admin/edit.php');
        $this->_instance->enqueueScripts();
        $this->assertNotContains('beyondwords-sdk', $wp_scripts->queue);

        set_current_screen('/wp-admin/post.php');
        $this->_instance->enqueueScripts();
        $this->assertNotContains('beyondwords-sdk', $wp_scripts->queue);

        $wp_scripts->queue = [];
    }
}
