<?php

use BeyondWords\Post\Metabox\Assets;

class MetaboxAssetsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        wp_dequeue_style('beyondwords-Metabox');
        wp_deregister_style('beyondwords-Metabox');

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
    public function admin_enqueue_scripts_only_runs_on_post_screens()
    {
        $style = 'beyondwords-Metabox';

        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts(null);
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts('edit.php');
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        Assets::admin_enqueue_scripts('post.php');
        $this->assertTrue(wp_style_is($style, 'enqueued'));

        wp_dequeue_style($style);

        Assets::admin_enqueue_scripts('post-new.php');
        $this->assertTrue(wp_style_is($style, 'enqueued'));
    }
}
