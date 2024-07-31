<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Credentials\Credentials;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class CredentialsTabTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Credentials\Credentials
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        delete_transient('beyondwords_settings_errors');

        $this->_instance = new Credentials();

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
        $this->_instance->init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array($this->_instance, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_fields;

        $this->_instance->addSettingsSection();

        $this->assertArrayHasKey('beyondwords_credentials', $wp_settings_fields);
        $this->assertArrayHasKey('credentials', $wp_settings_fields['beyondwords_credentials']);
    }

    /**
     * @test
     **/
    public function sectionCallback()
    {
        $this->markTestSkipped('Moved into parent?');

        set_transient('beyondwords_settings_errors', ['Test Error']);

        $this->_instance->sectionCallback();

        $errors = get_transient('beyondwords_settings_errors', []);

        $this->assertEmpty($errors);

        $this->assertEquals(['Test Error'], $errors);
    }
}
