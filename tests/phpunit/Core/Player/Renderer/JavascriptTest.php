<?php

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Renderer\Javascript;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class Javascript.
 *
 * Responsible for rendering the JavaScript BeyondWords player.
 */
class JavascriptTest extends WP_UnitTestCase
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
    public function render()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::render',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        // setup_postdata($post);

        $html = Javascript::render($post);

        $crawler = new Crawler($html);

        $script = $crawler->filter('script[async][defer]');
        $this->assertCount(1, $script);
        $this->assertSame(Environment::getJsSdkUrl(), $script->attr('src'));
        $this->assertNotEmpty($script->attr('onload'));

        // wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }
}
