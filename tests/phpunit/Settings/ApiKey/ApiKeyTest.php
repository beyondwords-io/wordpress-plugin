<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\ApiKey\ApiKey;
use \Symfony\Component\DomCrawler\Crawler;

class ApiKeyTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\ApiKey\ApiKey
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new ApiKey();

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
        $this->assertArrayHasKey('beyondwords-api-key', $wp_settings_fields['beyondwords']['basic']);

        $field = $wp_settings_fields['beyondwords']['basic']['beyondwords-api-key'];

        $this->assertSame('beyondwords-api-key', $field['id']);
        $this->assertSame('BeyondWords API key', $field['title']);
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

        $field = $crawler->filter('input[type="text"][name="beyondwords_api_key"][size="50"]');

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

        $this->assertNotContains('Please enter the BeyondWords API key. This can be found in your project settings.', get_transient('beyondwords_settings_errors'));

        // Assert empty value adds an error
        $result = $this->_instance->sanitize('');

        $this->assertContains('Please enter the BeyondWords API key. This can be found in your project settings.', get_transient('beyondwords_settings_errors'));
    }
}
