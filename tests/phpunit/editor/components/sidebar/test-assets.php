<?php

use BeyondWords\Editor\Components\Sidebar\Assets;

class SidebarAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-sidebar');
        wp_deregister_style('beyondwords-sidebar');

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
