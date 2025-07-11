<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class ApiKeyTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey
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
        ApiKey::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(ApiKey::class, 'addSetting')));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        ApiKey::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-api-key', $wp_settings_fields['beyondwords_credentials']['credentials']);

        $field = $wp_settings_fields['beyondwords_credentials']['credentials']['beyondwords-api-key'];

        $this->assertSame('beyondwords-api-key', $field['id']);
        $this->assertSame('API key', $field['title']);
        $this->assertSame(array(ApiKey::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        ApiKey::render();

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
        wp_cache_set('beyondwords_settings_errors', [], 'beyondwords');

        // Assert valid value does not add an error
        $result = ApiKey::sanitize('ABCDE');

        $this->assertNotContains('Please enter the BeyondWords API key. This can be found in your project settings.', wp_cache_get('beyondwords_settings_errors', 'beyondwords'));

        // Assert empty value adds an error
        $result = ApiKey::sanitize('');

        $this->assertContains('Please enter the BeyondWords API key. This can be found in your project settings.', wp_cache_get('beyondwords_settings_errors', 'beyondwords'));
    }
}
