<?php

declare(strict_types=1);

use BeyondWords\SiteHealth\SiteHealth;
use BeyondWords\Api\Client;
use BeyondWords\Core\Urls;

class SiteHealthTest extends TestCase
{
    /**
     * @var array
     */
    protected $info;

    public function setUp(): void
    {
        parent::setUp();

        $this->info = [];
    }

    public function tearDown(): void
    {
        $this->info = null;

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
     *
     * With credentials configured the probe reports the API reachable (mock returns non-error).
     */
    public function add_rest_api_connection_reports_reachable_when_configured()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $siteHealth = new SiteHealth();

        $siteHealth->add_rest_api_connection($this->info);

        $this->assertSame('REST API URL', $this->info['beyondwords']['fields']['api-url']['label']);
        $this->assertSame(Urls::get_api_url(), $this->info['beyondwords']['fields']['api-url']['value']);

        $this->assertSame('Communication with REST API', $this->info['beyondwords']['fields']['api-communication']['label']);
        $this->assertSame('BeyondWords API is reachable', $this->info['beyondwords']['fields']['api-communication']['value']);
        $this->assertSame('true', $this->info['beyondwords']['fields']['api-communication']['debug']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * Unconfigured installs skip the probe — no blocking HTTP request without credentials.
     */
    public function add_rest_api_connection_skips_probe_without_credentials()
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $calls   = 0;
        $counter = function ($preempt, $args, $url) use (&$calls) {
            if (str_starts_with((string) $url, Urls::get_api_url())) {
                $calls++;
            }
            return $preempt;
        };
        add_filter('pre_http_request', $counter, 0, 3);

        $siteHealth = new SiteHealth();
        $siteHealth->add_rest_api_connection($this->info);

        remove_filter('pre_http_request', $counter, 0);

        $this->assertSame(0, $calls, 'No HTTP request should be made without API credentials');
        $this->assertSame('not-configured', $this->info['beyondwords']['fields']['api-communication']['debug']);
        $this->assertStringContainsString('not configured', $this->info['beyondwords']['fields']['api-communication']['value']);
    }

    /**
     * @test
     *
     * Deliberately NOT cached: a diagnostic must re-probe on every render, else it keeps
     * reporting "unreachable" after a fix (mirrors core's dotorg_communication field).
     */
    public function add_rest_api_connection_is_not_cached()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $calls   = 0;
        $counter = function ($preempt, $args, $url) use (&$calls) {
            if (str_starts_with((string) $url, Urls::get_api_url())) {
                $calls++;
            }
            return $preempt;
        };
        add_filter('pre_http_request', $counter, 0, 3);

        $first  = [];
        $second = [];

        $siteHealth = new SiteHealth();
        $siteHealth->add_rest_api_connection($first);
        $siteHealth->add_rest_api_connection($second);

        remove_filter('pre_http_request', $counter, 0);

        $this->assertSame(2, $calls, 'Every render must re-probe so the diagnostic is never stale');

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * The probe passes the client's shared default timeout, keeping a slow API within VIP guidance.
     */
    public function add_rest_api_connection_uses_the_shared_default_timeout()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $captured = null;
        $filter   = function ($preempt, $args, $url) use (&$captured) {
            if (str_starts_with((string) $url, Urls::get_api_url())) {
                $captured = $args;
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 0, 3);

        $siteHealth = new SiteHealth();
        $siteHealth->add_rest_api_connection($this->info);

        remove_filter('pre_http_request', $filter, 0);

        $this->assertIsArray($captured);
        $this->assertSame(Client::DEFAULT_REQUEST_TIMEOUT, $captured['timeout']);
        $this->assertLessThanOrEqual(3, $captured['timeout'], 'Must stay within VIP guidance (<= 3s)');

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     *
     * A transport-level failure is reported as unreachable, surfacing the request's error message.
     */
    public function add_rest_api_connection_reports_unreachable_on_error()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $filter = function ($preempt, $args, $url) {
            if (str_starts_with((string) $url, Urls::get_api_url())) {
                return new WP_Error('http_request_failed', 'Connection timed out');
            }
            return $preempt;
        };
        add_filter('pre_http_request', $filter, 1, 3);

        $siteHealth = new SiteHealth();
        $siteHealth->add_rest_api_connection($this->info);

        remove_filter('pre_http_request', $filter, 1);

        $value = $this->info['beyondwords']['fields']['api-communication']['value'];
        $this->assertStringContainsString('Unable to reach BeyondWords API', $value);
        $this->assertStringContainsString('Connection timed out', $value);
        $this->assertSame('Connection timed out', $this->info['beyondwords']['fields']['api-communication']['debug']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
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
