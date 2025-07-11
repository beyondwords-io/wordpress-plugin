<?php

/**
 * BeyondWords Display Player element.
 *
 * Text Domain: speechkit
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

use Beyondwords\Wordpress\Component\Post\DisplayPlayer\DisplayPlayer;
use \Symfony\Component\DomCrawler\Crawler;

class DisplayPlayerTest extends WP_UnitTestCase
{
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
        DisplayPlayer::init();

        do_action('wp_loaded');

        $this->assertEquals(20, has_action('save_post_post', array(DisplayPlayer::class, 'save')));
        $this->assertEquals(20, has_action('save_post_page', array(DisplayPlayer::class, 'save')));
    }

    /**
     * @test
     */
    public function save()
    {
        $_POST['beyondwords_display_player_nonce'] = wp_create_nonce('beyondwords_display_player');

        $postId = self::factory()->post->create([
            'post_title' => 'DisplayPlayerTest::save',
        ]);

        DisplayPlayer::save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_disabled', true));

        $_POST['beyondwords_display_player'] = '1';

        DisplayPlayer::save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_disabled', true));

        unset($_POST['beyondwords_display_player']);

        DisplayPlayer::save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_disabled', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function element()
    {
        DisplayPlayer::element(null);

        $html = $this->getActualOutput();

        $this->assertSame('', $html);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'DisplayPlayerTest::element',
        ]);

        DisplayPlayer::element($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-display-player'));

        $input = $crawler->filter('p#beyondwords-metabox-display-player input[type="checkbox"]');

        $this->assertEquals('beyondwords_display_player', $input->attr('id'));
        $this->assertEquals('beyondwords_display_player', $input->attr('name'));
        $this->assertEquals('1', $input->attr('value'));

        $label = $crawler->filter('p#beyondwords-metabox-display-player');

        $this->assertEquals('Display player', $label->text());

        wp_delete_post($post->ID, true);
    }
}
