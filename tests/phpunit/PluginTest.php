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
    public function constructor()
    {
        $plugin = new Plugin();
        $plugin->init();

        $this->assertInstanceOf(Core::class, $plugin->core);
    }
}
