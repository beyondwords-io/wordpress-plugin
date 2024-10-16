<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Player\Player;
use Beyondwords\Wordpress\Core\ApiClient;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-player
 */
class PlayerTabTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Player\Player
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
        $this->_instance = new Player($apiClient);
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
        $wp_settings_sections = null;

        $this->_instance->addSettingsSection();

        $this->assertArrayHasKey('beyondwords_player', $wp_settings_sections);
        $this->assertArrayHasKey('player', $wp_settings_sections['beyondwords_player']);
        // $this->assertArrayHasKey('player', $wp_settings_fields['beyondwords_player']);

        $this->assertArrayHasKey('beyondwords_player', $wp_settings_sections);
        $this->assertArrayHasKey('player', $wp_settings_sections['beyondwords_player']);
        $this->assertSame('player', $wp_settings_sections['beyondwords_player']['player']['id']);
        $this->assertSame('Player', $wp_settings_sections['beyondwords_player']['player']['title']);
        $this->assertSame([$this->_instance, 'sectionCallback'], $wp_settings_sections['beyondwords_player']['player']['callback']);
    }
}
