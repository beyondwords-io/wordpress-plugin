<?php

use BeyondWords\Post\Sidebar\Assets;

class SidebarAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-Sidebar');
        wp_deregister_style('beyondwords-Sidebar');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Assets::init();

        $this->assertEquals(10, has_action('enqueue_block_assets', array(Assets::class, 'enqueue_block_assets')));
    }
}
