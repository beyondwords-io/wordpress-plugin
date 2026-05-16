<?php

use BeyondWords\Editor\Components\ContentId;
use Symfony\Component\DomCrawler\Crawler;

class ContentIdTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        unset($_POST, $_REQUEST);
    }

    public function tearDown(): void
    {
        unset($_POST, $_REQUEST);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        ContentId::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('save_post_post', array(ContentId::class, 'save')));
        $this->assertEquals(10, has_action('save_post_page', array(ContentId::class, 'save')));
        $this->assertFalse(has_action('admin_enqueue_scripts', array(ContentId::class, 'admin_enqueue_scripts_callback')));
    }

    /**
     * @test
     */
    public function element_renders_without_content_id()
    {
        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::elementRendersWithoutContentId',
        ]);

        $html = $this->capture_output(function () use ($post) {
            ContentId::element($post);
        });

        $crawler = new Crawler($html);

        // Nonce field
        $this->assertCount(1, $crawler->filter('#beyondwords_content_id_nonce'));

        // Text input with empty value
        $input = $crawler->filter('#beyondwords_content_id');
        $this->assertCount(1, $input);
        $this->assertSame('', $input->attr('value'));
        $this->assertSame('beyondwords_content_id', $input->attr('name'));

        // Fetch button
        $button = $crawler->filter('#beyondwords__content-id--fetch');
        $this->assertCount(1, $button);
        $this->assertStringContainsString('Fetch', $button->text());

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function element_renders_with_existing_content_id()
    {
        $contentId = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::elementRendersWithExistingContentId',
            'meta_input' => [
                'beyondwords_content_id' => $contentId,
            ],
        ]);

        $html = $this->capture_output(function () use ($post) {
            ContentId::element($post);
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('#beyondwords_content_id');
        $this->assertSame($contentId, $input->attr('value'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save_without_nonce()
    {
        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::saveWithoutNonce',
        ]);

        $resultId = ContentId::save($post->ID);

        $this->assertFalse(metadata_exists('post', $post->ID, 'beyondwords_content_id'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function save_with_invalid_nonce()
    {
        $_POST['beyondwords_content_id_nonce'] = 'invalid';

        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::saveWithInvalidNonce',
        ]);

        $resultId = ContentId::save($post->ID);

        $this->assertFalse(metadata_exists('post', $post->ID, 'beyondwords_content_id'));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @dataProvider save_provider
     */
    public function save($postValue, $expect)
    {
        $_POST['beyondwords_content_id_nonce'] = wp_create_nonce('beyondwords_content_id');
        $_POST['beyondwords_content_id'] = $postValue;

        $postId = self::factory()->post->create([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::save',
        ]);

        ContentId::save($postId);

        $this->assertSame($expect, get_post_meta($postId, 'beyondwords_content_id', true));

        wp_delete_post($postId, true);
    }

    public function save_provider()
    {
        return [
            'UUID content ID' => [
                'postValue' => '9279c9e0-e0b5-4789-9040-f44478ed3e9e',
                'expect'    => '9279c9e0-e0b5-4789-9040-f44478ed3e9e',
            ],
            'Empty content ID' => [
                'postValue' => '',
                'expect'    => '',
            ],
            'Script injection' => [
                'postValue' => '<script>alert("xss")</script>',
                'expect'    => '',
            ],
            'HTML tags stripped' => [
                'postValue' => 'abc<b>def</b>ghi',
                'expect'    => 'abcdefghi',
            ],
        ];
    }

}
