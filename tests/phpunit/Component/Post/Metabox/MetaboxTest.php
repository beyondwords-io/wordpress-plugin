<?php

use Beyondwords\Wordpress\Component\Post\Metabox\Metabox;
use \Symfony\Component\DomCrawler\Crawler;

class MetaboxTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Post\Metabox\Metabox
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.

        global $wp_meta_boxes;
        $wp_meta_boxes = null;

        $this->_instance = new Metabox();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $this->_instance->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_enqueue_scripts', array($this->_instance, 'adminEnqueueScripts')));
        $this->assertEquals(10, has_action('add_meta_boxes', array($this->_instance, 'addMetaBox')));
    }

    /**
     * @test
     */
    public function adminEnqueueScripts()
    {
        $style = 'beyondwords-Metabox';

        $this->assertFalse(wp_style_is($style, 'enqueued'));

        $this->_instance->adminEnqueueScripts(null);
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        $this->_instance->adminEnqueueScripts('edit.php');
        $this->assertFalse(wp_style_is($style, 'enqueued'));

        $this->_instance->adminEnqueueScripts('post.php');
        $this->assertTrue(wp_style_is($style, 'enqueued'));

        wp_dequeue_style($style);
    }

    /**
     * @test
     */
    public function addMetaBox()
    {
        global $wp_meta_boxes;

        $this->_instance->addMetaBox('post');

        $this->assertArrayHasKey('beyondwords', $wp_meta_boxes['post']['side']['default']);

        $wp_meta_boxes = null;
    }

    /**
     * @test
     * @dataProvider renderMetaBoxContentProvider
     */
    public function renderMetaBoxContent($expectPlayer, $postArgs)
    {
        $this->markTestSkipped('Needs updated after recent language changes.');

        $postId = self::factory()->post->create($postArgs);

        $this->_instance->renderMetaBoxContent($postId);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        if ($expectPlayer) {
            $this->assertCount(0, $crawler->filter('p#beyondwords-metabox-generate-audio'));
        } else {
            $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-generate-audio'));
        }

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-help'));
        $this->assertCount(0, $crawler->filter('div#beyondwords-metabox-errors'));

        wp_delete_post($postId);
    }

    public function renderMetaBoxContentProvider()
    {
        return [
            'No Post Meta' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::renderMetaBoxContent::1',
                ],
            ],
            'Empty beyondwords_content_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::renderMetaBoxContent::2',
                    'meta_input' => ['beyondwords_content_id' => '']
                ],
            ],
            'Empty beyondwords_podcast_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::renderMetaBoxContent::3',
                    'meta_input' => ['beyondwords_podcast_id' => '']
                ],
            ],
            'beyondwords_content_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::renderMetaBoxContent::4',
                    'meta_input' => ['beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
            'beyondwords_podcast_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::renderMetaBoxContent::5',
                    'meta_input' => ['beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function renderMetaBoxContentWithInvalidPost()
    {
        $this->markTestSkipped('Needs updated after recent language changes.');

        $this->_instance->renderMetaBoxContent(['ID' => BEYONDWORDS_TESTS_PROJECT_ID]);

        $html = $this->getActualOutput();

        $this->assertEmpty($html);
    }

    /**
     * @test
     * @dataProvider errorsProvider
     */
    public function errors($expect, $postArgs)
    {
        $post = self::factory()->post->create_and_get($postArgs);

        $this->_instance->errors($post);

        $html = $this->getActualOutput();

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

    public function errorsProvider()
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
    public function regenerateInstructions()
    {
        $this->_instance->regenerateInstructions();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $text = 'To create audio, resolve the error above then select ‘Update’ with ‘Generate audio’ checked.';

        $this->assertCount(1, $crawler->filter('p'));
        $this->assertSame($text, $crawler->filter('p')->text());
    }
}
