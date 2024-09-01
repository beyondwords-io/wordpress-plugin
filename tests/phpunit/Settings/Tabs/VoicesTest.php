<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Voices\Voices;
use Beyondwords\Wordpress\Core\ApiClient;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-voices
 */
class VoicesTabTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Voices\Voices
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        delete_transient('beyondwords_settings_errors');

        $apiClient       = new ApiClient();
        $this->_instance = new Voices($apiClient);

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
        global $wp_settings_sections;

        $this->_instance->addSettingsSection();

        $this->assertArrayHasKey('beyondwords_voices', $wp_settings_sections);
        $this->assertArrayHasKey('voices', $wp_settings_sections['beyondwords_voices']);
        $this->assertSame('voices', $wp_settings_sections['beyondwords_voices']['voices']['id']);
        $this->assertSame('Voices', $wp_settings_sections['beyondwords_voices']['voices']['title']);
        $this->assertSame([$this->_instance, 'sectionCallback'], $wp_settings_sections['beyondwords_voices']['voices']['callback']);
    }
}
