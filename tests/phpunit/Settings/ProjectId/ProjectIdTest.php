<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;
use \Symfony\Component\DomCrawler\Crawler;

class ProjectIdTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new ProjectId();

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
        global $wp_settings_fields;

        $this->_instance->addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-project-id', $wp_settings_fields['beyondwords_general']['credentials']);

        $field = $wp_settings_fields['beyondwords_general']['credentials']['beyondwords-project-id'];

        $this->assertSame('beyondwords-project-id', $field['id']);
        $this->assertSame('Project ID', $field['title']);
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

        $field = $crawler->filter('input[type="text"][name="beyondwords_project_id"][size="10"]');

        $this->assertCount(1, $field);
    }

    /**
     * @test
     */
    public function sanitize()
    {
        set_transient('beyondwords_settings_errors', []);

        // Assert valid value does not add an error
        $result = $this->_instance->sanitize('ABCDE');

        $this->assertNotContains('Please enter your BeyondWords project ID. This can be found in your project settings.', get_transient('beyondwords_settings_errors'));

        // Assert empty value adds an error
        $result = $this->_instance->sanitize('');

        $this->assertContains('Please enter your BeyondWords project ID. This can be found in your project settings.', get_transient('beyondwords_settings_errors'));
    }
}
