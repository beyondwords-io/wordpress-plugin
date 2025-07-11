<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Environment;

class EnvironmentTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Core\Environment
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
    public function getApiUrl()
    {
        // Tests should not be performed against public API
        $this->assertNotSame(Environment::BEYONDWORDS_API_URL, Environment::getApiUrl());
    }

    /**
     * @test
     */
    public function getBackendUrl()
    {
        $this->assertSame(Environment::BEYONDWORDS_BACKEND_URL, Environment::getBackendUrl());
    }

    /**
     * @test
     */
    public function getJsSdkUrl()
    {
        $this->assertSame(Environment::BEYONDWORDS_JS_SDK_URL, Environment::getJsSdkUrl());
    }

    /**
     * @test
     */
    public function getAmpPlayerUrl()
    {
        $this->assertSame(Environment::BEYONDWORDS_AMP_PLAYER_URL, Environment::getAmpPlayerUrl());
    }

    /**
     * @test
     */
    public function getAmpImgUrl()
    {
        $this->assertSame(Environment::BEYONDWORDS_AMP_IMG_URL, Environment::getAmpImgUrl());
    }

    /**
     * @test
     */
    public function getDashboardUrl()
    {
        $this->assertSame(Environment::BEYONDWORDS_DASHBOARD_URL, Environment::getDashboardUrl());
    }
}
