<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Player\Player;

class PlayerTest extends WP_UnitTestCase
{
    public const PLAYER_HTML = '<script async defer src="https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js" onload=\'new BeyondWords.Player({target:this, ...{"projectId":9969,"contentId":"9279c9e0-e0b5-4789-9040-f44478ed3e9e","playerStyle":"standard"}});\'></script>';

    /**
     * @test
     * @group player
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
     * @group player
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
     * @group player
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
     * @group player
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
     * @test
     * @group player
     */
    public function renderPlayer()
    {
        global $post;

        // Case 1: No post set, should return empty string
        $this->assertSame('', Player::renderPlayer());

        // Case 2: Post is not a WP_Post instance, should return empty string
        $post = null;
        $this->assertSame('', Player::renderPlayer());

        // Case 3: Post exists but player is disabled via option
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::renderPlayer',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);
        update_option('beyondwords_player_ui', PlayerUI::DISABLED);
        setup_postdata($post);
        $this->assertSame('', Player::renderPlayer());

        // Case 4: Post exists but player is disabled via post meta
        update_option('beyondwords_player_ui', PlayerUI::ENABLED);
        update_post_meta($post->ID, 'beyondwords_disabled', '1');
        $this->assertSame('', Player::renderPlayer());

        // Case 5: Post exists, player enabled, should render player HTML
        delete_post_meta($post->ID, 'beyondwords_disabled');
        $this->assertSame(self::PLAYER_HTML, Player::renderPlayer());

        wp_reset_postdata();
        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group player
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
     * @group player
     *
     * @dataProvider contentProvider
     */
    public function hasCustomPlayer($expect, $content)
    {
        $this->assertEquals($expect, Player::hasCustomPlayer($content));
    }

    public function contentProvider()
    {
        return [
            'No player' => [false, '<p>No player.</p>'],
            'Legacy player' => [true, '<p>Before.</p><div data-beyondwords-player="true"></div><p>After.</p>'],
            'Legacy player with contenteditable attribute' => [true, '<p>Before.</p><div data-beyondwords-player="true" contenteditable="false"></div><p>After.</p>'],
            'New player shortcode' => [true, '<p>Before.</p>[beyondwords_player]<p>After.</p>'],
            'New player shortcode with project_id attribute' => [true, '<p>Before.</p>[beyondwords_player project_id="1234"]<p>After.</p>'],
        ];
    }
}
