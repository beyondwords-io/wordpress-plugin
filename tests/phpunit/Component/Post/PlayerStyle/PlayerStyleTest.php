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

class PostPlayerStyleTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(DATE_ISO8601));
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $playerStyle = new PlayerStyle();
        $playerStyle->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('rest_api_init', array($playerStyle, 'restApiInit')));
        $this->assertEquals(10, has_action('save_post_page', array($playerStyle, 'save')));
        $this->assertEquals(10, has_action('save_post_post', array($playerStyle, 'save')));
    }

    /**
     * @test
     */
    public function element()
    {
        $playerStyle = new PlayerStyle();

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostPlayerStyleTest::element',
        ]);

        $transientName = sprintf('beyondwords_player_styles[%s]', BEYONDWORDS_TESTS_PROJECT_ID);

        /**
         * Each player style is an associative array with the following keys:
         * - string  `label`    The option label e.g. "Standard"
         * - string  `value`    The option value e.g. "standard"
         * - boolean `disabled` (Optional) Is this option disabled?
         * - boolean `default`  (Optional) Is this the default player style, assigned in the plugin settings?
        */
        set_transient($transientName, [
            [
                'label' => 'Foo',
                'value' => 'foo',
            ],
            [
                'label' => 'Bar',
                'value' => 'bar',
            ],
            [
                'label' => 'Baz',
                'value' => 'baz',
            ],
        ]);

        $playerStyle->element($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-player-style'));

        $select = $crawler->filter('#beyondwords_player_style');
        $this->assertCount(1, $select);

        $this->assertSame('foo', $select->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Foo', $select->filter('option:nth-child(1)')->text());

        $this->assertSame('bar', $select->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Bar', $select->filter('option:nth-child(2)')->text());

        $this->assertSame('baz', $select->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Baz', $select->filter('option:nth-child(3)')->text());

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

        $playerStyle = new PlayerStyle();

        $postId = self::factory()->post->create([
            'post_title' => 'PlayerStyleTest::save',
        ]);

        $playerStyle->save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_player_style', true));

        $_POST['beyondwords_player_style'] = 'video';

        $playerStyle->save($postId);

        $this->assertEquals('video', get_post_meta($postId, 'beyondwords_player_style', true));

        unset($_POST['beyondwords_player_style']);

        $playerStyle->save($postId);

        $this->assertEquals('video', get_post_meta($postId, 'beyondwords_player_style', true));

        wp_delete_post($postId, true);
    }
}
