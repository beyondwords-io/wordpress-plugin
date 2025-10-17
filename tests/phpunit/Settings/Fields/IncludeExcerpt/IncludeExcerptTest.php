<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class IncludeExcerptTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        IncludeExcerpt::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(IncludeExcerpt::class, 'addSetting')));
        $this->assertEquals(10, has_action('option_beyondwords_prepend_excerpt', 'rest_sanitize_boolean'));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        IncludeExcerpt::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-include-excerpt', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-include-excerpt'];

        $this->assertSame('beyondwords-include-excerpt', $field['id']);
        $this->assertSame('Excerpt', $field['title']);
        $this->assertSame(array(IncludeExcerpt::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $html = $this->captureOutput(function () {
            IncludeExcerpt::render();
        });

        $crawler = new Crawler($html);

        $selector = 'input[type="checkbox"][name="beyondwords_prepend_excerpt"]:not(:checked)';
        $this->assertCount(1, $crawler->filter($selector));

        update_option('beyondwords_prepend_excerpt', '1');

        $html = $this->captureOutput(function () {
            IncludeExcerpt::render();
        });

        $crawler = new Crawler($html);

        $selector = 'input[type="checkbox"][name="beyondwords_prepend_excerpt"]:checked';
        $this->assertCount(1, $crawler->filter($selector));

        delete_option('beyondwords_prepend_excerpt');
    }
}
