<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Plugin;
use Beyondwords\Wordpress\Core\Core;

class PluginTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function initWithoutValidApiConnection()
    {
        $plugin = new Plugin();
        $plugin->init();

        $this->assertInstanceOf(Core::class, $plugin->core);
    }

    /**
     * @test
     */
    public function initWithValidApiConnection()
    {

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $plugin = new Plugin();
        $plugin->init();

        $this->assertInstanceOf(Core::class, $plugin->core);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
    }
}
