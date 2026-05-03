<?php

use BeyondWords\Editor\Components\ErrorNotice\Assets;

class ErrorNoticeAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-error-notice');
        wp_deregister_style('beyondwords-error-notice');

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

    /**
     * @test
     */
    public function enqueue_block_assets_does_nothing_on_non_gutenberg_page()
    {
        global $current_screen;
        $current_screen = \WP_Screen::get('edit-post');
        $current_screen->is_block_editor(false);

        Assets::enqueue_block_assets();

        $this->assertFalse(
            wp_style_is('beyondwords-error-notice', 'enqueued'),
            'Should not enqueue styles on non-Gutenberg pages'
        );
    }

    /**
     * @test
     */
    public function enqueue_block_assets_enqueues_style_on_gutenberg_page()
    {
        global $current_screen;
        $current_screen = \WP_Screen::get('post');
        $current_screen->is_block_editor(true);

        Assets::enqueue_block_assets();

        $this->assertTrue(
            wp_style_is('beyondwords-error-notice', 'registered') || wp_style_is('beyondwords-error-notice', 'enqueued'),
            'Should register error notice style on Gutenberg pages'
        );
    }
}
