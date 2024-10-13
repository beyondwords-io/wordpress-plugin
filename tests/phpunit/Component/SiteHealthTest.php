<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\SiteHealth\SiteHealth;
use Beyondwords\Wordpress\Core\Environment;

class SiteHealthTest extends WP_UnitTestCase
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
        $siteHealth = new SiteHealth();
        $siteHealth->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_filter('debug_information', array($siteHealth, 'debugInformation')));
    }

    /**
     * @test
     */
    public function debugInformation()
    {
        $siteHealth = new SiteHealth();

        $info = $siteHealth->debugInformation($this->info);

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
        $this->assertArrayHasKey('incompatible-post-types', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('registered-filters', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('registered-deprecated-filters', $info['beyondwords']['fields']);

        $this->assertArrayHasKey('BEYONDWORDS_AUTO_SYNC_SETTINGS', $info['beyondwords']['fields']);
        $this->assertArrayHasKey('BEYONDWORDS_AUTOREGENERATE', $info['beyondwords']['fields']);
    }

    /**
     * @test
     */
    public function addPluginVersion()
    {
        $siteHealth = new SiteHealth();

        $this->assertTrue(defined('BEYONDWORDS__PLUGIN_VERSION'));

        update_option('beyondwords_version', BEYONDWORDS__PLUGIN_VERSION);

        $siteHealth->addPluginVersion($this->info);

        $this->assertSame('Plugin version', $this->info['beyondwords']['fields']['plugin-version']['label']);

        $errorMessage = 'Version mismatch: file: ' . BEYONDWORDS__PLUGIN_VERSION . ' / db: 1.2.3';

        $this->assertSame(BEYONDWORDS__PLUGIN_VERSION, $this->info['beyondwords']['fields']['plugin-version']['value']);

        delete_option('beyondwords_version');
    }

    /**
     * @test
     */
    public function addPluginVersionDisplaysError()
    {
        $siteHealth = new SiteHealth();

        update_option('beyondwords_version', '1.2.3');

        $siteHealth->addPluginVersion($this->info);

        $this->assertSame('Plugin version', $this->info['beyondwords']['fields']['plugin-version']['label']);

        $errorMessage = 'Version mismatch: file: ' . BEYONDWORDS__PLUGIN_VERSION . ' / db: 1.2.3';

        $this->assertSame($errorMessage, $this->info['beyondwords']['fields']['plugin-version']['value']);

        delete_option('beyondwords_version');
    }

    /**
     * @test
     */
    public function addRestApiConnection()
    {
        $siteHealth = new SiteHealth();

        $siteHealth->addRestApiConnection($this->info);

        $this->assertSame('REST API URL', $this->info['beyondwords']['fields']['api-url']['label']);
        $this->assertSame(Environment::getApiUrl(), $this->info['beyondwords']['fields']['api-url']['value']);

        $this->assertSame('Communication with REST API', $this->info['beyondwords']['fields']['api-communication']['label']);
        $this->assertSame('BeyondWords API is reachable', $this->info['beyondwords']['fields']['api-communication']['value']);
        $this->assertSame('true', $this->info['beyondwords']['fields']['api-communication']['debug']);
    }

    /**
     * @test
     */
    public function addConstant()
    {
        $siteHealth = new SiteHealth();

        $this->assertTrue(defined('BEYONDWORDS__PLUGIN_URI'));

        $siteHealth->addConstant($this->info, 'BEYONDWORDS__PLUGIN_URI');

        $this->assertSame('BEYONDWORDS__PLUGIN_URI', $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['label']);
        $this->assertSame(BEYONDWORDS__PLUGIN_URI, $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['value']);
        $this->assertSame(BEYONDWORDS__PLUGIN_URI, $this->info['beyondwords']['fields']['BEYONDWORDS__PLUGIN_URI']['debug']);

        $this->assertFalse(defined('SOME_UNDEFINED_CONSTANT'));

        $siteHealth->addConstant($this->info, 'SOME_UNDEFINED_CONSTANT');

        $this->assertSame('SOME_UNDEFINED_CONSTANT', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['label']);
        $this->assertSame('Undefined', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['value']);
        $this->assertSame('undefined', $this->info['beyondwords']['fields']['SOME_UNDEFINED_CONSTANT']['debug']);
    }

    /**
     * @test
     */
    public function maskString()
    {
        $siteHealth = new SiteHealth();

        $this->assertEquals('XXXXXXX',       $siteHealth->maskString('1234567'));
        $this->assertEquals('XXXX5678',      $siteHealth->maskString('12345678'));
        $this->assertEquals('XXXXXXXXXabcd', $siteHealth->maskString('123456789abcd'));
        $this->assertEquals('XXXXXX78',      $siteHealth->maskString('12345678', 2));
        $this->assertEquals('??????78',      $siteHealth->maskString('12345678', 2, '?'));
    }
}
