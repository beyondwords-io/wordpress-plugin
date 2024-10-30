<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class IncludeExcerptTest extends WP_UnitTestCase
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
        $this->_instance = new IncludeExcerpt();

        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $includeExcerpt = new IncludeExcerpt();
        $includeExcerpt->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array($includeExcerpt, 'addSetting')));
        $this->assertEquals(10, has_action('option_beyondwords_prepend_excerpt', 'rest_sanitize_boolean'));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        $this->_instance->addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-include-excerpt', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-include-excerpt'];

        $this->assertSame('beyondwords-include-excerpt', $field['id']);
        $this->assertSame('Excerpt', $field['title']);
        $this->assertSame(array($this->_instance, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $this->_instance->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $selector = 'input[type="checkbox"][name="beyondwords_prepend_excerpt"]:not(:checked)';
        $this->assertCount(1, $crawler->filter($selector));

        update_option('beyondwords_prepend_excerpt', '1');

        $this->_instance->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $selector = 'input[type="checkbox"][name="beyondwords_prepend_excerpt"]:checked';
        $this->assertCount(1, $crawler->filter($selector));

        delete_option('beyondwords_prepend_excerpt');
    }
}
