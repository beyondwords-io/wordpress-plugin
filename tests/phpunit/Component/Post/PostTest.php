<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Post\Post;

/**
 * @group post
 */
class PostTest extends TestCase
{
    private int $postId;

    public function setUp(): void
    {
        parent::setUp();

        // Create a test post
        $this->postId = self::factory()->post->create([
            'post_title' => 'Test Post Title',
            'post_author' => 1,
            'post_status' => 'publish',
        ]);
    }

    public function tearDown(): void
    {
        wp_delete_post($this->postId, true);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_wp_head_hook(): void
    {
        Post::init();

        $this->assertEquals(
            10,
            has_action('wp_head', [Post::class, 'addMetaTags']),
            'Should register addMetaTags on wp_head'
        );
    }

    /**
     * @test
     */
    public function addMetaTags_does_nothing_on_non_singular_page(): void
    {
        // Simulate archive page
        $this->go_to('/');

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertEmpty($html, 'Should not output meta tags on non-singular pages');
    }

    /**
     * @test
     */
    public function addMetaTags_does_nothing_without_project_id(): void
    {
        // Ensure no project ID is set
        delete_post_meta($this->postId, 'beyondwords_project_id');
        delete_option('beyondwords_project_id');

        // Go to singular post
        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertEmpty($html, 'Should not output meta tags without project ID');
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_title_meta_tag(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-title"', $html);
        $this->assertStringContainsString('data-beyondwords-title=', $html);
        $this->assertStringContainsString('Test Post Title', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_author_meta_tag(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-author"', $html);
        $this->assertStringContainsString('data-beyondwords-author=', $html);
        // Should contain the author's display name
        $this->assertNotEmpty($html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_publish_date_meta_tag(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-publish-date"', $html);
        $this->assertStringContainsString('data-beyondwords-publish-date=', $html);
        // Date should be in ISO 8601 format (contains 'T' for time separator)
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T/', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_title_voice_id_when_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_post_meta($this->postId, 'beyondwords_title_voice_id', 123);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-title-voice-id"', $html);
        $this->assertStringContainsString('data-beyondwords-title-voice-id=', $html);
        $this->assertStringContainsString('content="123"', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_omits_title_voice_id_when_not_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        delete_post_meta($this->postId, 'beyondwords_title_voice_id');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringNotContainsString('beyondwords-title-voice-id', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_body_voice_id_when_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_post_meta($this->postId, 'beyondwords_body_voice_id', 456);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-body-voice-id"', $html);
        $this->assertStringContainsString('data-beyondwords-body-voice-id=', $html);
        $this->assertStringContainsString('content="456"', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_omits_body_voice_id_when_not_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        delete_post_meta($this->postId, 'beyondwords_body_voice_id');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringNotContainsString('beyondwords-body-voice-id', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_summary_voice_id_when_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_post_meta($this->postId, 'beyondwords_summary_voice_id', 789);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-summary-voice-id"', $html);
        $this->assertStringContainsString('data-beyondwords-summary-voice-id=', $html);
        $this->assertStringContainsString('content="789"', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_omits_summary_voice_id_when_not_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        delete_post_meta($this->postId, 'beyondwords_summary_voice_id');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringNotContainsString('beyondwords-summary-voice-id', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_outputs_language_code_when_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_post_meta($this->postId, 'beyondwords_language_code', 'es');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringContainsString('name="beyondwords-article-language"', $html);
        $this->assertStringContainsString('data-beyondwords-article-language=', $html);
        $this->assertStringContainsString('content="es"', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_omits_language_code_when_not_set(): void
    {
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        delete_post_meta($this->postId, 'beyondwords_language_code');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        $this->assertStringNotContainsString('beyondwords-article-language', $html);
    }

    /**
     * @test
     */
    public function addMetaTags_escapes_output_properly(): void
    {
        $maliciousTitle = '<script>alert("xss")</script>';
        wp_update_post([
            'ID' => $this->postId,
            'post_title' => $maliciousTitle,
        ]);

        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        // Should escape HTML entities (WordPress may use smart quotes &#8220; instead of literal quotes)
        $this->assertStringNotContainsString('<script>', $html, 'Should not contain unescaped script tag');
        // The escaped output should be safe for HTML attributes
        $this->assertMatchesRegularExpression('/content="[^"]*alert[^"]*"/', $html, 'Should have escaped content in attribute');
    }

    /**
     * @test
     */
    public function integration_full_meta_tags_output(): void
    {
        // Set up all possible meta values
        update_post_meta($this->postId, 'beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_post_meta($this->postId, 'beyondwords_title_voice_id', 111);
        update_post_meta($this->postId, 'beyondwords_body_voice_id', 222);
        update_post_meta($this->postId, 'beyondwords_summary_voice_id', 333);
        update_post_meta($this->postId, 'beyondwords_language_code', 'en');

        $this->go_to(get_permalink($this->postId));

        $html = $this->captureOutput(function () {
            Post::addMetaTags();
        });

        // Should contain all meta tags
        $this->assertStringContainsString('beyondwords-title', $html);
        $this->assertStringContainsString('beyondwords-author', $html);
        $this->assertStringContainsString('beyondwords-publish-date', $html);
        $this->assertStringContainsString('beyondwords-title-voice-id', $html);
        $this->assertStringContainsString('beyondwords-body-voice-id', $html);
        $this->assertStringContainsString('beyondwords-summary-voice-id', $html);
        $this->assertStringContainsString('beyondwords-article-language', $html);

        // Should have all values
        $this->assertStringContainsString('111', $html);
        $this->assertStringContainsString('222', $html);
        $this->assertStringContainsString('333', $html);
    }
}
