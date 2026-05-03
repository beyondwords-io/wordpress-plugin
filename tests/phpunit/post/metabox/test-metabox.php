<?php

use BeyondWords\Post\Metabox;
use \Symfony\Component\DomCrawler\Crawler;

class MetaboxTest extends TestCase
{
    /**
     * @var \BeyondWords\Post\Metabox
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.

        global $wp_meta_boxes;
        $wp_meta_boxes = null;
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
        Metabox::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('add_meta_boxes', array(Metabox::class, 'add_meta_box_callback')));
    }

    /**
     * @test
     */
    public function add_meta_box_callback()
    {
        global $wp_meta_boxes;

        Metabox::add_meta_box_callback('post');

        $this->assertArrayHasKey('beyondwords', $wp_meta_boxes['post']['side']['default']);

        $wp_meta_boxes = null;
    }

    /**
     * @test
     * @group integration
     * @dataProvider render_meta_box_content_provider
     */
    public function render_meta_box_content($expectPlayer, $postArgs)
    {
        // Set up API credentials for metabox rendering
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create($postArgs);

        $html = $this->capture_output(function () use ($postId) {
            Metabox::render_meta_box_content($postId);
        });

        $crawler = new Crawler($html);

        // Generate audio checkbox is always rendered.
        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-generate-audio'));

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-help'));
        $this->assertCount(0, $crawler->filter('div#beyondwords-metabox-errors'));

        wp_delete_post($postId, true);

        // Clean up
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    public function render_meta_box_content_provider()
    {
        return [
            'No Post Meta' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::1',
                ],
            ],
            'Empty beyondwords_content_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::2',
                    'meta_input' => ['beyondwords_content_id' => '']
                ],
            ],
            'Empty beyondwords_podcast_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::3',
                    'meta_input' => ['beyondwords_podcast_id' => '']
                ],
            ],
            'beyondwords_content_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::4',
                    'meta_input' => ['beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
            'beyondwords_podcast_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::5',
                    'meta_input' => ['beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
        ];
    }

    /**
     * @test
     * @group integration
     */
    public function render_meta_box_content_with_invalid_post()
    {
        // Set up API credentials
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Pass an invalid post (array instead of WP_Post or int)
        $html = $this->capture_output(function () {
            Metabox::render_meta_box_content(['ID' => BEYONDWORDS_TESTS_PROJECT_ID]);
        });

        $this->assertEmpty($html);

        // Clean up
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     * @dataProvider errors_provider
     */
    public function errors($expect, $postArgs)
    {
        $post = self::factory()->post->create_and_get($postArgs);

        $html = $this->capture_output(function () use ($post) {
            Metabox::errors($post);
        });

        $crawler = new Crawler($html);

        if ($expect) {
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors'));
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error'));
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error > p'));

            $errorText = $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error > p')->text();
            $this->assertSame($errorText, get_post_meta($post->ID, 'beyondwords_error_message', true));
        } else {
            $this->assertSame('', $html);
        }

        wp_delete_post($post->ID, true);
    }

    public function errors_provider()
    {
        return [
            'No errors' => [
                'expect' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::errors::1',
                ],
            ],
            'Error 500' => [
                'expect' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::errors::2',
                    'meta_input' => ['beyondwords_error_message' => '[500] Unknown error.']
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function regenerate_instructions()
    {
        $html = $this->capture_output(function () {
            Metabox::regenerate_instructions();
        });

        $crawler = new Crawler($html);

        $text = 'To create audio, resolve the error above then select ‘Update’ with ‘Generate audio’ checked.';

        $this->assertCount(1, $crawler->filter('p'));
        $this->assertSame($text, $crawler->filter('p')->text());
    }
}
