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

use Beyondwords\Wordpress\Component\Post\SelectVoice\SelectVoice;
use \Symfony\Component\DomCrawler\Crawler;

class SelectVoiceTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

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

        $this->assertEquals(10, has_action('rest_api_init', array(SelectVoice::class, 'restApiInit')));
        $this->assertEquals(10, has_action('admin_enqueue_scripts', array(SelectVoice::class, 'adminEnqueueScripts')));
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

        SelectVoice::element($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $languageLabel = $crawler->filter('p#beyondwords-metabox-select-voice--language-code');
        $this->assertEquals('Language', $languageLabel->text());

        $languageSelect = $crawler->filter('#beyondwords_language_code');
        $this->assertCount(1, $languageSelect);

        $this->assertSame('en_US', $languageSelect->filter('option:nth-child(33)')->attr('value'));
        $this->assertSame('English (American)', $languageSelect->filter('option:nth-child(33)')->text());

        $this->assertSame('en_GB', $languageSelect->filter('option:nth-child(35)')->attr('value'));
        $this->assertSame('English (British)', $languageSelect->filter('option:nth-child(35)')->text());

        $this->assertSame('cy_GB', $languageSelect->filter('option:nth-child(92)')->attr('value'));
        $this->assertSame('Welsh (Welsh)', $languageSelect->filter('option:nth-child(92)')->text());

        $voiceLabel = $crawler->filter('p#beyondwords-metabox-select-voice--voice-id');
        $this->assertEquals('Voice', $voiceLabel->text());

        $voiceSelect = $crawler->filter('#beyondwords_voice_id');
        $this->assertCount(1, $voiceSelect);

        $this->assertSame('3555', $voiceSelect->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Ada (Multilingual)', $voiceSelect->filter('option:nth-child(1)')->text());

        $this->assertSame('2517', $voiceSelect->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Ava (Multilingual)', $voiceSelect->filter('option:nth-child(2)')->text());

        $this->assertSame('3558', $voiceSelect->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Ollie (Multilingual)', $voiceSelect->filter('option:nth-child(3)')->text());

        wp_delete_post($post->ID, true);
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
}
