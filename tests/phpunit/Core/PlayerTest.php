<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Player;
use \Symfony\Component\DomCrawler\Crawler;

class PlayerTest extends WP_UnitTestCase
{
    public const PLAYER_HTML = '<script async defer src="https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js" onload=\'new BeyondWords.Player({...{"projectId":9969,"contentId":"9279c9e0-e0b5-4789-9040-f44478ed3e9e","playerStyle":"standard"}, target:this});\'></script>';

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

        $this->assertSame("<p>Before</p>\n" . self::PLAYER_HTML . "\n<p>After</p>", $content);

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
        $this->assertSame(self::PLAYER_HTML . $content, $output);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function replaceLegacyCustomPlayer()
    {
        global $post;

        $content = "<p>Before</p>\n<div data-beyondwords-player=\"true\" contenteditable=\"false\"></div>\n<p>After</p>";

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::autoPrependPlayer',
            'post_content' => $content,
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        setup_postdata($post);

        $output = Player::replaceLegacyCustomPlayer($content);

        // Replacement only happens when is_singular()
        $this->assertSame($content, $output);

        $this->go_to("/?p={$post->ID}");

        $output = Player::replaceLegacyCustomPlayer($content);

        // We are now is_singular() so player div should be replaced with player shortcode
        $this->assertSame("<p>Before</p>\n[beyondwords_player]\n<p>After</p>", $output);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * Render a player (AMP/JS depending on context).
     *
     * @return string
     */
    public static function renderPlayer()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    /**
     * @test
     */
    public function isEnabled()
    {
        update_option('beyondwords_player_ui', PlayerUI::DISABLED);

        $post = self::factory()->post->create_and_get();

        $this->assertFalse(Player::isEnabled($post));

        delete_option('beyondwords_player_ui');

        $this->assertTrue(Player::isEnabled($post));

        update_post_meta($post->ID, 'beyondwords_disabled', '1');

        $this->assertFalse(Player::isEnabled($post));

        wp_delete_post($post->ID, true);
    }


    /**
     * @test
     */
    public function hasCustomPlayer()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }
}
