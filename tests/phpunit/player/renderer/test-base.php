<?php

use BeyondWords\Core\Urls;
use BeyondWords\Player\Renderer\Amp;
use BeyondWords\Player\Renderer\Base;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class BaseTest
 */
class BaseTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option('beyondwords_project_id');

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