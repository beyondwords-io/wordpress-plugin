<?php

use BeyondWords\Editor\Classic\Assets;

class MetaboxAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-metabox');
        wp_deregister_style('beyondwords-metabox');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Assets::init();

        $this->assertEquals(10, has_action('admin_enqueue_scripts', array(Assets::class, 'admin_enqueue_scripts')));
    }

    /**
     * @test
     */
    public function admin_enqueue_scripts_only_runs_on_post_screens()
    {
        global $post;

        $style = 'beyondwords-metabox';

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts(null);
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts('edit.php');
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts('post.php');
        $this->assertTrue(wp_style_is($style, 'enqueued'));

        wp_dequeue_style($style);

        Assets::admin_enqueue_scripts('post-new.php');
        $this->assertTrue(wp_style_is($style, 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function admin_enqueue_scripts_skips_for_incompatible_post_type()
    {
        global $post;

        $post = self::factory()->post->create_and_get(['post_type' => 'attachment']);
        setup_postdata($post);

        Assets::admin_enqueue_scripts('post.php');

        $this->assertFalse(wp_style_is('beyondwords-metabox', 'enqueued'));

        wp_delete_post($post->ID, true);
    }
}
