<?php

declare(strict_types=1);

use BeyondWords\Core\Environment;

class EnvironmentTest extends TestCase
{
    /**
     * @var \BeyondWords\Core\Environment
     */
    private $_instance;

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
    public function get_api_url()
    {
        if (defined('BEYONDWORDS_API_URL') && strlen(BEYONDWORDS_API_URL)) {
            $this->assertSame(BEYONDWORDS_API_URL, Environment::get_api_url());
        } else {
            $this->assertSame(Environment::BEYONDWORDS_API_URL, Environment::get_api_url());
        }
    }

    /**
     * @test
     */
    public function get_backend_url()
    {
        $this->assertSame(Environment::BEYONDWORDS_BACKEND_URL, Environment::get_backend_url());
    }

    /**
     * @test
     */
    public function get_js_sdk_url()
    {
        $this->assertSame(Environment::BEYONDWORDS_JS_SDK_URL, Environment::get_js_sdk_url());
    }

    /**
     * @test
     */
    public function get_amp_player_url()
    {
        $this->assertSame(Environment::BEYONDWORDS_AMP_PLAYER_URL, Environment::get_amp_player_url());
    }

    /**
     * @test
     */
    public function get_amp_img_url()
    {
        $this->assertSame(Environment::BEYONDWORDS_AMP_IMG_URL, Environment::get_amp_img_url());
    }

    /**
     * @test
     */
    public function get_dashboard_url()
    {
        $this->assertSame(Environment::BEYONDWORDS_DASHBOARD_URL, Environment::get_dashboard_url());
    }
}
