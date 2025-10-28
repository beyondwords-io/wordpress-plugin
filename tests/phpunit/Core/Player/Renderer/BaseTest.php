<?php

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Renderer\Amp;
use Beyondwords\Wordpress\Core\Player\Renderer\Base;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class Amp
 *
 * Renders the AMP-compatible BeyondWords player.
 */
class BaseTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_project_id');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function check()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'Base::check::1',
        ]);

        $this->assertFalse(Base::check($post));

        $post = self::factory()->post->create_and_get([
            'post_title' => 'Base::check::2',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertTrue(Base::check($post));
    }
}