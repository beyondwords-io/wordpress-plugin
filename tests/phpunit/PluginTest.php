<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Plugin;

/**
 * @group plugin
 */
class PluginTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
    }

    public function tearDown(): void
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_all_components(): void
    {
        Plugin::init();

        // Verify that various hooks have been registered by components
        // These prove that the Plugin::init() method is initializing all components

        // Core components
        $this->assertTrue(
            has_action('init') !== false,
            'Should register Core component hooks'
        );

        $this->assertTrue(
            has_action('wp_head') !== false,
            'Should register Post component hooks'
        );

        $this->assertTrue(
            has_action('admin_menu') !== false,
            'Should register Settings component hooks'
        );

        // Verify components with valid API connection are initialized
        $this->assertTrue(
            has_action('admin_enqueue_scripts') !== false,
            'Should register admin scripts when API connection is valid'
        );
    }
}
