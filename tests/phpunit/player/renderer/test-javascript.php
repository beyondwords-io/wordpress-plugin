<?php

use BeyondWords\Core\Urls;
use BeyondWords\Player\Renderer\Javascript;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class JavascriptTest
 */
class JavascriptTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
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

        $html = Javascript::render($post);

        $crawler = new Crawler($html);

        $script = $crawler->filter('script[async][defer]');
        $this->assertCount(1, $script);
        $this->assertSame(Urls::get_js_sdk_url(), $script->attr('src'));
        $this->assertNotEmpty($script->attr('onload'));

        wp_delete_post($post->ID, true);
    }

    /**
     * Stored-XSS regression: a dangerous Content ID must never break out of the single-quoted onload attribute.
     *
     * Asserts the security *property* (one intact <script>, value inert JSON), not a
     * specific escaping mechanism, so refactoring the escaping keeps it valid.
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
            ],
        ]);

        // The meta sanitiser would blank this value, so store it raw to simulate
        // a legacy/externally-written row reaching the renderer.
        $this->store_raw_content_id($post->ID, $payload);

        $html = Javascript::render($post);

        // A breakout would truncate the attribute and/or inject sibling elements.
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));

        // The browser-decoded onload is the JS the engine actually runs.
        $onload = $crawler->filter('script')->attr('onload');
        $this->assertStringStartsWith('new BeyondWords.Player(', $onload);
        $this->assertStringEndsWith('}});', $onload);

        // Valid JSON with the payload verbatim: inert data, not executable, not corrupted.
        $this->assertSame(1, preg_match('/\.\.\.(\{.*\})\}\);$/', $onload, $matches));
        $params = json_decode($matches[1]);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame($payload, $params->contentId);

        wp_delete_post($post->ID, true);
    }

    /**
     * Write a Content ID straight into post meta, bypassing Meta::sanitize_content_id().
     *
     * Reproduces a hostile legacy/raw row so the renderer's output escaping stays under test.
     */
    private function store_raw_content_id($post_id, $value)
    {
        global $wpdb;

        $wpdb->insert($wpdb->postmeta, [
            'post_id'    => $post_id,
            'meta_key'   => 'beyondwords_content_id',
            'meta_value' => $value,
        ]);

        wp_cache_delete($post_id, 'post_meta');
    }

    /**
     * Values that would break out of, or corrupt, the onload attribute.
     *
     * The meta sanitiser blanks these, hence the raw DB write; tag-based payloads
     * are covered by render_neutralises_dangerous_sdk_params_from_filter().
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
     * The `beyondwords_player_script_onload` filter output is escaped too.
     *
     * The filter result bypasses wp_json_encode(), so the JSON HEX flags do not
     * protect it — this isolates the esc_attr() defense.
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

        // The attribute must round-trip to the exact filter output; a breakout would truncate it.
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));
        $this->assertSame($injected, $crawler->filter('script')->attr('onload'));

        wp_delete_post($post->ID, true);
    }

    /**
     * Defense-in-depth via the beyondwords_player_sdk_params filter, the advisory's other injection vector.
     *
     * Filters can inject arbitrary unsanitised values, so the JSON layer itself
     * (JSON_HEX_APOS|QUOT|TAG|AMP) must neutralise ' " < > &.
     *
     * @test
     */
    public function render_neutralises_dangerous_sdk_params_from_filter()
    {
        // Quote-style juggling keeps both quote chars in the value without backslash escaping.
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

        // The value must round-trip as inert JSON — including < >, which the meta sanitiser would strip.
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('script'));
        $onload = $crawler->filter('script')->attr('onload');
        $this->assertSame(1, preg_match('/\.\.\.(\{.*\})\}\);$/', $onload, $matches));
        $params = json_decode($matches[1]);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame($dangerous, $params->contentId);

        // A dropped HEX flag would surface the char in another form (e.g. &#039;) and fail
        // the match; building needles via the same encoder avoids literal backslashes.
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
