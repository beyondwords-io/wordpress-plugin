<?php

use Beyondwords\Wordpress\Component\Post\Sidebar\Sidebar;

class SidebarTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $sidebar = new Sidebar();
        $sidebar->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('enqueue_block_assets', array($sidebar, 'enqueueBlockAssets')));
    }
}
