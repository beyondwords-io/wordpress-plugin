<?php

declare(strict_types=1);

use BeyondWords\SiteHealth\SiteHealth;
use BeyondWords\Core\Environment;

class SiteHealthTest extends TestCase
{
    /**
     * @var array
     */
    protected $info;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->info = [];
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->info = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        SiteHealth::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_filter('debug_information', array(SiteHealth::class, 'debug_information')));
    }

    /**
     * @test
     */
    public function debug_information()
    {
        $siteHealth = new SiteHealth();

        $info = $siteHealth->debug_information($this->info);

        $this->assertSame('BeyondWords - Text-to-Speech', $info['beyondwords']['label']);

        $this->assertArrayHasKey('plugin-version', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('api-url', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('api-communication', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('beyondwords_api_key', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('beyondwords_project_id', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('beyondwords_player_ui', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('beyondwords_prepend_excerpt', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('beyondwords_preselect', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('compatible-post-types', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('registered-filters', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('registered-deprecated-filters', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('beyondwords_date_activated', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('beyondwords_notice_review_dismissed', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('BEYONDWORDS_AUTOREGENERATE', $info['beyondwords']['fields']);
    }

    /**
     * @test
     */
    public function add_plugin_version()
    {
        $siteHealth = new SiteHealth();

        $this->assertTrue(defined('BEYONDWORDS__PLUGIN_VERSION'));

        update_option('beyondwords_version', BEYONDWORDS__PLUGIN_VERSION);

        $siteHealth->add_plugin_version($this->info);

        $this->assertSame('Plugin version', $this->info['beyondwords']['fields']['plugin-version']['label']);

        $errorMessage = 'Version mismatch: file: ' . BEYONDWORDS__PLUGIN_VERSION . ' / db: 1.2.3';

        $this->assertSame(BEYONDWORDS__PLUGIN_VERSION, $this->info['beyondwords']['fields']['plugin-version']['value']);

        delete_option('beyondwords_version');
    }

    /**
     * @test
     */
    public function add_plugin_version_displays_error()
    {
        $siteHealth = new SiteHealth();

        update_option('beyondwords_version', '1.2.3');

        $siteHealth->add_plugin_version($this->info);

        $this->assertSame('Plugin version', $this->info['beyondwords']['fields']['plugin-version']['label']);

        $errorMessage = 'Version mismatch: file: ' . BEYONDWORDS__PLUGIN_VERSION . ' / db: 1.2.3';

        $this->assertSame($errorMessage, $this->info['beyondwords']['fields']['plugin-version']['value']);

        delete_option('beyondwords_version');
    }

    /**
     * @test
     */
    public function add_rest_api_connection()
    {
        $siteHealth = new SiteHealth();

        $siteHealth->add_rest_api_connection($this->info);

        $this->assertSame('REST API URL', $this->info['beyondwords']['fields']['api-url']['label']);
        $this->assertSame(Environment::get_api_url(), $this->info['beyondwords']['fields']['api-url']['value']);

        $this->assertSame('Communication with REST API', $this->info['beyondwords']['fields']['api-communication']['label']);
        $this->assertSame('BeyondWords API is reachable', $this->info['beyondwords']['fields']['api-communication']['value']);
        $this->assertSame('true', $this->info['beyondwords']['fields']['api-communication']['debug']);
    }

    /**
     * @test
     */
    public function add_constant()
    {
        $siteHealth = new SiteHealth();

        $this->assertTrue(defined('BEYONDWORDS__PLUGIN_URI'));

        $siteHealth->add_constant($this->info, 'BEYONDWORDS__PLUGIN_URI');

        $this->assertSame('BEYONDWORDS__PLUGIN_URI', $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['label']);
        $this->assertSame(BEYONDWORDS__PLUGIN_URI, $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['value']);
        $this->assertSame(BEYONDWORDS__PLUGIN_URI, $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['debug']);

        $this->assertFalse(defined('SOME_UNDEFINED_CONSTANT'));

        $siteHealth->add_constant($this->info, 'SOME_UNDEFINED_CONSTANT');

        $this->assertSame('SOME_UNDEFINED_CONSTANT', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['label']);
        $this->assertSame('Undefined', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['value']);
        $this->assertSame('Undefined', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['debug']);
    }

    /**
     * @test
     */
    public function mask_string()
    {
        $siteHealth = new SiteHealth();

        $this->assertEquals('XXXXXXX',       $siteHealth->mask_string('1234567'));
        $this->assertEquals('XXXX5678',      $siteHealth->mask_string('12345678'));
        $this->assertEquals('XXXXXXXXXabcd', $siteHealth->mask_string('123456789abcd'));
        $this->assertEquals('XXXXXX78',      $siteHealth->mask_string('12345678', 2));
        $this->assertEquals('??????78',      $siteHealth->mask_string('12345678', 2, '?'));
    }
}
