<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Voices\Voices;

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
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
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
        Voices::init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array(Voices::class, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_sections;
        $wp_settings_sections = null;

        Voices::addSettingsSection();

        $this->assertArrayHasKey('beyondwords_voices', $wp_settings_sections);
        $this->assertArrayHasKey('voices', $wp_settings_sections['beyondwords_voices']);
        $this->assertSame('voices', $wp_settings_sections['beyondwords_voices']['voices']['id']);
        $this->assertSame('Voices', $wp_settings_sections['beyondwords_voices']['voices']['title']);
        $this->assertSame([Voices::class, 'sectionCallback'], $wp_settings_sections['beyondwords_voices']['voices']['callback']);
    }
}
