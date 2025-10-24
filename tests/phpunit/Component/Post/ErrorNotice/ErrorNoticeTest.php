<?php

use Beyondwords\Wordpress\Component\Post\ErrorNotice\ErrorNotice;

class ErrorNoticeTest extends TestCase
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
        ErrorNotice::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('enqueue_block_assets', array(ErrorNotice::class, 'enqueueBlockAssets')));
    }

    /**
     * @test
     */
    public function enqueueBlockAssets_does_nothing_on_non_gutenberg_page()
    {
        // Simulate non-Gutenberg page (admin but not editor)
        set_current_screen('edit-post');

        ErrorNotice::enqueueBlockAssets();

        $this->assertFalse(
            wp_style_is('beyondwords-ErrorNotice', 'enqueued'),
            'Should not enqueue styles on non-Gutenberg pages'
        );
    }

    /**
     * @test
     */
    public function enqueueBlockAssets_enqueues_style_on_gutenberg_page()
    {
        // Simulate Gutenberg page
        global $current_screen;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor(true);

        ErrorNotice::enqueueBlockAssets();

        // The style should be registered/enqueued
        $this->assertTrue(
            wp_style_is('beyondwords-ErrorNotice', 'registered') || wp_style_is('beyondwords-ErrorNotice', 'enqueued'),
            'Should register error notice style on Gutenberg pages'
        );
    }
}
