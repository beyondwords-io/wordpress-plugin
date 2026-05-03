<?php

use BeyondWords\Editor\Components\ContentId\Assets;

class ContentIdAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_script('beyondwords-metabox--content-id');
        wp_deregister_script('beyondwords-metabox--content-id');

        global $current_screen;
        $current_screen = null;

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
    public function admin_enqueue_scripts_enqueues_on_classic_post_screen()
    {
        global $current_screen, $post;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        $post = self::factory()->post->create_and_get(['post_type' => 'post']);
        setup_postdata($post);

        Assets::admin_enqueue_scripts('post.php');

        $this->assertTrue(wp_script_is('beyondwords-metabox--content-id', 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function admin_enqueue_scripts_skips_for_unrelated_hook()
    {
        global $current_screen;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        Assets::admin_enqueue_scripts('plugins.php');

        $this->assertFalse(wp_script_is('beyondwords-metabox--content-id', 'enqueued'));
    }
}
