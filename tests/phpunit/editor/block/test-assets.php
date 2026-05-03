<?php

declare(strict_types=1);

use BeyondWords\Editor\Block\Assets;

class AssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_script('beyondwords-block-js');
        wp_deregister_script('beyondwords-block-js');
        delete_option('beyondwords_valid_api_connection');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Assets::init();

        $this->assertEquals(1, has_action('enqueue_block_editor_assets', array(Assets::class, 'enqueue_block_editor_assets')));
    }

    /**
     * @test
     */
    public function enqueue_block_editor_assets()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'EditorTest::enqueueBlockEditorAssets',
            'post_type'  => 'post',
        ]);

        setup_postdata($post);

        set_current_screen('edit-post');
        $current_screen = get_current_screen();
        $current_screen->is_block_editor(true);

        // Script should not be enqueued without a valid API connection
        Assets::enqueue_block_editor_assets();
        $this->assertFalse(wp_script_is('beyondwords-block-js', 'enqueued'));

        // Set a valid API connection
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        // Script should now be enqueued
        Assets::enqueue_block_editor_assets();
        $this->assertTrue(wp_script_is('beyondwords-block-js', 'enqueued'));

        wp_delete_post($post->ID, true);
    }
}
