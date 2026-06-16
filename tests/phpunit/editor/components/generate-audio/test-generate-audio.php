<?php

use BeyondWords\Editor\Components\GenerateAudio;

class GenerateAudioTest extends TestCase
{
    /**
     * @var \BeyondWords\Editor\Components\GenerateAudio
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        unset($_POST, $_REQUEST);

        // save() requires a user who can edit the post.
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_REQUEST);

        wp_dequeue_script('beyondwords-metabox--generate-audio');
        wp_deregister_script('beyondwords-metabox--generate-audio');

        global $current_screen;
        $current_screen = null;

        delete_option('beyondwords_preselect');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        GenerateAudio::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('save_post_post', array(GenerateAudio::class, 'save')));
        $this->assertEquals(10, has_action('save_post_page', array(GenerateAudio::class, 'save')));
        $this->assertEquals(10, has_action('admin_enqueue_scripts', array(GenerateAudio::class, 'admin_enqueue_scripts')));
    }

    /**
     * The classic term-gating script is enqueued (and localized) only for
     * post types configured with 'terms' mode.
     *
     * @test
     */
    public function admin_enqueue_scripts_enqueues_for_terms_mode()
    {
        global $current_screen, $post;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        update_option('beyondwords_preselect', [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [1]]],
        ]);

        GenerateAudio::admin_enqueue_scripts('post.php');

        $this->assertTrue(wp_script_is('beyondwords-metabox--generate-audio', 'enqueued'));

        // The localized payload carries the mode + resolved selected terms.
        $data = wp_scripts()->get_data('beyondwords-metabox--generate-audio', 'data');
        $this->assertStringContainsString('"mode":"terms"', $data);
        $this->assertStringContainsString('"category":[1]', $data);

        wp_delete_post($post->ID, true);
    }

    /**
     * 'all' mode needs no live JS — the server renders the initial state.
     *
     * @test
     */
    public function admin_enqueue_scripts_skips_for_all_mode()
    {
        global $current_screen, $post;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        update_option('beyondwords_preselect', ['post' => ['mode' => 'all']]);

        GenerateAudio::admin_enqueue_scripts('post.php');

        $this->assertFalse(wp_script_is('beyondwords-metabox--generate-audio', 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * The block editor handles preselect in React, so the classic script is
     * never enqueued on a Gutenberg screen.
     *
     * @test
     */
    public function admin_enqueue_scripts_skips_in_block_editor()
    {
        global $current_screen, $post;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = true;

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        update_option('beyondwords_preselect', [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [1]]],
        ]);

        GenerateAudio::admin_enqueue_scripts('post.php');

        $this->assertFalse(wp_script_is('beyondwords-metabox--generate-audio', 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * 'terms' mode with no usable terms has nothing to watch — skip the script.
     *
     * @test
     */
    public function admin_enqueue_scripts_skips_when_terms_empty()
    {
        global $current_screen, $post;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        update_option('beyondwords_preselect', ['post' => ['mode' => 'terms', 'terms' => []]]);

        GenerateAudio::admin_enqueue_scripts('post.php');

        $this->assertFalse(wp_script_is('beyondwords-metabox--generate-audio', 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save_without_nonce()
    {
        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::saveWithoutNonce',
            'post_content' => '<p>The body.</p>',
        ]);

        $resultId = GenerateAudio::save($post->ID);

        // Check the post object has not changed
        $this->assertSame(json_encode($post), wp_json_encode(get_post($resultId)));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save_with_invalid_nonce()
    {
        $_POST['beyondwords_generate_audio_nonce'] = 'foo';

        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::saveWithInvalidNonce',
            'post_content' => '<p>The body.</p>',
        ]);

        $resultId = GenerateAudio::save($post->ID);

        // Check the post object has not changed
        $this->assertSame(json_encode($post), wp_json_encode(get_post($resultId)));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @dataProvider save_provider
     */
    public function save($postKey, $postValue, $expect)
    {
        $_POST['beyondwords_generate_audio_nonce'] = wp_create_nonce('beyondwords_generate_audio');

        if ($postKey) {
            $_POST[$postKey] = $postValue;
        }

        $postId = self::factory()->post->create([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::save',
            'post_content' => '<p>The body.</p>',
        ]);

        GenerateAudio::save($postId);

        $this->assertSame($expect, get_post_meta($postId, 'beyondwords_generate_audio', true));

        wp_delete_post($postId, true);
    }

    public function save_provider()
    {
        return [
            'Empty POST vars' => [
                'postKey'   => '',
                'postValue' => '',
                'expect'    => '0',
            ],
            'Some other POST var' => [
                'postKey'   => 'foo',
                'postValue' => 'bar',
                'expect'    => '0',
            ],
            'Integer' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => 1,
                'expect'    => '1',
            ],
            'String' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => '1',
                'expect'    => '1',
            ],
            '<script>' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => 'foo<script></script>bar',
                'expect'    => '1',
            ]
        ];
    }

    /**
     * Delegates to Preselect::should_preselect_for_post, which now honours
     * both whole-post-type ('all') and term-gated ('terms') preselection.
     *
     * @test
     */
    public function should_preselect_generate_audio()
    {
        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        $page = self::factory()->post->create_and_get(['post_type' => 'page']);
        wp_set_post_terms($post->ID, [], 'category');

        $this->assertFalse(GenerateAudio::should_preselect_generate_audio(null));

        // Whole post type — legacy '1' reads as 'all'.
        update_option('beyondwords_preselect', ['post' => '1']);
        $this->assertTrue(GenerateAudio::should_preselect_generate_audio($post));
        $this->assertFalse(GenerateAudio::should_preselect_generate_audio($page));

        // New 'all' mode for a different post type.
        update_option('beyondwords_preselect', ['page' => ['mode' => 'all']]);
        $this->assertFalse(GenerateAudio::should_preselect_generate_audio($post));
        $this->assertTrue(GenerateAudio::should_preselect_generate_audio($page));

        // Term-gated: preselect only when the post has a listed term.
        $news = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'News']);
        update_option('beyondwords_preselect', [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [$news]]],
        ]);

        // Post has no matching term yet.
        $this->assertFalse(GenerateAudio::should_preselect_generate_audio($post));

        // Assign the term → now it preselects.
        wp_set_post_terms($post->ID, [$news], 'category');
        $this->assertTrue(GenerateAudio::should_preselect_generate_audio($post));

        delete_option('beyondwords_preselect');

        wp_delete_post($post->ID, true);
        wp_delete_post($page->ID, true);
    }

    /**
     * The classic metabox checkbox is checked when preselect matches and no
     * explicit meta is stored — via Meta::has_generate_audio.
     *
     * @test
     */
    public function element_checkbox_checked_when_preselect_matches()
    {
        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        update_option('beyondwords_preselect', ['post' => ['mode' => 'all']]);

        $html = $this->capture_output(function () use ($post) {
            GenerateAudio::element($post);
        });

        $this->assertMatchesRegularExpression(
            '/id="beyondwords_generate_audio"[^>]*checked/s',
            $html
        );

        delete_option('beyondwords_preselect');
        wp_delete_post($post->ID, true);
    }

    /**
     * The classic metabox checkbox is unchecked when preselect is off and no
     * explicit meta is stored.
     *
     * @test
     */
    public function element_checkbox_unchecked_when_preselect_off()
    {
        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        update_option('beyondwords_preselect', []);

        $html = $this->capture_output(function () use ($post) {
            GenerateAudio::element($post);
        });

        $this->assertDoesNotMatchRegularExpression(
            '/id="beyondwords_generate_audio"[^>]*checked/s',
            $html
        );

        delete_option('beyondwords_preselect');
        wp_delete_post($post->ID, true);
    }
}
