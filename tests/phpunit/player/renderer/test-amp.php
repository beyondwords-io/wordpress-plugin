<?php

use BeyondWords\Core\Urls;
use BeyondWords\Player\Renderer\Amp;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Test the Amp player renderer.
 *
 * Amp::check() isn't tested here — amp_is_request() can't be mocked in this
 * environment; integration tests with the AMP plugin active cover it.
 */
class AmpTest extends TestCase
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
    public function render()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'AmpTest::render',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $src = "https://audio.beyondwords.io/amp/" . BEYONDWORDS_TESTS_PROJECT_ID . "?podcast_id=" . BEYONDWORDS_TESTS_CONTENT_ID;

        $html = Amp::render($post);

        $crawler = new Crawler($html);

        $iframe = $crawler->filter('amp-iframe');
        $this->assertCount(1, $iframe);
        $this->assertSame('0', $iframe->attr('frameborder'));
        $this->assertSame('43', $iframe->attr('height'));
        $this->assertSame('responsive', $iframe->attr('layout'));
        $this->assertSame('allow-scripts allow-same-origin allow-popups', $iframe->attr('sandbox'));
        $this->assertSame('no', $iframe->attr('scrolling'));
        $this->assertSame($src, $iframe->attr('src'));
        $this->assertSame('295', $iframe->attr('width'));

        $img = $iframe->filter('amp-img');
        $this->assertCount(1, $img);
        $this->assertSame('150', $img->attr('height'));
        $this->assertSame('responsive', $img->attr('layout'));
        $this->assertSame('', $img->attr('placeholder'));
        $this->assertSame(Urls::get_amp_img_url(), $img->attr('src'));
        $this->assertSame('643', $img->attr('width'));

        wp_delete_post($post->ID, true);
    }
}