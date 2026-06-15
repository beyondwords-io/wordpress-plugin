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

        $voiceLabel = $crawler->filter('p#beyondwords-metabox-select-voice--voice-id');
        $this->assertEquals('Voice', $voiceLabel->text());

        // The Voice dropdown lists distinct names, "Select a voice" first.
        $voiceSelect = $crawler->filter('#beyondwords_voice');
        $this->assertCount(1, $voiceSelect);

        $this->assertSame('', $voiceSelect->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Select a voice', $voiceSelect->filter('option:nth-child(1)')->text());

        $this->assertSame('Ada (Multilingual)', $voiceSelect->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Ava (Multilingual)', $voiceSelect->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Ollie (Multilingual)', $voiceSelect->filter('option:nth-child(4)')->attr('value'));

        // ElevenLabs "Bridget" appears once despite having three models.
        $names = $voiceSelect->filter('option')->each(fn ($node) => $node->text());
        $this->assertSame(1, count(array_keys($names, 'Bridget', true)));

        // With no voice selected the Model dropdown is hidden and empty.
        $modelWrapper = $crawler->filter('#beyondwords-metabox-select-voice--model');
        $this->assertStringContainsString('display: none', (string) $modelWrapper->attr('style'));
        $this->assertCount(0, $crawler->filter('#beyondwords_voice_id option'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function element_shows_model_dropdown_for_multi_model_voice()
    {
        // Bridget (id 9001) is an ElevenLabs voice with three models.
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

        // Voice name dropdown shows "Bridget" selected.
        $this->assertSame(
            'Bridget',
            $crawler->filter('#beyondwords_voice option[selected]')->attr('value')
        );

        // Model dropdown is visible with the three variants, default first.
        $modelWrapper = $crawler->filter('#beyondwords-metabox-select-voice--model');
        $this->assertStringNotContainsString('display: none', (string) $modelWrapper->attr('style'));

        $modelOptions = $crawler->filter('#beyondwords_voice_id option')->each(fn ($node) => $node->text());
        $this->assertSame(['Multilingual v2', 'v3', 'Flash v2.5'], $modelOptions);

        // The persisted variant id stays selected.
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

        // The language dropdown still renders — only the empty placeholder
        // option, with no language entries (the failed API returns none).
        $languageSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $languageSelect);
        $options = $languageSelect->filter('option');
        $this->assertCount(1, $options);
        $this->assertSame('', $options->first()->attr('value'));

        // Render continued past the language select to the voice select, proving
        // no TypeError was thrown.
        $this->assertCount(1, $crawler->filter('#beyondwords_voice'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function voice_model_variants()
    {
        $bridget1 = ['id' => 9001, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_flash_v2_5'];
        $bridget2 = ['id' => 9002, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_multilingual_v2'];
        $bridget3 = ['id' => 9003, 'name' => 'Bridget', 'service' => 'ElevenLabs', 'model_id' => 'eleven_v3'];
        $ada      = ['id' => 3555, 'name' => 'Ada (Multilingual)'];

        $voices = [$bridget1, $bridget2, $bridget3, $ada];

        // Non-ElevenLabs voices have no variants — they stand alone.
        $this->assertSame([$ada], SelectVoice::voice_model_variants($ada, $voices));

        // ElevenLabs variants are grouped by name, default model first.
        $variants = SelectVoice::voice_model_variants($bridget1, $voices);
        $this->assertSame(
            ['eleven_multilingual_v2', 'eleven_flash_v2_5', 'eleven_v3'],
            array_column($variants, 'model_id')
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
