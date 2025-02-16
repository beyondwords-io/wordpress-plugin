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
        $selectVoice = new SelectVoice();
        $selectVoice->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('rest_api_init', array($selectVoice, 'restApiInit')));
        $this->assertEquals(10, has_action('admin_enqueue_scripts', array($selectVoice, 'adminEnqueueScripts')));
        $this->assertEquals(10, has_action('save_post_page', array($selectVoice, 'save')));
        $this->assertEquals(10, has_action('save_post_post', array($selectVoice, 'save')));
    }

    /**
     * @test
     */
    public function element()
    {
        update_option('beyondwords_languages', ['1', '2', '3']);

        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostSelectVoiceTest::element',
            'meta_input' => [
                // Set Language ID so we see the "Voice" <select>
                'beyondwords_language_id' => '1',
            ],
        ]);

        $selectVoice = new SelectVoice();

        $selectVoice->element($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $languageLabel = $crawler->filter('p#beyondwords-metabox-select-voice--language-id');
        $this->assertEquals('Language', $languageLabel->text());

        $languageSelect = $crawler->filter('#beyondwords_language_id');
        $this->assertCount(1, $languageSelect);

        $this->assertSame('', $languageSelect->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Project default', $languageSelect->filter('option:nth-child(1)')->text());

        $this->assertSame('1', $languageSelect->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Language 1', $languageSelect->filter('option:nth-child(2)')->text());

        $this->assertSame('2', $languageSelect->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Language 2', $languageSelect->filter('option:nth-child(3)')->text());

        $this->assertSame('3', $languageSelect->filter('option:nth-child(4)')->attr('value'));
        $this->assertSame('Language 3', $languageSelect->filter('option:nth-child(4)')->text());

        $voiceLabel = $crawler->filter('p#beyondwords-metabox-select-voice--voice-id');
        $this->assertEquals('Voice', $voiceLabel->text());

        $voiceSelect = $crawler->filter('#beyondwords_voice_id');
        $this->assertCount(1, $voiceSelect);

        $this->assertSame('1', $voiceSelect->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Voice 1', $voiceSelect->filter('option:nth-child(2)')->text());

        $this->assertSame('2', $voiceSelect->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Voice 2', $voiceSelect->filter('option:nth-child(3)')->text());

        $this->assertSame('3', $voiceSelect->filter('option:nth-child(4)')->attr('value'));
        $this->assertSame('Voice 3', $voiceSelect->filter('option:nth-child(4)')->text());

        wp_delete_post($post->ID, true);

        delete_option('beyondwords_languages');
    }

    /**
     * @test
     */
    public function save()
    {
        $_POST['beyondwords_select_voice_nonce'] = wp_create_nonce('beyondwords_select_voice');

        $selectVoice = new SelectVoice();

        $postId = self::factory()->post->create([
            'post_title' => 'SelectVoiceTest::save',
        ]);

        $selectVoice->save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        $_POST['beyondwords_voice_id'] = '1';

        $selectVoice->save($postId);

        $this->assertEquals('', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        $_POST['beyondwords_language_id'] = '1';
        $_POST['beyondwords_voice_id'] = '1';

        $selectVoice->save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        unset($_POST['beyondwords_voice_id']);

        $selectVoice->save($postId);

        $this->assertEquals('1', get_post_meta($postId, 'beyondwords_body_voice_id', true));

        wp_delete_post($postId, true);
    }
}
