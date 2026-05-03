<?php

declare(strict_types=1);

use BeyondWords\Core\Urls;

class UrlsTest extends TestCase
{
    /**
     * @var \BeyondWords\Core\Urls
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
            $this->assertSame(BEYONDWORDS_API_URL, Urls::get_api_url());
        } else {
            $this->assertSame(Urls::BEYONDWORDS_API_URL, Urls::get_api_url());
        }
    }

    /**
     * @test
     */
    public function get_backend_url()
    {
        $this->assertSame(Urls::BEYONDWORDS_BACKEND_URL, Urls::get_backend_url());
    }

    /**
     * @test
     */
    public function get_js_sdk_url()
    {
        $this->assertSame(Urls::BEYONDWORDS_JS_SDK_URL, Urls::get_js_sdk_url());
    }

    /**
     * @test
     */
    public function get_amp_player_url()
    {
        $this->assertSame(Urls::BEYONDWORDS_AMP_PLAYER_URL, Urls::get_amp_player_url());
    }

    /**
     * @test
     */
    public function get_amp_img_url()
    {
        $this->assertSame(Urls::BEYONDWORDS_AMP_IMG_URL, Urls::get_amp_img_url());
    }

    /**
     * @test
     */
    public function get_dashboard_url()
    {
        $this->assertSame(Urls::BEYONDWORDS_DASHBOARD_URL, Urls::get_dashboard_url());
    }
}
