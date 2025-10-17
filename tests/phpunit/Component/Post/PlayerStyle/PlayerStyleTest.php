<?php

/**
 * BeyondWords Player Style element.
 *
 * Text Domain: speechkit
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.5.2
 */

use Beyondwords\Wordpress\Component\Post\PlayerStyle\PlayerStyle;
use \Symfony\Component\DomCrawler\Crawler;

class PostPlayerStyleTest extends TestCase
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
        PlayerStyle::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('rest_api_init', array(PlayerStyle::class, 'restApiInit')));
        $this->assertEquals(10, has_action('save_post_page', array(PlayerStyle::class, 'save')));
        $this->assertEquals(10, has_action('save_post_post', array(PlayerStyle::class, 'save')));
    }

    /**
     * @test
     */
    public function element()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostPlayerStyleTest::element',
        ]);

        $html = $this->captureOutput(function () use ($post) {
            PlayerStyle::element($post);
        });

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-player-style'));

        $select = $crawler->filter('#beyondwords_player_style');
        $this->assertCount(1, $select);

        $this->assertSame('', $select->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('', $select->filter('option:nth-child(1)')->text());

        $this->assertSame('standard', $select->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Standard', $select->filter('option:nth-child(2)')->text());

        $this->assertSame('small', $select->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Small', $select->filter('option:nth-child(3)')->text());

        $this->assertSame('large', $select->filter('option:nth-child(4)')->attr('value'));
        $this->assertSame('Large', $select->filter('option:nth-child(4)')->text());

        $this->assertSame('video', $select->filter('option:nth-child(5)')->attr('value'));
        $this->assertSame('Video', $select->filter('option:nth-child(5)')->text());

        $label = $crawler->filter('p#beyondwords-metabox-player-style');

        $this->assertEquals('Player style', $label->text());

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save()
    {
        $_POST['beyondwords_player_style_nonce'] = wp_create_nonce('beyondwords_player_style');

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerStyleTest::save',
        ]);

        PlayerStyle::save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_player_style', true));

        $_POST['beyondwords_player_style'] = 'video';

        PlayerStyle::save($postId);

        $this->assertEquals('video', get_post_meta($postId, 'beyondwords_player_style', true));

        unset($_POST['beyondwords_player_style']);

        PlayerStyle::save($postId);

        $this->assertEquals('video', get_post_meta($postId, 'beyondwords_player_style', true));

        wp_delete_post($postId, true);
    }
}
