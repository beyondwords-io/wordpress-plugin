<?php

declare(strict_types=1);

use BeyondWords\Settings\Fields;
use BeyondWords\Core\Environment;
use BeyondWords\Player\Player;
use Symfony\Component\DomCrawler\Crawler;

class PlayerTest extends TestCase
{
    public const PLAYER_HTML_FORMAT = '<script data-beyondwords-player-context="%s" async defer src="https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js" onload=\'new BeyondWords.Player({target:this, ...{"projectId":9969,"contentId":"9279c9e0-e0b5-4789-9040-f44478ed3e9e"}});\'></script>';

    /**
     * @test
     * @group player
     */
    public function init()
    {
        Player::init();

        do_action('wp_loaded');

        // Actions
        $this->assertEquals(10, has_action('init', array(Player::class, 'register_shortcodes')));

        // Filters
        $this->assertEquals(1000000, has_filter('the_content', array(Player::class, 'auto_prepend_player')));
        $this->assertEquals(10, has_filter('newsstand_the_content', array(Player::class, 'auto_prepend_player')));
    }

    /**
     * @test
     * @group player
     */
    public function register_shortcodes()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::register_shortcodes',
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

        $this->assertSame("<p>Before</p>\n" . sprintf(self::PLAYER_HTML_FORMAT, 'shortcode') . "\n<p>After</p>", $content);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group player
     */
    public function replaces_legacy_div_in_the_content()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::replaces_legacy_div_in_the_content',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
            'post_content' => "<p>Before</p>\n<div data-beyondwords-player=\"true\"></div>\n<p>After</p>",
        ]);

        $this->go_to("/?p={$post->ID}");

        setup_postdata($post);

        ob_start();
        \the_content();
        $content = trim(ob_get_clean());

        $this->assertSame("<p>Before</p>\n" . sprintf(self::PLAYER_HTML_FORMAT, 'shortcode') . "\n<p>After</p>", $content);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group player
     */
    public function auto_prepend_player()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::auto_prepend_player',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        setup_postdata($post);

        $content = '<p>Test content.</p>';

        $output = Player::auto_prepend_player($content);

        // auto_prepend_player() should not affect $content unless is_singular()
        $this->assertSame($content, $output);

        $this->go_to("/?p={$post->ID}");

        $output = Player::auto_prepend_player($content);

        // We are now is_singular() so player should be prepended
        $this->assertSame(sprintf(self::PLAYER_HTML_FORMAT, 'auto') . $content, $output);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group player
     *
     * @dataProvider replaceLegacyCustomPlayerProvider
     */
    public function replace_legacy_custom_player($content, $expected)
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::replace_legacy_custom_player',
            'post_content' => $content,
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        setup_postdata($post);

        $output = Player::replace_legacy_custom_player($content);

        // Replacement only happens when is_singular()
        $this->assertSame($content, $output);

        $this->go_to("/?p={$post->ID}");

        $output = Player::replace_legacy_custom_player($content);

        // We are now is_singular() so player div should be replaced with player shortcode
        $this->assertSame($expected, $output);

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    public function replaceLegacyCustomPlayerProvider()
    {
        return [
            // === SHOULD BE REPLACED ===
            'Basic div' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with contenteditable after' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\" contenteditable=\"false\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with contenteditable before' => [
                "<p>Before</p>\n<div contenteditable=\"false\" data-beyondwords-player=\"true\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Self-closing div' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\" />\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with whitespace inside' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"> </div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with contenteditable before and whitespace inside' => [
                "<p>Before</p>\n<div contenteditable=\"false\" data-beyondwords-player=\"true\"> </div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with single quotes' => [
                "<p>Before</p>\n<div data-beyondwords-player='true'></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with newline inside' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\">\n</div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Div with tabs inside' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\">\t\t</div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Multiple player divs' => [
                "<div data-beyondwords-player=\"true\"></div>\n<p>Middle</p>\n<div data-beyondwords-player=\"true\"> </div>",
                "[beyondwords_player]\n<p>Middle</p>\n[beyondwords_player]",
            ],
            'Attribute value without quotes' => [
                "<p>Before</p>\n<div data-beyondwords-player=true></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Boolean attribute (no value)' => [
                "<p>Before</p>\n<div data-beyondwords-player></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Boolean attribute with other attrs' => [
                "<p>Before</p>\n<div data-beyondwords-player contenteditable=\"false\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Boolean attribute with whitespace inside' => [
                "<p>Before</p>\n<div contenteditable=\"false\" data-beyondwords-player> </div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Attribute with false value (still a boolean attr)' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"false\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],
            'Attribute with arbitrary value' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"anything\"></div>\n<p>After</p>",
                "<p>Before</p>\n[beyondwords_player]\n<p>After</p>",
            ],

            // === SHOULD NOT BE REPLACED ===
            'No player div' => [
                "<p>Before</p>\n<div class=\"other\">Content</div>\n<p>After</p>",
                "<p>Before</p>\n<div class=\"other\">Content</div>\n<p>After</p>",
            ],
            'Div with text content - should preserve' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\">Some text</div>\n<p>After</p>",
                "<p>Before</p>\n<div data-beyondwords-player=\"true\">Some text</div>\n<p>After</p>",
            ],
            'Div with nested elements - should preserve' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"><span>Nested</span></div>\n<p>After</p>",
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"><span>Nested</span></div>\n<p>After</p>",
            ],
            'Span element with player attribute - should preserve' => [
                "<p>Before</p>\n<span data-beyondwords-player=\"true\"></span>\n<p>After</p>",
                "<p>Before</p>\n<span data-beyondwords-player=\"true\"></span>\n<p>After</p>",
            ],
            'Attribute in class name - should preserve' => [
                "<p>Before</p>\n<div class=\"data-beyondwords-player-true\"></div>\n<p>After</p>",
                "<p>Before</p>\n<div class=\"data-beyondwords-player-true\"></div>\n<p>After</p>",
            ],
            'Attribute string in id value - should preserve' => [
                "<p>Before</p>\n<div id=\"data-beyondwords-player\"></div>\n<p>After</p>",
                "<p>Before</p>\n<div id=\"data-beyondwords-player\"></div>\n<p>After</p>",
            ],
            'Attribute string in data attr value - should preserve' => [
                "<p>Before</p>\n<div data-foo=\"data-beyondwords-player\"></div>\n<p>After</p>",
                "<p>Before</p>\n<div data-foo=\"data-beyondwords-player\"></div>\n<p>After</p>",
            ],
            // Note: HTML comments are NOT handled specially by the regex.
            // This is a known limitation but is acceptable because:
            // 1. It's extremely unlikely someone would put a player div in a comment
            // 2. Even if replaced, the shortcode in a comment won't render (invisible to users)
            'HTML comment with player div - known limitation' => [
                "<p>Before</p>\n<!-- <div data-beyondwords-player=\"true\"></div> -->\n<p>After</p>",
                "<p>Before</p>\n<!-- [beyondwords_player] -->\n<p>After</p>",
            ],
            'Div with child div inside - should preserve' => [
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"><div></div></div>\n<p>After</p>",
                "<p>Before</p>\n<div data-beyondwords-player=\"true\"><div></div></div>\n<p>After</p>",
            ],
        ];
    }

    /**
     * @test
     * @group player
     */
    public function render_player()
    {
        global $post;

        // Case 1: No post set, should return empty string
        $this->assertSame('', Player::render_player());

        // Case 2: Post is not a WP_Post instance, should return empty string
        $post = null;
        $this->assertSame('', Player::render_player());

        // Case 3: Post exists but player is disabled via option
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::render_player',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_DISABLED);
        setup_postdata($post);
        $this->assertSame('', Player::render_player());

        // Case 4: Post exists but player is disabled via post meta
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_ENABLED);
        update_post_meta($post->ID, 'beyondwords_disabled', '1');
        $this->assertSame('', Player::render_player());

        // Case 5: Post exists, player enabled, should render player HTML
        delete_post_meta($post->ID, 'beyondwords_disabled');
        $this->assertSame(sprintf(self::PLAYER_HTML_FORMAT, 'shortcode'), Player::render_player());

        wp_reset_postdata();
        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function renderPlayerWithFilter()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PlayerTest::renderPlayerWithFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        setup_postdata($post);

        $filter = function($html, $postId, $projectId, $contentId, $context) {
            return sprintf(
                '<div id="wrapper" data-post-id="%d" data-project-id="%d" data-podcast-id="%s" data-context="%s">%s</div>',
                $postId,
                $projectId,
                $contentId,
                $context,
                $html
            );
        };

        add_filter('beyondwords_player_html', $filter, 10, 5);

        $html = Player::render_player();

        remove_filter('beyondwords_player_html', $filter, 10, 5);

        $crawler = new Crawler($html);

        // <div id="wrapper">
        $wrapper = $crawler->filter('#wrapper');
        $this->assertCount(1, $wrapper);
        $this->assertSame("$post->ID", $wrapper->attr('data-post-id'));
        $this->assertSame(BEYONDWORDS_TESTS_PROJECT_ID, $wrapper->attr('data-project-id'));
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID, $wrapper->attr('data-podcast-id'));
        $this->assertSame('shortcode', $wrapper->attr('data-context'));

        $script = $wrapper->filter('script[async][defer]');
        $this->assertCount(1, $script);
        $this->assertSame(Environment::get_js_sdk_url(), $script->attr('src'));
        $this->assertNotEmpty($script->attr('onload'));
        $this->assertSame('shortcode', $script->attr('data-beyondwords-player-context'));

        wp_reset_postdata();
        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group player
     */
    public function is_enabled()
    {
        update_option(Fields::OPTION_PLAYER_UI, Fields::PLAYER_UI_DISABLED);

        $post = self::factory()->post->create_and_get();

        $this->assertFalse(Player::is_enabled($post));

        delete_option(Fields::OPTION_PLAYER_UI);

        $this->assertTrue(Player::is_enabled($post));

        update_post_meta($post->ID, 'beyondwords_disabled', '1');

        $this->assertFalse(Player::is_enabled($post));

        wp_delete_post($post->ID, true);
    }


    /**
     * @test
     * @group player
     *
     * @dataProvider contentProvider
     */
    public function has_custom_player($expect, $content)
    {
        $this->assertEquals($expect, Player::has_custom_player($content));
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
