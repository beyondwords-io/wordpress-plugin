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

        // save() requires a user who can edit the post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
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
            $_POST['beyondwords_embed'],
            $_POST['beyondwords_embed_touched']
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

        // With no stored value the first asset is selected (player shows) rather than None.
        $this->assertSame(
            'audio_post',
            $crawler->filter('select#beyondwords_embed option[selected]')->attr('value')
        );

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
    public function render_player_section_falls_back_to_default_asset_when_embed_invalid()
    {
        // Embed = video_post is invalid for Post + Audio → falls back to the first
        // produced asset, so the post keeps a player instead of silently losing one.
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::player::invalid',
            'meta_input' => ['beyondwords_embed' => 'video_post'],
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_player_section($post);
        }));

        $this->assertSame(
            'audio_post',
            $crawler->filter('select#beyondwords_embed option[selected]')->attr('value')
        );

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @group integration
     */
    public function render_player_section_outputs_an_untouched_embed_flag()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'SettingsFieldsTest::player::touched',
        ]);

        $crawler = new Crawler($this->capture_output(function () use ($post) {
            SettingsFields::render_player_section($post);
        }));

        // The select always submits a value, so save() reads this flag to tell a
        // real choice from the rendered default. It ships empty; JS sets it on change.
        $flag = $crawler->filter('input#beyondwords_embed_touched');
        $this->assertCount(1, $flag);
        $this->assertSame('hidden', $flag->attr('type'));
        $this->assertSame('beyondwords_embed_touched', $flag->attr('name'));
        $this->assertSame('', $flag->attr('value'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function get_effective_embed_falls_back_to_the_default_asset_when_invalid()
    {
        // Output was changed to Video after audio_post was stored.
        $postId = self::factory()->post->create([
            'post_title' => 'SettingsFieldsTest::effective::invalid',
            'meta_input' => [
                'beyondwords_output' => 'video',
                'beyondwords_embed'  => 'audio_post',
            ],
        ]);

        $this->assertSame(SettingsFields::EMBED_VIDEO_POST, SettingsFields::get_effective_embed($postId));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_effective_embed_keeps_an_explicit_none()
    {
        // None is valid for every Source × Output, so the deliberate opt-out is never
        // re-derived into an asset — this is what separates it from a stale value.
        $postId = self::factory()->post->create([
            'post_title' => 'SettingsFieldsTest::effective::none',
            'meta_input' => [
                'beyondwords_output' => 'video',
                'beyondwords_embed'  => 'none',
            ],
        ]);

        $this->assertSame(SettingsFields::EMBED_NONE, SettingsFields::get_effective_embed($postId));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_effective_embed_resolves_an_unset_value_via_the_legacy_flag()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'SettingsFieldsTest::effective::legacy',
            'meta_input' => ['beyondwords_disabled' => '1'],
        ]);

        // Pre-v7 opt-out with no Embed stored → None.
        $this->assertSame(SettingsFields::EMBED_NONE, SettingsFields::get_effective_embed($postId));

        // An explicit asset outranks the legacy flag.
        update_post_meta($postId, 'beyondwords_embed', SettingsFields::EMBED_AUDIO_POST);
        $this->assertSame(SettingsFields::EMBED_AUDIO_POST, SettingsFields::get_effective_embed($postId));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_effective_embed_defaults_an_unset_value_to_the_first_asset()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'SettingsFieldsTest::effective::unset',
            'meta_input' => ['beyondwords_source' => 'script'],
        ]);

        $this->assertSame(SettingsFields::EMBED_AUDIO_SCRIPT, SettingsFields::get_effective_embed($postId));

        wp_delete_post($postId, true);
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
        $_POST['beyondwords_embed_touched']         = '1';

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

        // Values outside the option set are rejected, leaving the prior value.
        $_POST['beyondwords_source']            = 'not-a-real-source';
        $_POST['beyondwords_video_template_id'] = 'not-numeric';
        SettingsFields::save($postId);
        $this->assertSame('post_and_script', get_post_meta($postId, 'beyondwords_source', true));
        $this->assertSame('3', get_post_meta($postId, 'beyondwords_video_template_id', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function save_only_persists_the_embed_the_user_chose()
    {
        $postId = self::factory()->post->create(['post_title' => 'SettingsFieldsTest::save_embed']);

        $_POST['beyondwords_settings_fields_nonce'] = wp_create_nonce('beyondwords_settings_fields');
        $_POST['beyondwords_output']                = 'video';

        // Untouched: the select still submits the rendered default, but storing it
        // would pin the post to a publish-time value the user never picked.
        $_POST['beyondwords_embed'] = 'video_post';

        SettingsFields::save($postId);

        $this->assertSame('video', get_post_meta($postId, 'beyondwords_output', true));
        $this->assertSame('', get_post_meta($postId, 'beyondwords_embed', true));

        // Touched → persisted.
        $_POST['beyondwords_embed_touched'] = '1';
        $_POST['beyondwords_embed']         = 'none';

        SettingsFields::save($postId);

        $this->assertSame('none', get_post_meta($postId, 'beyondwords_embed', true));

        // An untouched later save leaves the stored choice alone rather than
        // overwriting it with the default asset.
        unset($_POST['beyondwords_embed_touched']);
        $_POST['beyondwords_embed'] = 'video_post';

        SettingsFields::save($postId);

        $this->assertSame('none', get_post_meta($postId, 'beyondwords_embed', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function save_requires_edit_capability()
    {
        $postId = self::factory()->post->create(['post_title' => 'SettingsFieldsTest::save_cap']);

        // A subscriber cannot edit the post, so nothing is written even with a valid nonce.
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $_POST['beyondwords_settings_fields_nonce'] = wp_create_nonce('beyondwords_settings_fields');
        $_POST['beyondwords_source']                = 'script';

        SettingsFields::save($postId);

        $this->assertSame('', get_post_meta($postId, 'beyondwords_source', true));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function default_embed_returns_first_asset()
    {
        $this->assertSame(
            SettingsFields::EMBED_AUDIO_POST,
            SettingsFields::default_embed(SettingsFields::SOURCE_POST, SettingsFields::OUTPUT_AUDIO)
        );

        $this->assertSame(
            SettingsFields::EMBED_VIDEO_POST,
            SettingsFields::default_embed(SettingsFields::SOURCE_POST, SettingsFields::OUTPUT_VIDEO)
        );
    }

    /**
     * @test
     */
    public function is_player_disabled_for_post_prefers_embed_then_legacy_flag()
    {
        $postId = self::factory()->post->create(['post_title' => 'SettingsFieldsTest::disabled']);

        // No embed + no legacy flag → not disabled (player shows by default).
        $this->assertFalse(SettingsFields::is_player_disabled_for_post($postId));

        // Legacy opt-out, no embed → disabled.
        update_post_meta($postId, 'beyondwords_disabled', '1');
        $this->assertTrue(SettingsFields::is_player_disabled_for_post($postId));

        // An explicit non-None embed wins over the legacy flag.
        update_post_meta($postId, 'beyondwords_embed', SettingsFields::EMBED_AUDIO_POST);
        $this->assertFalse(SettingsFields::is_player_disabled_for_post($postId));

        // Embed = None disables.
        update_post_meta($postId, 'beyondwords_embed', SettingsFields::EMBED_NONE);
        $this->assertTrue(SettingsFields::is_player_disabled_for_post($postId));

        wp_delete_post($postId, true);
    }
}
