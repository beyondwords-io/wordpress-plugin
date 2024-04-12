<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\PrependExcerpt\PrependExcerpt;
use \Symfony\Component\DomCrawler\Crawler;

class PrependExcerptTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\PrependExcerpt\PrependExcerpt
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new PrependExcerpt();

        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(DATE_ISO8601));
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
    public function addSettingsField()
    {
        global $wp_settings_fields;

        $this->_instance->addSettingsField();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-prepend-excerpt', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-prepend-excerpt'];

        $this->assertSame('beyondwords-prepend-excerpt', $field['id']);
        $this->assertSame('Process excerpts', $field['title']);
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
