<?php

use Beyondwords\Wordpress\Component\Post\Panel\Inspect\Inspect;
use \Symfony\Component\DomCrawler\Crawler;

class InspectTest extends \WP_UnitTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    /**
     * @var \Beyondwords\Wordpress\Component\Post\Inspect\Inspect
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        set_current_screen('index.php');

        global $wp_meta_boxes;
        $wp_meta_boxes = null;

        $this->_instance = new Inspect();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = NULL;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addMetaBox()
    {
        global $wp_meta_boxes;

        $this->_instance->addMetaBox('post');

        $this->assertArrayHasKey('beyondwords__inspect', $wp_meta_boxes['post']['advanced']['low']);

        $wp_meta_boxes = null;
    }

    /**
     * @test
     */
    public function renderMetaBoxContent()
    {
        $postMeta = [
            'beyondwords_project_id'       => BEYONDWORDS_TESTS_PROJECT_ID,
            'beyondwords_content_id'       => BEYONDWORDS_TESTS_CONTENT_ID,
            'beyondwords_podcast_id'       => BEYONDWORDS_TESTS_CONTENT_ID,
            'beyondwords_language_id'      => '42',
            'beyondwords_title_voice_id'   => '101',
            'beyondwords_body_voice_id'    => '202',
            'beyondwords_summary_voice_id' => '303',
            'beyondwords_disabled'         => '0',
            'beyondwords_error_message'    => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'publish_post_to_speechkit'    => 'Value 1',
            'speechkit_info'               => ["foo" => ["bar" => "baz"]],
            'speechkit_response'           => 'Value 7',
            'speechkit_retries'            => '1',
            '_speechkit_link'              => 'https://example.com/foo/bar?baz=1',
            '_speechkit_text'              => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque viverra pellentesque vulputate. Praesent et justo in nibh aliquet dictum. Praesent gravida ipsum sed ante rhoncus maximus. Phasellus quis facilisis nisi. Aenean facilisis sagittis tortor eu dapibus. Maecenas ac venenatis leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed ac vulputate elit. Donec hendrerit augue sed elit dignissim lobortis. Aenean at imperdiet felis. Donec condimentum, sem non placerat maximus, risus lorem facilisis tellus, at accumsan enim metus vitae erat. Nam eu quam semper, facilisis ex eu, dictum augue. Sed euismod sodales risus, nec maximus dolor convallis eu. Nullam laoreet cursus pharetra. Aliquam velit enim, scelerisque id aliquet sed, varius in leo. Mauris aliquet eros eu ex porttitor, in tristique leo pellentesque.</p><p>Nulla neque justo, porta non elit quis, facilisis hendrerit urna. Donec cursus vestibulum est, at consectetur dui venenatis nec. Sed rutrum, massa sed mollis ullamcorper, enim nisi efficitur nibh, sed vehicula urna augue et orci. Aliquam non interdum nisi. Duis vitae orci ut metus lacinia tristique vel ut est. In pharetra blandit urna vel blandit. Nulla varius ultrices elementum. Maecenas iaculis eleifend libero sed ultricies. Nunc odio sem, euismod nec consequat quis, egestas quis dui.</p>',
        ];

        $post = $this->factory->post->create_and_get([
            'post_title' => 'InspectTest::render',
            'post_type' => 'post',
            'meta_input' => $postMeta,
        ]);

        $this->_instance->renderMetaBoxContent($post);

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        // Get post meta including meta_ids, needed below
        $meta = has_meta($post->ID);

        foreach ($meta as $data) {
            if (! array_key_exists($data['meta_key'], $postMeta)) {
                continue;
            }

            // Remove from post meta array so we can check they are all output later
            unset($postMeta[$data['meta_key']]);

            $nameInput = $crawler->filter('input#beyondwords-inspect-'.$data['meta_id'].'-key');

            $this->assertCount(1, $nameInput);
            $this->assertEquals($data['meta_key'], $nameInput->attr('value'));

            $valueTextarea = $crawler->filter('textarea#beyondwords-inspect-'.$data['meta_id'].'-value');

            if ($data['meta_key'] === 'post_id') {
                // Special case for WordPress Post ID
                $expect = "$post->ID";
            } else {
                $expect = get_post_meta($post->ID, $data['meta_key'], true);
            }

            $this->assertCount(1, $valueTextarea);
            $this->assertEquals($expect, html_entity_decode($valueTextarea->html()));
        }

        // Assert all post meta has been output
        $this->assertEmpty($postMeta);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function saveWithRemove()
    {
        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = $this->factory->post->create([
            'post_title' => 'InspectTest::save',
            'meta_input' => [
                'beyondwords_project_id'       => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id'       => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_error_message'    => 'An error message',
            ],
        ]);

        $_POST['beyondwords_delete_content_nonce'] = wp_create_nonce('beyondwords_delete_content');
        $_POST['beyondwords_delete_content'] = '1';

        $this->_instance->save($postId);

        unset($_POST['beyondwords_delete_content']);

        $this->assertSame('1', get_post_meta($postId, 'beyondwords_delete_content', true));

        wp_delete_post($postId, true);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }
}