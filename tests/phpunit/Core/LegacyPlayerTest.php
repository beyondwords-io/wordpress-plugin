<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\LegacyPlayer;
use \Symfony\Component\DomCrawler\Crawler;

class LegacyPlayerTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Core\Player\LegacyPlayer
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

        $this->assertNull($wp_scripts);

        set_current_screen('/wp-admin/options.php');
        $this->_instance->enqueueScripts();
        $this->assertNull($wp_scripts);

        set_current_screen('/wp-admin/edit.php');
        $this->_instance->enqueueScripts();
        $this->assertNull($wp_scripts);

        set_current_screen('/wp-admin/post.php');
        $this->_instance->enqueueScripts();
        $this->assertNull($wp_scripts);

        $wp_scripts = null;
    }
}
