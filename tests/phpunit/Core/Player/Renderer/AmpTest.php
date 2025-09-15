<?php

use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\Renderer\Amp;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class Amp
 *
 * Renders the AMP-compatible BeyondWords player.
 */
class AmpTest extends WP_UnitTestCase
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
        $this->markTestIncomplete('Unable to mock amp_is_request() function using @runInSeparateProcess and preserveGlobalState disabled.');

        require __DIR__ . '/../../../Stubs/amp_is_request_true.php';

        $this->assertTrue(\amp_is_request());

        $post = self::factory()->post->create_and_get([
            'post_title' => 'Amp::check::1',
        ]);

        $this->assertFalse(Amp::check($post));

        $post = self::factory()->post->create_and_get([
            'post_title' => 'Amp::check::2',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->assertTrue(Amp::check($post));
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

        // <amp-iframe>
        $iframe = $crawler->filter('amp-iframe');
        $this->assertCount(1, $iframe);
        $this->assertSame('0', $iframe->attr('frameborder'));
        $this->assertSame('43', $iframe->attr('height'));
        $this->assertSame('responsive', $iframe->attr('layout'));
        $this->assertSame('allow-scripts allow-same-origin allow-popups', $iframe->attr('sandbox'));
        $this->assertSame('no', $iframe->attr('scrolling'));
        $this->assertSame($src, $iframe->attr('src'));
        $this->assertSame('295', $iframe->attr('width'));

        // <amp-img>
        $img = $iframe->filter('amp-img');
        $this->assertCount(1, $img);
        $this->assertSame('150', $img->attr('height'));
        $this->assertSame('responsive', $img->attr('layout'));
        $this->assertSame('', $img->attr('placeholder'));
        $this->assertSame(Environment::getAmpImgUrl(), $img->attr('src'));
        $this->assertSame('643', $img->attr('width'));

        wp_delete_post($post->ID, true);
    }
}