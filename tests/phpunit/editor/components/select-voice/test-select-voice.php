<?php

/**
 * BeyondWords Select Voice element.
 *
 * Text Domain: speechkit
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.5.2
 */

use BeyondWords\Editor\Components\SelectVoice;
use \Symfony\Component\DomCrawler\Crawler;

class SelectVoiceTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // save() requires a user who can edit the post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

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
        SelectVoice::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('rest_api_init', array(SelectVoice::class, 'rest_api_init_callback')));
        $this->assertFalse(has_action('admin_enqueue_scripts', array(SelectVoice::class, 'admin_enqueue_scripts_callback')));
        $this->assertEquals(10, has_action('save_post_page', array(SelectVoice::class, 'save')));
        $this->assertEquals(10, has_action('save_post_post', array(SelectVoice::class, 'save')));
    }

    /**
     * @test
     */
    public function element()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element',
            'meta_input' => [
                // Set Language ID so we see the "Voice" <select>
                'beyondwords_language_code' => 'en_US',
            ],
        ]);

        $html = $this->capture_output(function () use ($post) {
            SelectVoice::element($post);
        });

        $crawler = new Crawler($html);

        $languageLabel = $crawler->filter('p#beyondwords-metabox-select-voice--language-name');
        $this->assertEquals('Language', $languageLabel->text());

        // Client-side control only, so it has no name attribute and isn't submitted.
        $nameSelect = $crawler->filter('#beyondwords_language_name');
        $this->assertCount(1, $nameSelect);
        $this->assertNull($nameSelect->attr('name'));

        // 79 distinct names + the "Select a language…" placeholder.
        $this->assertCount(80, $nameSelect->filter('option'));

        $this->assertSame('English', $nameSelect->filter('option:nth-child(4)')->attr('value'));
        $this->assertSame('English', $nameSelect->filter('option:nth-child(4)')->text());
        $this->assertNotNull($nameSelect->filter('option:nth-child(4)')->attr('selected'));

        $this->assertSame('Welsh', $nameSelect->filter('option:nth-child(53)')->attr('value'));
        $this->assertSame('Welsh', $nameSelect->filter('option:nth-child(53)')->text());

        // The Accent select carries the language CODE and is the submitted field.
        $accentLabel = $crawler->filter('#beyondwords-metabox-select-voice--accent label');
        $this->assertEquals('Accent', $accentLabel->text());

        $accentWrapper = $crawler->filter('#beyondwords-metabox-select-voice--accent');
        $this->assertStringNotContainsString('display: none', (string) $accentWrapper->attr('style'));

        $accentSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $accentSelect);
        $this->assertSame('beyondwords_language_code', $accentSelect->attr('name'));

        $this->assertCount(14, $accentSelect->filter('option'));

        $this->assertSame('en_US', $accentSelect->filter('option:nth-child(5)')->attr('value'));
        $this->assertSame('American', $accentSelect->filter('option:nth-child(5)')->text());
        $this->assertNotNull($accentSelect->filter('option:nth-child(5)')->attr('selected'));

        $this->assertSame('en_GB', $accentSelect->filter('option:nth-child(6)')->attr('value'));
        $this->assertSame('British', $accentSelect->filter('option:nth-child(6)')->text());

        $nativeLabel = $crawler->filter('#beyondwords-metabox-select-voice--native label');
        $this->assertEquals('Native', $nativeLabel->text());

        $nativeSelect = $crawler->filter('#beyondwords_native');
        $this->assertNull($nativeSelect->attr('name'));
        $this->assertSame(
            ['Native', 'All'],
            $nativeSelect->filter('option')->each(fn ($node) => $node->text())
        );
        $this->assertSame('native', $nativeSelect->filter('option[selected]')->attr('value'));

        // en_US offers several models, so the Model dropdown is visible with a
        // "Select a model" placeholder leading the buckets (Standard last).
        $modelLabel = $crawler->filter('#beyondwords-metabox-select-voice--model label');
        $this->assertEquals('Model', $modelLabel->text());

        $modelWrapper = $crawler->filter('#beyondwords-metabox-select-voice--model');
        $this->assertStringNotContainsString('display: none', (string) $modelWrapper->attr('style'));

        $modelOptions = $crawler->filter('#beyondwords_model option')->each(fn ($node) => $node->text());
        $this->assertSame(
            ['Select a model', 'Multilingual v2', 'v3', 'Flash v2.5', 'Legacy'],
            $modelOptions
        );

        // The Model select carries no name — it is a client-side filter only.
        $this->assertNull($crawler->filter('#beyondwords_model')->attr('name'));

        // No voice yet → the placeholder is selected and the Voice field hides.
        $this->assertSame('', $crawler->filter('#beyondwords_model option[selected]')->attr('value'));

        $voiceLabel = $crawler->filter('#beyondwords-metabox-select-voice--voice-id label');
        $this->assertEquals('Voice', $voiceLabel->text());

        $voiceWrapper = $crawler->filter('#beyondwords-metabox-select-voice--voice-id');
        $this->assertStringContainsString('display: none', (string) $voiceWrapper->attr('style'));

        // The Voice select is the saved field and stays in the DOM while hidden.
        $voiceSelect = $crawler->filter('#beyondwords_voice_id');
        $this->assertCount(1, $voiceSelect);
        $this->assertSame('beyondwords_voice_id', $voiceSelect->attr('name'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function element_scopes_voice_dropdown_to_selected_model()
    {
        // Bridget (id 9001) offers Multilingual v2 — the only en_US voice that
        // does — so selecting it scopes the Voice dropdown to just Bridget.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element_model',
            'meta_input' => [
                'beyondwords_language_code' => 'en_US',
                'beyondwords_body_voice_id' => '9001',
            ],
        ]);

        $html = $this->capture_output(function () use ($post) {
            SelectVoice::element($post);
        });

        $crawler = new Crawler($html);

        // Model dropdown is visible with every bucket; Bridget's model selected.
        $modelWrapper = $crawler->filter('#beyondwords-metabox-select-voice--model');
        $this->assertStringNotContainsString('display: none', (string) $modelWrapper->attr('style'));

        $modelOptions = $crawler->filter('#beyondwords_model option')->each(fn ($node) => $node->text());
        $this->assertSame(
            ['Select a model', 'Multilingual v2', 'v3', 'Flash v2.5', 'Legacy'],
            $modelOptions
        );
        $this->assertSame(
            'eleven_multilingual_v2',
            $crawler->filter('#beyondwords_model option[selected]')->attr('value')
        );

        // Voice dropdown is visible and scoped to the selected model: only
        // Bridget offers Multilingual v2.
        $voiceWrapper = $crawler->filter('#beyondwords-metabox-select-voice--voice-id');
        $this->assertStringNotContainsString('display: none', (string) $voiceWrapper->attr('style'));

        $voiceOptions = $crawler->filter('#beyondwords_voice_id option')->each(fn ($node) => $node->text());
        $this->assertSame(['Select a voice', 'Bridget'], $voiceOptions);

        // The persisted voice id stays selected.
        $this->assertSame(
            '9001',
            $crawler->filter('#beyondwords_voice_id option[selected]')->attr('value')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * Regression: a failed languages API call returns null (network error,
     * WP_Error, non-2xx, empty body or invalid JSON). Passed unguarded into
     * render_language_select()'s array-typed parameter under strict_types this
     * threw an uncatchable TypeError, crashing the classic-editor metabox. The
     * language dropdown must now render empty instead of fataling.
     *
     * @test
     */
    public function element_degrades_gracefully_when_languages_api_fails()
    {
        // Simulate the languages API being unreachable. Priority 1 short-circuits
        // before the mock API filter, which respects an earlier preempt.
        $filter = function ($preempt, $args, $url) {
            if (str_contains($url, '/organization/languages')) {
                return new \WP_Error('http_request_failed', 'Connection refused');
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        // No language code, so no voices API call is made — the languages call
        // is the only one exercised here.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element_languages_api_fails',
        ]);

        $html = $this->capture_output(function () use ($post) {
            SelectVoice::element($post);
        });

        remove_filter('pre_http_request', $filter, 1);

        $crawler = new Crawler($html);

        // The Language select still renders, with only the "Select a
        // language…" placeholder option — the failed API yields no languages.
        $nameSelect = $crawler->filter('#beyondwords_language_name');
        $this->assertCount(1, $nameSelect);
        $this->assertCount(1, $nameSelect->filter('option'));
        $this->assertSame('', $nameSelect->filter('option')->attr('value'));

        // The Accent select still submits a single empty option ('' = no language chosen).
        $accentSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $accentSelect);
        $this->assertCount(1, $accentSelect->filter('option'));
        $this->assertSame('', $accentSelect->filter('option')->attr('value'));

        // Render continued past the language select to the voice select, proving
        // no TypeError was thrown.
        $this->assertCount(1, $crawler->filter('#beyondwords_voice_id'));

        wp_delete_post($post->ID, true);
    }

    /**
     * Regression: a non-2xx voices response can carry the BeyondWords API's own
     * error shape — e.g. {"message": "Too many requests"} on a 429 — which
     * json_decode()s to ['message' => '<string>']. That passed the top-level
     * is_array() check, so the string element flowed into the render helpers
     * (find_voice() / voice_model_key()), which are typed for arrays, and threw
     * an uncatchable TypeError under strict_types — crashing the classic-editor
     * metabox mid-render on every load for the duration of an API incident. The
     * Model/Voice dropdowns must now degrade to empty instead of fataling.
     *
     * @test
     */
    public function element_degrades_gracefully_when_voices_api_returns_error_object()
    {
        // Answer the voices request with the API's {"message": ...} error body
        // and a 429. Priority 1 short-circuits before the mock API filter, which
        // respects an earlier preempt; the languages request is left to the mock
        // so only the voices path is exercised here.
        $filter = function ($preempt, $args, $url) {
            if (str_contains($url, '/organization/voices')) {
                return [
                    'response' => ['code' => 429, 'message' => 'Too Many Requests'],
                    'body'     => '{"message":"Too many requests"}',
                    'headers'  => [],
                    'cookies'  => [],
                ];
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        // A saved language code makes element() call the voices API.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element_voices_api_error_object',
            'meta_input' => [
                'beyondwords_language_code' => 'en_US',
            ],
        ]);

        $html = $this->capture_output(function () use ($post) {
            SelectVoice::element($post);
        });

        remove_filter('pre_http_request', $filter, 1);

        $crawler = new Crawler($html);

        // Render reached the Voice select — no TypeError was thrown.
        $this->assertCount(1, $crawler->filter('#beyondwords_voice_id'));

        // The error body yields no voice records, so both dropdowns hide and
        // carry only their placeholder option.
        $modelWrapper = $crawler->filter('#beyondwords-metabox-select-voice--model');
        $this->assertStringContainsString('display: none', (string) $modelWrapper->attr('style'));
        $this->assertSame(
            ['Select a model'],
            $crawler->filter('#beyondwords_model option')->each(fn ($node) => $node->text())
        );

        $voiceWrapper = $crawler->filter('#beyondwords-metabox-select-voice--voice-id');
        $this->assertStringContainsString('display: none', (string) $voiceWrapper->attr('style'));
        $this->assertSame(
            ['Select a voice'],
            $crawler->filter('#beyondwords_voice_id option')->each(fn ($node) => $node->text())
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function language_names()
    {
        $languages = [
            ['code' => 'en_US', 'name' => 'English', 'accent' => 'American'],
            ['code' => 'en_GB', 'name' => 'English', 'accent' => 'British'],
            ['code' => 'cy_GB', 'name' => 'Welsh', 'accent' => 'Welsh'],
            // Rows missing a code, name or accent are skipped.
            ['code' => 'xx_XX', 'name' => 'Broken'],
        ];

        $this->assertSame(['English', 'Welsh'], SelectVoice::language_names($languages));
    }

    /**
     * @test
     */
    public function accents_for_name()
    {
        $languages = [
            ['code' => 'en_US', 'name' => 'English', 'accent' => 'American'],
            ['code' => 'cy_GB', 'name' => 'Welsh', 'accent' => 'Welsh'],
            ['code' => 'en_GB', 'name' => 'English', 'accent' => 'British'],
        ];

        $accents = SelectVoice::accents_for_name($languages, 'English');

        $this->assertSame(['en_US', 'en_GB'], array_column($accents, 'code'));
        $this->assertSame([], SelectVoice::accents_for_name($languages, ''));
    }

    /**
     * @test
     */
    public function find_language_by_code()
    {
        $languages = [
            ['code' => 'en_US', 'name' => 'English', 'accent' => 'American'],
        ];

        $this->assertSame('English', SelectVoice::find_language_by_code($languages, 'en_US')['name']);
        $this->assertNull(SelectVoice::find_language_by_code($languages, 'xx_XX'));
        $this->assertNull(SelectVoice::find_language_by_code($languages, false));
    }

    /**
     * @test
     */
    public function languages_for_script()
    {
        $rows = SelectVoice::languages_for_script();

        $this->assertCount(148, $rows);
        $this->assertSame(
            ['code', 'name', 'accent', 'defaultVoiceId'],
            array_keys($rows[0])
        );

        $en = array_column($rows, null, 'code')['en_US'];
        $this->assertSame('English', $en['name']);
        $this->assertSame('American', $en['accent']);
        $this->assertSame('2517', $en['defaultVoiceId']);
    }

    /**
     * @test
     */
    public function voice_primary_code()
    {
        $this->assertSame('en_US', SelectVoice::voice_primary_code(['language' => 'en_US']));
        $this->assertSame('en_US', SelectVoice::voice_primary_code(['language' => ['code' => 'en_US']]));
        $this->assertSame('de_DE', SelectVoice::voice_primary_code(['languages' => [['code' => 'de_DE']]]));
        $this->assertSame('', SelectVoice::voice_primary_code(['name' => 'Nobody']));
    }

    /**
     * @test
     */
    public function voice_is_native()
    {
        $this->assertTrue(SelectVoice::voice_is_native(['language' => ['code' => 'en_US']], 'en_US'));
        $this->assertFalse(SelectVoice::voice_is_native(['language' => ['code' => 'de_DE']], 'en_US'));

        // A voice with no determinable primary language is treated as native.
        $this->assertTrue(SelectVoice::voice_is_native(['name' => 'Nobody'], 'en_US'));
    }

    /**
     * @test
     */
    public function filter_voices_by_native()
    {
        $native     = ['id' => 1, 'name' => 'Ada', 'language' => ['code' => 'en_US']];
        $nonNative  = ['id' => 2, 'name' => 'Klaus', 'language' => ['code' => 'de_DE']];
        $voices     = [$native, $nonNative];

        $this->assertSame(
            [1],
            array_column(SelectVoice::filter_voices_by_native($voices, 'en_US', 'native', ''), 'id')
        );
        $this->assertSame(
            [1, 2],
            array_column(SelectVoice::filter_voices_by_native($voices, 'en_US', 'all', ''), 'id')
        );

        // The kept voice is unioned back in even when native-only would hide it.
        $this->assertSame(
            [1, 2],
            array_column(SelectVoice::filter_voices_by_native($voices, 'en_US', 'native', '2'), 'id')
        );
    }

    /**
     * @test
     */
    public function default_native_filter()
    {
        $voices = [
            ['id' => 1, 'name' => 'Ada', 'language' => ['code' => 'en_US']],
            ['id' => 2, 'name' => 'Klaus', 'language' => ['code' => 'de_DE']],
        ];

        $this->assertSame('native', SelectVoice::default_native_filter($voices, 'en_US', '1'));
        $this->assertSame('all', SelectVoice::default_native_filter($voices, 'en_US', '2'));
        $this->assertSame('native', SelectVoice::default_native_filter($voices, 'en_US', ''));
    }

    /**
     * A saved voice that is not native to the language opens the picker on the
     * "All" Native filter so it stays visible in the Voice dropdown.
     *
     * @test
     */
    public function element_opens_on_all_for_non_native_saved_voice()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element_non_native',
            'meta_input' => [
                'beyondwords_language_code' => 'en_US',
                // Klaus (9500) is German-primary, non-native to en_US.
                'beyondwords_body_voice_id' => '9500',
            ],
        ]);

        $html = $this->capture_output(function () use ($post) {
            SelectVoice::element($post);
        });

        $crawler = new Crawler($html);

        $this->assertSame('all', $crawler->filter('#beyondwords_native option[selected]')->attr('value'));

        $voiceOptions = $crawler->filter('#beyondwords_voice_id option')->each(fn ($node) => $node->text());
        $this->assertContains('Klaus (Multilingual)', $voiceOptions);
        $this->assertSame(
            '9500',
            $crawler->filter('#beyondwords_voice_id option[selected]')->attr('value')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function voice_model_key()
    {
        $this->assertSame(
            'eleven_v3',
            SelectVoice::voice_model_key(['service' => 'ElevenLabs', 'model_id' => 'eleven_v3'])
        );

        // Non-ElevenLabs voices, and ElevenLabs voices without a string
        // model_id, fall into the shared Standard bucket.
        $this->assertSame('standard', SelectVoice::voice_model_key(['name' => 'Ada (Multilingual)']));
        $this->assertSame('standard', SelectVoice::voice_model_key(['service' => 'Azure', 'model_id' => null]));
        $this->assertSame('standard', SelectVoice::voice_model_key(['service' => 'ElevenLabs']));

        // Defensive: a non-record value — e.g. a scalar left by a decoded API
        // error body — buckets as Standard rather than fataling against the
        // parameter type. Mirrors the JS `voice?.` guard.
        $this->assertSame('standard', SelectVoice::voice_model_key('Too many requests'));
        $this->assertSame('standard', SelectVoice::voice_model_key(null));
    }

    /**
     * @test
     */
    public function language_models()
    {
        // API order puts a non-default ElevenLabs model first, to prove the
        // default is pulled to the front while the rest keep order, Standard last.
        $voices = [
            ['id' => 9001, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_flash_v2_5'],
            ['id' => 9002, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_multilingual_v2'],
            ['id' => 9003, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_v3'],
            ['id' => 3555, 'name' => 'Ada (Multilingual)'],
        ];

        $models = SelectVoice::language_models($voices);

        $this->assertSame(
            ['eleven_multilingual_v2', 'eleven_flash_v2_5', 'eleven_v3', 'standard'],
            array_column($models, 'key')
        );
        $this->assertSame(
            ['Multilingual v2', 'Flash v2.5', 'v3', 'Legacy'],
            array_column($models, 'label')
        );

        // The Standard bucket is omitted when every voice is ElevenLabs.
        $elevenOnly = SelectVoice::language_models(array_slice($voices, 0, 3));
        $this->assertSame(
            ['eleven_multilingual_v2', 'eleven_flash_v2_5', 'eleven_v3'],
            array_column($elevenOnly, 'key')
        );
    }

    /**
     * @test
     */
    public function voice_model_label()
    {
        $this->assertSame('Multilingual v2', SelectVoice::voice_model_label('eleven_multilingual_v2'));
        $this->assertSame('v3', SelectVoice::voice_model_label('eleven_v3'));
        $this->assertSame('Flash v2.5', SelectVoice::voice_model_label('eleven_flash_v2_5'));

        // Unknown slugs fall back to a title-cased label without the prefix.
        $this->assertSame('Custom Model', SelectVoice::voice_model_label('eleven_custom_model'));
    }

    /**
     * @test
     */
    public function save()
    {
        $_POST['beyondwords_select_voice_nonce'] = wp_create_nonce('beyondwords_select_voice');

        $postId = self::factory()->post->create([
            'post_title' => 'SelectVoiceTest::save',
        ]);

        SelectVoice::save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        $_POST['beyondwords_voice_id'] = '1';

        SelectVoice::save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        $_POST['beyondwords_language_code'] = 'en_US';
        $_POST['beyondwords_voice_id'] = '1';

        SelectVoice::save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        unset($_POST['beyondwords_voice_id']);

        SelectVoice::save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function rest_api_init_callback_registers_routes()
    {
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();

        // Must run inside rest_api_init or WP raises a "_doing_it_wrong" notice.
        add_action('rest_api_init', [SelectVoice::class, 'rest_api_init_callback']);
        do_action('rest_api_init');

        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey('/beyondwords/v1/languages', $routes);
        $this->assertArrayHasKey('/beyondwords/v1/languages/(?P<languageCode>[a-zA-Z0-9-_]+)/voices', $routes);

        remove_action('rest_api_init', [SelectVoice::class, 'rest_api_init_callback']);
    }

    /**
     * @test
     */
    public function languages_rest_api_response_returns_wp_rest_response()
    {
        $response = SelectVoice::languages_rest_api_response();

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
    }

    /**
     * @test
     */
    public function voices_rest_api_response_returns_wp_rest_response()
    {
        $request = new \WP_REST_Request('GET', '/beyondwords/v1/languages/en_US/voices');
        $request->set_url_params(['languageCode' => 'en_US']);

        $response = SelectVoice::voices_rest_api_response($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
    }

}
