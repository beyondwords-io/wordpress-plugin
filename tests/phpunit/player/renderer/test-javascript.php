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
     * Stored-XSS regression: dangerous characters in a param value must never
     * break out of the single-quoted onload='...' attribute, whatever the payload.
     *
     * Asserts the security *property* — one intact <script> and the value survives
     * as inert JSON data — rather than a specific escaping mechanism, so it stays
     * valid if the escaping strategy is ever refactored. The injected value reaches
     * the renderer via `beyondwords_content_id`, the documented editor/REST sink.
     *
     * @test
     * @dataProvider dangerousContentIdProvider
     */
    public function render_neutralises_a_dangerous_content_id($payload)
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render_neutralises_a_dangerous_content_id',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => $payload,
            ],
        ]);

        $html = Javascript::render($post);

        // A real HTML parser must see exactly one <script> — a breakout would
        // truncate the attribute and/or inject sibling elements.
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));

        // The browser-decoded onload is the JS the engine actually runs.
        $onload = $crawler->filter('script')->attr('onload');
        $this->assertStringStartsWith('new BeyondWords.Player(', $onload);
        $this->assertStringEndsWith('}});', $onload);

        // The spread params are valid JSON and the payload survived verbatim as a
        // string value (inert data) — proving it can't execute and isn't corrupted.
        $this->assertSame(1, preg_match('/\.\.\.(\{.*\})\}\);$/', $onload, $matches));
        $params = json_decode($matches[1]);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame($payload, $params->contentId);

        wp_delete_post($post->ID, true);
    }

    /**
     * Payloads that survive sanitize_text_field() (the meta sanitiser registered
     * for beyondwords_content_id) — quotes, ampersands and Unicode. It strips
     * < > tags, so tag-based payloads can only reach the renderer via a filter;
     * those are covered by render_neutralises_dangerous_sdk_params_from_filter().
     */
    public function dangerousContentIdProvider()
    {
        return [
            'single quote (attribute breakout)' => ["123'); alert(document.cookie);//"],
            'double quote'                      => ['123"); alert(document.cookie);//'],
            'both quote styles'                 => ["o'brien " . '"quote"'],
            'ampersand and dashes'              => ['a&b-c&d'],
            'unicode'                           => ['café-naïve-señor'],
        ];
    }

    /**
     * The `beyondwords_player_script_onload` filter output is escaped too, so a
     * third-party filter that injects raw single quotes cannot break out of the
     * attribute. This isolates the esc_attr() defense: the filter result bypasses
     * wp_json_encode(), so the HEX flags do not protect it — only esc_attr() does.
     *
     * @test
     */
    public function render_escapes_an_onload_value_injected_via_filter()
    {
        $injected = "new BeyondWords.Player({}); alert('xss'); //";
        $filter   = static function () use ($injected) {
            return $injected;
        };
        add_filter('beyondwords_player_script_onload', $filter);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render_escapes_an_onload_value_injected_via_filter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $html = Javascript::render($post);

        remove_filter('beyondwords_player_script_onload', $filter);

        // esc_attr() + the browser's attribute decoding round-trip to the exact
        // filter output; a raw-apostrophe breakout would instead truncate it.
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));
        $this->assertSame($injected, $crawler->filter('script')->attr('onload'));

        wp_delete_post($post->ID, true);
    }

    /**
     * Defense-in-depth via the beyondwords_player_sdk_params filter — the other
     * injection vector named in the advisory. Third-party code can put *arbitrary*
     * unsanitised values into a param (here bypassing the sanitize_text_field()
     * that strips tags on meta save), so the renderer must neutralise ', ", <, >, &
     * in the JSON layer (JSON_HEX_APOS|QUOT|TAG|AMP) and still emit one intact
     * <script> whose params round-trip to the original value.
     *
     * @test
     */
    public function render_neutralises_dangerous_sdk_params_from_filter()
    {
        // contentId containing ' " < > & — the quote-style juggling keeps both
        // quote characters in the value without any backslash escaping.
        $dangerous = "a'b" . '"c<d>e&f';
        $filter    = static function ($params) use ($dangerous) {
            $params['contentId'] = $dangerous;
            return $params;
        };
        add_filter('beyondwords_player_sdk_params', $filter);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render_neutralises_dangerous_sdk_params_from_filter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $html = Javascript::render($post);

        remove_filter('beyondwords_player_sdk_params', $filter);

        // Still exactly one intact script, and the value round-trips as inert JSON
        // (including the < > tags that the meta sanitiser would otherwise strip).
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));
        $onload = $crawler->filter('script')->attr('onload');
        $this->assertSame(1, preg_match('/\.\.\.(\{.*\})\}\);$/', $onload, $matches));
        $params = json_decode($matches[1]);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame($dangerous, $params->contentId);

        // Each dangerous character is HEX-encoded in the raw markup; a dropped flag
        // would surface it in another form (e.g. &#039; or \") and fail the match.
        // The needle is built from the same encoder so no literal backslashes are
        // embedded in the test source.
        $hex = static function ($char) {
            return trim(wp_json_encode($char, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), '"');
        };
        $this->assertStringContainsString($hex("'"), $html); // JSON_HEX_APOS
        $this->assertStringContainsString($hex('"'), $html); // JSON_HEX_QUOT
        $this->assertStringContainsString($hex('<'), $html); // JSON_HEX_TAG
        $this->assertStringContainsString($hex('>'), $html); // JSON_HEX_TAG
        $this->assertStringContainsString($hex('&'), $html); // JSON_HEX_AMP

        wp_delete_post($post->ID, true);
    }
}
