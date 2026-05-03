<?php

use BeyondWords\Post\SelectVoice\Assets;

class SelectVoiceAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_script('beyondwords-metabox--select-voice');
        wp_deregister_script('beyondwords-metabox--select-voice');

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
    public function admin_enqueue_scripts_registers_classic_metabox_script()
    {
        global $current_screen;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor = false;

        Assets::admin_enqueue_scripts('post.php');

        $this->assertTrue(wp_script_is('beyondwords-metabox--select-voice', 'enqueued'));
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

        $this->assertFalse(wp_script_is('beyondwords-metabox--select-voice', 'enqueued'));
        $this->assertFalse(wp_script_is('beyondwords-metabox--select-voice', 'registered'));
    }
}
