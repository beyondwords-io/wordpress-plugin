<?php

use Beyondwords\Wordpress\Component\Post\GenerateAudio\GenerateAudio;

class GenerateAudioTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Post\GenerateAudio\GenerateAudio
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        unset($_POST, $_REQUEST);

        // Your set up methods here.
        $this->_instance = new GenerateAudio();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_REQUEST);

        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function saveWithoutNonce()
    {
        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::saveWithoutNonce',
            'post_content' => '<p>The body.</p>',
        ]);

        $resultId = $this->_instance->save($post->ID);

        // Check the post object has not changed
        $this->assertSame(json_encode($post), wp_json_encode(get_post($resultId)));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function saveWithInvalidNonce()
    {
        $_POST['beyondwords_generate_audio_nonce'] = 'foo';

        $post = self::factory()->post->create_and_get([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::saveWithInvalidNonce',
            'post_content' => '<p>The body.</p>',
        ]);

        $resultId = $this->_instance->save($post->ID);

        // Check the post object has not changed
        $this->assertSame(json_encode($post), wp_json_encode(get_post($resultId)));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @dataProvider saveProvider
     */
    public function save($postKey, $postValue, $expect)
    {
        $_POST['beyondwords_generate_audio_nonce'] = wp_create_nonce('beyondwords_generate_audio');

        if ($postKey) {
            $_POST[$postKey] = $postValue;
        }

        $postId = self::factory()->post->create([
            'post_type' => 'post',
            'post_title' => 'GenerateAudioTest::save',
            'post_content' => '<p>The body.</p>',
        ]);

        $this->_instance->save($postId);

        $this->assertSame($expect, get_post_meta($postId, 'beyondwords_generate_audio', true));

        wp_delete_post($postId, true);
    }

    public function saveProvider()
    {
        return [
            'Empty POST vars' => [
                'postKey'   => '',
                'postValue' => '',
                'expect'    => '0',
            ],
            'Some other POST var' => [
                'postKey'   => 'foo',
                'postValue' => 'bar',
                'expect'    => '0',
            ],
            'Integer' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => 1,
                'expect'    => '1',
            ],
            'String' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => '1',
                'expect'    => '1',
            ],
            '<script>' => [
                'postKey'   => 'beyondwords_generate_audio',
                'postValue' => 'foo<script></script>bar',
                'expect'    => '1',
            ]
        ];
    }

    /**
     * @test
     */
    public function shouldPreselectGenerateAudio()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'GenerateAudioTest::shouldPreselectGenerateAudio::post',
            'post_type' => 'post'
        ]);

        $page = self::factory()->post->create_and_get([
            'post_title' => 'GenerateAudioTest::shouldPreselectGenerateAudio::page',
            'post_type' => 'page'
        ]);

        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio(null));

        update_option('beyondwords_preselect', ['post' => '1']);
        $this->assertTrue($this->_instance->shouldPreselectGenerateAudio($post));
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($page));

        update_option('beyondwords_preselect', ['post' => ['category' => ['1']]]);
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($post));
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($page));

        update_option('beyondwords_preselect', ['page' => '1']);
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($post));
        $this->assertTrue($this->_instance->shouldPreselectGenerateAudio($page));

        update_option('beyondwords_preselect', ['page' => ['category' => ['1']]]);
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($post));
        $this->assertFalse($this->_instance->shouldPreselectGenerateAudio($page));

        delete_option('beyondwords_preselect');

        wp_delete_post($post->ID, true);
        wp_delete_post($page->ID, true);
    }
}
