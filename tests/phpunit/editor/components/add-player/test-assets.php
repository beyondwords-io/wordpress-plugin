<?php

use BeyondWords\Editor\Components\AddPlayer\Assets;

class AddPlayerAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-add-player');
        wp_deregister_style('beyondwords-add-player');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Assets::init();

        $this->assertEquals(10, has_action('enqueue_block_editor_assets', array(Assets::class, 'enqueue_block_editor_assets')));
    }

    /**
     * @test
     */
    public function enqueue_block_editor_assets_enqueues_on_post_screen()
    {
        Assets::enqueue_block_editor_assets('post.php');

        $this->assertTrue(wp_style_is('beyondwords-add-player', 'enqueued'));
    }

    /**
     * @test
     */
    public function enqueue_block_editor_assets_skips_for_unrelated_hook()
    {
        Assets::enqueue_block_editor_assets('plugins.php');

        $this->assertFalse(wp_style_is('beyondwords-add-player', 'enqueued'));
    }
}
