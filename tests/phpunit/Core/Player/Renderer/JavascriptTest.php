<?php

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use Beyondwords\Wordpress\Core\CoreUtils;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * Class Javascript.
 *
 * Responsible for rendering the JavaScript BeyondWords player.
 */
class Javascript
{
    /**
     * @test
     */
    public function check()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'Javascript::check::1',
        ]);

        $this->assertFalse(Amp::render($post));

        $post = self::factory()->post->create_and_get([
            'post_title' => 'Javascript::check::2',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->markTestIncomplete('This test needs to be updated for AMP rendering.');

        $this->assertTrue(Amp::render($post));
    }

    // /**
    //  * @test
    //  */
    // public static function check(\WP_Post $post): bool
    // {
    //     if (function_exists('is_preview') && is_preview()) {
    //         return false;
    //     }

    //     if (CoreUtils::isGutenbergPage() || CoreUtils::isEditScreen()) {
    //         return false;
    //     }

    //     $projectId = PostMetaUtils::getProjectId($post->ID);

    //     if (! $projectId) {
    //         return false;
    //     }

    //     $contentId = PostMetaUtils::getContentId($post->ID);
    //     $method = IntegrationMethod::getIntegrationMethod($post);

    //     return $method === IntegrationMethod::CLIENT_SIDE ||
    //            ($method === IntegrationMethod::REST_API && $contentId);
    // }

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

        $html = Javascript::render($post);

        $this->assertNotEmpty($html);

        setup_postdata($post);

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_reset_postdata();

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function renderWithFilter()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'JavascriptTest::renderWithFilter',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $filter = function($html, $postId, $projectId, $contentId) {
            return sprintf(
                '<div id="wrapper" data-post-id="%d" data-project-id="%d" data-podcast-id="%s">%s</div>',
                $postId,
                $projectId,
                $contentId,
                $html
            );
        };

        add_filter('beyondwords_player_html', $filter, 10, 4);

        $html = Javascript::render($post);

        remove_filter('beyondwords_player_html', $filter, 10, 4);

        $crawler = new Crawler($html);

        // <div id="wrapper">
        $wrapper = $crawler->filter('#wrapper');
        $this->assertCount(1, $wrapper);
        $this->assertSame("$post->ID", $wrapper->attr('data-post-id'));
        $this->assertSame(BEYONDWORDS_TESTS_PROJECT_ID, $wrapper->attr('data-project-id'));
        $this->assertSame(BEYONDWORDS_TESTS_CONTENT_ID, $wrapper->attr('data-podcast-id'));

        $this->assertCount(1, $wrapper->filter('div[data-beyondwords-player="true"][contenteditable="false"]'));

        wp_delete_post($post->ID, true);
    }
}
