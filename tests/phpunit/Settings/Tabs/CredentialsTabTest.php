<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Credentials\Credentials;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-credentials
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
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        $this->_instance = new Credentials();

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

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
        $this->_instance->init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array($this->_instance, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_sections;
        $wp_settings_sections = null;

        $this->_instance->addSettingsSection();

        $this->assertArrayHasKey('beyondwords_credentials', $wp_settings_sections);
        $this->assertArrayHasKey('credentials', $wp_settings_sections['beyondwords_credentials']);
        $this->assertSame('credentials', $wp_settings_sections['beyondwords_credentials']['credentials']['id']);
        $this->assertSame('Credentials', $wp_settings_sections['beyondwords_credentials']['credentials']['title']);
        $this->assertSame([$this->_instance, 'sectionCallback'], $wp_settings_sections['beyondwords_credentials']['credentials']['callback']);
    }
}
