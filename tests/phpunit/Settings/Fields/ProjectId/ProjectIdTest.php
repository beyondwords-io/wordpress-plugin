<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
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
        ProjectId::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(ProjectId::class, 'addSetting')));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        ProjectId::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-project-id', $wp_settings_fields['beyondwords_credentials']['credentials']);

        $field = $wp_settings_fields['beyondwords_credentials']['credentials']['beyondwords-project-id'];

        $this->assertSame('beyondwords-project-id', $field['id']);
        $this->assertSame('Project ID', $field['title']);
        $this->assertSame(array(ProjectId::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        ProjectId::render();

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
        wp_cache_set('beyondwords_settings_errors', [], 'beyondwords');

        // Assert valid value does not add an error
        ProjectId::sanitize('ABCDE');
        $this->assertNotContains('Please enter your BeyondWords project ID. This can be found in your project settings.', wp_cache_get('beyondwords_settings_errors', 'beyondwords'));

        // Assert empty value adds an error
        ProjectId::sanitize('');
        $this->assertContains('Please enter your BeyondWords project ID. This can be found in your project settings.', wp_cache_get('beyondwords_settings_errors', 'beyondwords'));
    }
}
