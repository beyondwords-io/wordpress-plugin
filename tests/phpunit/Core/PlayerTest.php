<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Player;
use \Symfony\Component\DomCrawler\Crawler;

class PlayerTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Core\Player\Player
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
    public function init()
    {
        Player::init();

        do_action('wp_loaded');

        // Actions
        $this->assertEquals(10, has_action('init', array(Player::class, 'registerShortcodes')));

        // Filters
        $this->assertEquals(1000000, has_filter('the_content', array(Player::class, 'autoPrependPlayer')));
        $this->assertEquals(10, has_filter('newsstand_the_content', array(Player::class, 'autoPrependPlayer')));
    }

    /**
     * @test
     */
    public function addShortcode()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::addShortcode',
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

        $output = Player::autoPrependPlayer($content);

        // autoPrependPlayer() should not affect $content unless is_singular()
        $this->assertSame($content, $output);

        $this->go_to("/?p={$post->ID}");

        $output = Player::autoPrependPlayer($content);

        // We are now is_singular() so player should be prepended
        $this->assertSame('<div data-beyondwords-player="true" contenteditable="false"></div>' . $content, $output);

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

        $html = Player::jsPlayerHtml($postId, BEYONDWORDS_TESTS_PROJECT_ID, BEYONDWORDS_TESTS_CONTENT_ID);

        $this->assertNotEmpty($html);

        setup_postdata($postId);

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_reset_postdata();

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

        $html = Player::playerHtml($post);

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
    public function ampPlayerHtml() {

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerTest::ampPlayerHtml',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $src = "https://audio.beyondwords.io/amp/" . BEYONDWORDS_TESTS_PROJECT_ID . "?podcast_id=" . BEYONDWORDS_TESTS_CONTENT_ID;

        $html = Player::ampPlayerHtml($postId, BEYONDWORDS_TESTS_PROJECT_ID, BEYONDWORDS_TESTS_CONTENT_ID);

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

        $this->assertFalse(Player::isPlayerEnabled());
        $this->assertFalse(Player::isPlayerEnabled(0));
        $this->assertFalse(Player::isPlayerEnabled(false));

        $this->assertTrue(Player::isPlayerEnabled($post));
        $this->assertTrue(Player::isPlayerEnabled($post->ID));

        update_post_meta($post->ID, 'beyondwords_disabled', 1);

        $this->assertFalse(Player::isPlayerEnabled($post->ID));

        wp_delete_post($post->ID, true);
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

        $params = Player::jsPlayerParams($post);

        $this->assertEquals($params->projectId, BEYONDWORDS_TESTS_PROJECT_ID);
        $this->assertEquals($params->contentId, BEYONDWORDS_TESTS_CONTENT_ID);
        $this->assertEquals($params->playerStyle, 'standard');

        $this->assertObjectNotHasProperty('playerType', $params);
        $this->assertObjectNotHasProperty('skBackend', $params);
        $this->assertObjectNotHasProperty('processingStatus', $params);
        $this->assertObjectNotHasProperty('apiWriteKey', $params);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function playerSdkParamsFilter()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::playerSdkParamsFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($params) {
            $params['projectId']     = 4321;
            $params['contentId']     = 87654321;
            $params['playerStyle']   = 'screen';
            $params['playerContent'] = 'custom content value';
            $params['myCustomParam'] = 'my custom value';

            return $params;
        };

        add_filter('beyondwords_player_sdk_params', $filter, 10);

        $params = Player::jsPlayerParams($post);

        remove_filter('beyondwords_player_sdk_params', $filter, 10);

        $this->assertEquals($params->projectId, 4321);
        $this->assertEquals($params->contentId, 87654321);
        $this->assertEquals($params->playerStyle, 'screen');
        $this->assertEquals($params->playerContent, 'custom content value');
        $this->assertEquals($params->myCustomParam, 'my custom value');

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function enqueueScripts()
    {
        global $wp_scripts;

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerTest::enqueueScripts',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertNull($wp_scripts);

        $this->go_to("/");
        Player::enqueueScripts( 'front.php' );
        $this->assertNull($wp_scripts);

        $this->go_to("/wp-admin/options.php");
        Player::enqueueScripts( 'options.php' );
        $this->assertNull($wp_scripts);

        $this->go_to("/wp-admin/post.php");
        Player::enqueueScripts( 'post.php' );
        $this->assertNull($wp_scripts);

        $this->go_to("/wp-admin/post-new.php");
        Player::enqueueScripts( 'post-new.php' );
        $this->assertNull($wp_scripts);

        $this->go_to("/?p={$postId}");
        Player::enqueueScripts( 'single.php' );
        $this->assertContains('beyondwords-sdk', $wp_scripts->queue);

        $wp_scripts = null;

    }

    /**
     * @test
     * @dataProvider scriptLoaderTagProvider
     */
    public function scriptLoaderTag($postArgs, $tag, $handle, $src, $expect)
    {
        global $post;

        set_current_screen('/wp-admin/front');

        $post = self::factory()->post->create_and_get($postArgs);

        setup_postdata($post);

        $output = Player::scriptLoaderTag($tag, $handle, $src);
        $output = trim($output);

        // Trim new lines and whitespace
        $output = trim(preg_replace('/\s\s+/', ' ', $output));

        $this->assertEquals($expect, $output);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    public function scriptLoaderTagProvider()
    {
        $tag    = '<script src="https://example.com/index.js"></script>';
        $handle = 'beyondwords-sdk';
        $src    = 'https://proxy.beyondwords.io/npm/@beyondwords/beyondwords-audio-player-v2@latest/dist/module/index.js';

        $playerScript = '<script data-beyondwords-sdk="true" async defer src="https://proxy.beyondwords.io/npm/@beyondwords/beyondwords-audio-player-v2@latest/dist/module/index.js" onload=\'document.querySelectorAll(&quot;div[data-beyondwords-player]&quot;).forEach(function(el) { new BeyondWords.Player({ ...{&quot;projectId&quot;:9969,&quot;contentId&quot;:&quot;9279c9e0-e0b5-4789-9040-f44478ed3e9e&quot;,&quot;playerStyle&quot;:&quot;standard&quot;}, target: el });});\' ></script>';

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
                'expect'   => $playerScript,
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
                'expect' => $playerScript,
            ],
        ];
    }
}
