<?php

use Beyondwords\Wordpress\Component\Post\ContentId\ContentId;
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
    }

    /**
     * @test
     */
    public function elementRendersWithoutContentId()
    {
        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::elementRendersWithoutContentId',
        ]);

        $html = $this->captureOutput(function () use ($post) {
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
    public function elementRendersWithExistingContentId()
    {
        $contentId = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'ContentIdTest::elementRendersWithExistingContentId',
            'meta_input' => [
                'beyondwords_content_id' => $contentId,
            ],
        ]);

        $html = $this->captureOutput(function () use ($post) {
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
    public function saveWithoutNonce()
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
    public function saveWithInvalidNonce()
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
     * @dataProvider saveProvider
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

    public function saveProvider()
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
