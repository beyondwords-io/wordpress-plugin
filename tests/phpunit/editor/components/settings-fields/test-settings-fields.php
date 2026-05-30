<?php

/**
 * BeyondWords Settings Fields (Classic editor) tests.
 *
 * @package Beyondwords\Wordpress
 * @since   7.0.0
 */

use BeyondWords\Editor\Components\SettingsFields;
use \Symfony\Component\DomCrawler\Crawler;

class SettingsFieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        unset(
            $_POST['beyondwords_settings_fields_nonce'],
            $_POST['beyondwords_source'],
            $_POST['beyondwords_script_template_id'],
            $_POST['beyondwords_output'],
            $_POST['beyondwords_video_template_id'],
            $_POST['beyondwords_video_size'],
            $_POST['beyondwords_embed']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        SettingsFields::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('save_post_post', array(SettingsFields::class, 'save')));
        $this->assertEquals(10, has_action('save_post_page', array(SettingsFields::class, 'save')));
    }

    /**
     * @test
     */
    public function nonce_outputs_nonce_field()
    {
        $html = $this->capture_output(function () {
            SettingsFields::nonce();
        });

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('#beyondwords_settings_fields_nonce'));
    }

    /**
     * @test
     */
    public function source_and_output_options()
    {
        $this->assertSame(
            ['post', 'script', 'post_and_script'],
            array_column(SettingsFields::source_options(), 'value')
        );

        $this->assertSame(
            ['audio', 'video', 'audio_and_video'],
            array_column(SettingsFields::output_options(), 'value')
        );
    }

    /**
     * @test
     */
    public function source_and_output_predicates()
    {
        $this->assertTrue(SettingsFields::source_includes_post('post'));
        $this->assertTrue(SettingsFields::source_includes_post('post_and_script'));
        $this->assertFalse(SettingsFields::source_includes_post('script'));

        $this->assertTrue(SettingsFields::source_includes_script('script'));
        $this->assertTrue(SettingsFields::source_includes_script('post_and_script'));
        $this->assertFalse(SettingsFields::source_includes_script('post'));

        $this->assertTrue(SettingsFields::output_includes_audio('audio'));
        $this->assertTrue(SettingsFields::output_includes_audio('audio_and_video'));
        $this->assertFalse(SettingsFields::output_includes_audio('video'));

        $this->assertTrue(SettingsFields::output_includes_video('video'));
        $this->assertTrue(SettingsFields::output_includes_video('audio_and_video'));
        $this->assertFalse(SettingsFields::output_includes_video('audio'));
    }

    /**
     * @test
     * @dataProvider embed_options_provider
     */
    public function embed_options($source, $output, $expected)
    {
        $this->assertSame(
            $expected,
            array_column(SettingsFields::embed_options($source, $output), 'value')
        );

        // Every derived value is valid; None is always valid.
        foreach ($expected as $value) {
            $this->assertTrue(SettingsFields::is_embed_valid($value, $source, $output));
        }
        $this->assertTrue(SettingsFields::is_embed_valid('none', $source, $output));
    }

    public function embed_options_provider()
    {
        return [
            'Post + Audio'                => ['post', 'audio', ['none', 'audio_post']],
            'Script + Audio'              => ['script', 'audio', ['none', 'audio_script']],
            'Post+script + Audio'         => ['post_and_script', 'audio', ['none', 'audio_post', 'audio_script']],
            'Post + Video'                => ['post', 'video', ['none', 'video_post']],
            'Post + Audio+video'          => ['post', 'audio_and_video', ['none', 'audio_post', 'video_post']],
            'Post+script + Audio+video'   => [
                'post_and_script',
                'audio_and_video',
                ['none', 'audio_post', 'audio_script', 'video_post', 'video_script'],
            ],
        ];
    }

    /**
     * @test
     */
    public function is_embed_valid_rejects_unavailable_value()
    {
        // Video (post) is not offered while Output = Audio.
        $this->assertFalse(SettingsFields::is_embed_valid('video_post', 'post', 'audio'));
        // Audio (script) is not offered while Source = Post.
        $this->assertFalse(SettingsFields::is_embed_valid('audio_script', 'post', 'audio'));
    }

    /**
     * @test
     * @group integration
     */
    public function render_content_section_hides_script_template_for_post()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::content::post',
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_content_section($post);
        }));

        $this->assertCount(1, $crawler->filter('select#beyondwords_source'));
        $this->assertCount(3, $crawler->filter('select#beyondwords_source option'));
        $this->assertStringContainsString(
            'display: none',
            (string) $crawler->filter('#beyondwords-metabox-settings--beyondwords-script-template-id')->attr('style')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_content_section_shows_script_template_for_script()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::content::script',
            'meta_input' => ['beyondwords_source' => 'script'],
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_content_section($post);
        }));

        $wrapper = $crawler->filter('#beyondwords-metabox-settings--beyondwords-script-template-id');
        $this->assertStringNotContainsString('display: none', (string) $wrapper->attr('style'));

        $options = $crawler->filter('select#beyondwords_script_template_id option');
        $this->assertSame('Project default', $options->eq(0)->text());
        $this->assertGreaterThan(1, $options->count());

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_format_section_hides_video_fields_for_audio()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::format::audio',
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_format_section($post);
        }));

        $this->assertCount(3, $crawler->filter('select#beyondwords_output option'));
        $this->assertStringContainsString(
            'display: none',
            (string) $crawler->filter('#beyondwords-metabox-settings--beyondwords-video-template-id')->attr('style')
        );
        $this->assertStringContainsString(
            'display: none',
            (string) $crawler->filter('#beyondwords-metabox-settings--beyondwords-video-size')->attr('style')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_format_section_shows_video_fields_for_video()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::format::video',
            'meta_input' => ['beyondwords_output' => 'video'],
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_format_section($post);
        }));

        $this->assertStringNotContainsString(
            'display: none',
            (string) $crawler->filter('#beyondwords-metabox-settings--beyondwords-video-template-id')->attr('style')
        );

        $sizeOptions = $crawler->filter('select#beyondwords_video_size option');
        $this->assertSame('Project default', $sizeOptions->eq(0)->text());
        // Mock project sizes include "landscape (16:9)".
        $this->assertStringContainsString('landscape', $crawler->filter('select#beyondwords_video_size')->text());

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_player_section_default_post_audio()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::player::default',
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_player_section($post);
        }));

        $labels = $crawler->filter('select#beyondwords_embed option')->each(fn ($node) => $node->text());
        $this->assertSame(['None', 'Audio (post)'], $labels);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_player_section_full_source_output()
    {
        // Post + script × Audio + video → all four assets offered.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::player::full',
            'meta_input' => [
                'beyondwords_source' => 'post_and_script',
                'beyondwords_output' => 'audio_and_video',
                'beyondwords_embed'  => 'video_script',
            ],
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_player_section($post);
        }));

        $labels = $crawler->filter('select#beyondwords_embed option')->each(fn ($node) => $node->text());
        $this->assertSame(
            ['None', 'Audio (post)', 'Audio (script)', 'Video (post)', 'Video (script)'],
            $labels
        );

        // The persisted value is still valid, so it stays selected.
        $this->assertSame(
            'video_script',
            $crawler->filter('select#beyondwords_embed option[selected]')->attr('value')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_player_section_falls_back_to_none_when_embed_invalid()
    {
        // Embed = video_post is invalid for Post + Audio → falls back to None.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::player::invalid',
            'meta_input' => ['beyondwords_embed' => 'video_post'],
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_player_section($post);
        }));

        $this->assertSame(
            'none',
            $crawler->filter('select#beyondwords_embed option[selected]')->attr('value')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save()
    {
        $postId = self::factory()->post->create(['post_title' => 'SettingsFieldsTest::save']);

        // No nonce → nothing saved.
        $_POST['beyondwords_source'] = 'script';
        SettingsFields::save($postId);
        $this->assertSame('', get_post_meta($postId, 'beyondwords_source', true));

        // Valid nonce → values persisted.
        $_POST['beyondwords_settings_fields_nonce'] = wp_create_nonce('beyondwords_settings_fields');
        $_POST['beyondwords_source']                = 'post_and_script';
        $_POST['beyondwords_script_template_id']    = '2';
        $_POST['beyondwords_output']                = 'audio_and_video';
        $_POST['beyondwords_video_template_id']     = '3';
        $_POST['beyondwords_video_size']            = 'landscape';
        $_POST['beyondwords_embed']                 = 'audio_post';

        SettingsFields::save($postId);

        $this->assertSame('post_and_script', get_post_meta($postId, 'beyondwords_source', true));
        $this->assertSame('2', get_post_meta($postId, 'beyondwords_script_template_id', true));
        $this->assertSame('audio_and_video', get_post_meta($postId, 'beyondwords_output', true));
        $this->assertSame('3', get_post_meta($postId, 'beyondwords_video_template_id', true));
        $this->assertSame('landscape', get_post_meta($postId, 'beyondwords_video_size', true));
        $this->assertSame('audio_post', get_post_meta($postId, 'beyondwords_embed', true));

        // Empty value → meta deleted (defer to Project default).
        $_POST['beyondwords_script_template_id'] = '';
        SettingsFields::save($postId);
        $this->assertSame('', get_post_meta($postId, 'beyondwords_script_template_id', true));

        wp_delete_post($postId, true);
    }
}
