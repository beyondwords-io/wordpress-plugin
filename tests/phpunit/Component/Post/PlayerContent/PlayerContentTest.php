<?php

/**
 * BeyondWords Player Content element.
 *
 * Text Domain: speechkit
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.5.2
 */

use Beyondwords\Wordpress\Component\Post\PlayerContent\PlayerContent;
use \Symfony\Component\DomCrawler\Crawler;

class PostPlayerContentTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $playerContent = new PlayerContent();
        $playerContent->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('save_post_page', array($playerContent, 'save')));
        $this->assertEquals(10, has_action('save_post_post', array($playerContent, 'save')));
    }

    /**
     * @test
     */
    public function element()
    {
        $playerContent = new PlayerContent();

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostPlayerContentTest::element',
        ]);

        $playerContent->element($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-player-content'));

        $select = $crawler->filter('#beyondwords_player_content');
        $this->assertCount(1, $select);

        $this->assertSame('', $select->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Article', $select->filter('option:nth-child(1)')->text());

        $this->assertSame('summary', $select->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Summary', $select->filter('option:nth-child(2)')->text());

        $label = $crawler->filter('p#beyondwords-metabox-player-content');

        $this->assertEquals('Player content', $label->text());

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save()
    {
        $_POST['beyondwords_player_content_nonce'] = wp_create_nonce('beyondwords_player_content');

        $playerContent = new PlayerContent();

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerContentTest::save',
        ]);

        $playerContent->save($postId);

        $this->assertFalse(metadata_exists('post', $postId, 'beyondwords_player_content'));

        $_POST['beyondwords_player_content'] = '';

        $playerContent->save($postId);

        $this->assertFalse(metadata_exists('post', $postId, 'beyondwords_player_content'));

        $_POST['beyondwords_player_content'] = 'summary';

        $playerContent->save($postId);

        $this->assertEquals('summary', get_post_meta($postId, 'beyondwords_player_content', true));

        $_POST['beyondwords_player_content'] = '';

        $playerContent->save($postId);

        $this->assertFalse(metadata_exists('post', $postId, 'beyondwords_player_content'));

        wp_delete_post($postId, true);
    }
}
