<?php

use Beyondwords\Wordpress\Component\Settings\SettingsUpdated\SettingsUpdated;

final class SettingsUpdatedTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Posts\BulkEdit\BulkEdit
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        unset($_POST, $_REQUEST);

        // Your set up methods here.
        $this->_instance = new SettingsUpdated();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_REQUEST);

        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $settingsUpdated = new SettingsUpdated();
        $settingsUpdated->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array($settingsUpdated, 'addSettingsField')));
    }

    /**
     * @test
     */
    public function addSettingsField()
    {
        global $wp_settings_fields;

        $settingsUpdated = new SettingsUpdated();
        $settingsUpdated->addSettingsField();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-settings-updated', $wp_settings_fields['beyondwords_basic']['basic']);

        $field = $wp_settings_fields['beyondwords_basic']['basic']['beyondwords-settings-updated'];

        $this->assertSame('beyondwords-settings-updated', $field['id']);
        $this->assertSame('Settings Updated', $field['title']);
        $this->assertSame(array($settingsUpdated, 'render'), $field['callback']);
        $this->assertSame(['class' => 'hidden'], $field['args']);
    }
}
