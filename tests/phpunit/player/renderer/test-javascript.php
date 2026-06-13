<?php

use BeyondWords\Core\Urls;
use BeyondWords\Player\Renderer\Javascript;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class Javascript.
 *
 * Responsible for rendering the JavaScript BeyondWords player.
 */
class JavascriptTest extends TestCase
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
    public function render()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        // setup_postdata($post);

        $html = Javascript::render($post);

        $crawler = new Crawler($html);

        $script = $crawler->filter('script[async][defer]');
        $this->assertCount(1, $script);
        $this->assertSame(Urls::get_js_sdk_url(), $script->attr('src'));
        $this->assertNotEmpty($script->attr('onload'));

        // wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * A Content ID containing a single quote must not break out of the
     * single-quoted onload='...' attribute (stored XSS regression).
     *
     * Prior to the fix, an editor-supplied `beyondwords_content_id` such as
     * `123'); alert(document.cookie);//` closed the onload attribute and injected
     * executable markup on the public front end. The renderer now esc_attr()s the
     * onload value and encodes ', ", <, >, & via the wp_json_encode() HEX flags.
     *
     * @test
     */
    public function render_does_not_allow_content_id_to_break_out_of_onload()
    {
        $payload = "123'); alert(document.cookie);//";

        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render_does_not_allow_content_id_to_break_out_of_onload',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => $payload,
            ],
        ]);

        $html = Javascript::render($post);

        // A real HTML parser must see exactly one <script> with an intact onload —
        // a breakout would truncate the attribute and/or spawn sibling markup.
        $crawler = new Crawler($html);
        $script  = $crawler->filter('script');
        $this->assertCount(1, $script);

        $onload = $script->attr('onload');
        $this->assertStringStartsWith('new BeyondWords.Player(', $onload);
        // If the attribute had broken out, the parsed value would be truncated
        // mid-string and would not contain the constructor's closing `}});`.
        $this->assertStringEndsWith('}});', $onload);

        // The raw markup must not contain an unescaped apostrophe able to close the
        // attribute; the JSON encoder emits it as the unicode escape \\u0027 instead.
        $this->assertStringNotContainsString("'); alert", $html);
        $this->assertStringContainsString('\\u0027', $html);

        wp_delete_post($post->ID, true);
    }
}
