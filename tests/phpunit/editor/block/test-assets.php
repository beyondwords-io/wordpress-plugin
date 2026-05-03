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
    public function enqueue_block_editor_assets_skips_for_incompatible_post_type()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'AssetsTest::enqueueBlockEditorAssetsSkipsIncompatible',
            'post_type'  => 'attachment',
        ]);

        setup_postdata($post);

        Assets::enqueue_block_editor_assets();
        $this->assertFalse(wp_script_is('beyondwords-block-js', 'enqueued'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function enqueue_block_editor_assets_enqueues_for_compatible_post_type()
    {
        global $post;

        $post = self::factory()->post->create_and_get([
            'post_title' => 'AssetsTest::enqueueBlockEditorAssetsEnqueues',
            'post_type'  => 'post',
        ]);

        setup_postdata($post);

        Assets::enqueue_block_editor_assets();
        $this->assertTrue(wp_script_is('beyondwords-block-js', 'enqueued'));

        wp_delete_post($post->ID, true);
    }
}
