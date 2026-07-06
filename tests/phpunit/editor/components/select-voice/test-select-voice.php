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

        $languageLabel = $crawler->filter('p#beyondwords-metabox-select-voice--language-code');
        $this->assertEquals('Language', $languageLabel->text());

        $languageSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $languageSelect);

        $this->assertSame('en_US', $languageSelect->filter('option:nth-child(34)')->attr('value'));
        $this->assertSame('English (American)', $languageSelect->filter('option:nth-child(34)')->text());

        $this->assertSame('en_GB', $languageSelect->filter('option:nth-child(36)')->attr('value'));
        $this->assertSame('English (British)', $languageSelect->filter('option:nth-child(36)')->text());

        $this->assertSame('cy_GB', $languageSelect->filter('option:nth-child(93)')->attr('value'));
        $this->assertSame('Welsh (Welsh)', $languageSelect->filter('option:nth-child(93)')->text());

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

        // The language dropdown still renders, with only the empty "Select a
        // language…" placeholder option — the failed API yields no languages.
        $languageSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $languageSelect);
        $this->assertCount(1, $languageSelect->filter('option'));
        $this->assertSame('', $languageSelect->filter('option')->attr('value'));

        // Render continued past the language select to the voice select, proving
        // no TypeError was thrown.
        $this->assertCount(1, $crawler->filter('#beyondwords_voice_id'));

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
