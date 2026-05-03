<?php

use BeyondWords\Post\InspectPanel\Assets;

class InspectPanelAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_script('beyondwords-inspect');
        wp_deregister_script('beyondwords-inspect');

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
    public function admin_enqueue_scripts_enqueues_on_post_screen()
    {
        Assets::admin_enqueue_scripts('post.php');

        $this->assertTrue(wp_script_is('beyondwords-inspect', 'enqueued'));
    }

    /**
     * @test
     */
    public function admin_enqueue_scripts_skips_for_unrelated_hook()
    {
        Assets::admin_enqueue_scripts('plugins.php');

        $this->assertFalse(wp_script_is('beyondwords-inspect', 'enqueued'));
    }
}
