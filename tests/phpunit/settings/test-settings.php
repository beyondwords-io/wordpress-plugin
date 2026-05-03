<?php

declare(strict_types=1);

use BeyondWords\Settings\Settings;
use BeyondWords\Settings\Utils;
use \Symfony\Component\DomCrawler\Crawler;

class SettingsTest extends TestCase
{
    /**
     * @var \BeyondWords\Settings\Settings
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');
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
    public function init()
    {
        Settings::init();

        do_action('wp_loaded');

        $this->assertSame(1, has_action('admin_menu', array(Settings::class, 'add_options_page')));
        $this->assertSame(100, has_action('admin_notices', array(Settings::class, 'maybe_print_missing_creds_warning')));
        $this->assertSame(200, has_action('admin_notices', array(Settings::class, 'print_settings_errors')));
        $this->assertSame(10, has_action('admin_notices', array(Settings::class, 'maybe_print_review_notice')));
        $this->assertSame(10, has_action('load-settings_page_beyondwords', array(Settings::class, 'maybe_validate_api_creds')));

        $this->assertSame(10, has_action('rest_api_init', array(Settings::class, 'register_rest_routes')));

        $this->assertSame(10, has_filter('plugin_action_links_speechkit/speechkit.php', array(Settings::class, 'add_plugin_action_link')));
    }

    /**
     * @test
     */
    public function add_plugin_action_link()
    {
        $links = [
            '<a href="#">Deactivate</a>'
        ];

        $expected = '<a href="' .
            esc_url(admin_url('options-general.php?page=beyondwords')) .
            '">' . __('Settings', 'speechkit') . '</a>';

        $newLinks = Settings::add_plugin_action_link($links);

        $this->assertSame($newLinks[0], $expected);
        $this->assertSame($newLinks[1], $links[0]);
    }

    /**
     * @test
     */
    public function render_admin_page()
    {
        $html = $this->capture_output(function () {
            Settings::render_admin_page();
        });

        $crawler = new Crawler($html);

        $form = $crawler->filter('div.wrap > form#beyondwords-plugin-settings[method="post"]');
        $this->assertCount(1, $form);

        $heading = $crawler->filter('div.wrap > h1');
        $this->assertCount(1, $heading);
        $this->assertSame('BeyondWords Settings', $heading->text());

        $headerEnd = $crawler->filter('div.wrap hr.wp-header-end');
        $this->assertCount(1, $headerEnd);
    }

    /**
     * @test
     */
    public function has_valid_api_connection_without_any_field()
    {
        delete_option('beyondwords_valid_api_connection');
        $this->assertFalse(Utils::has_valid_api_connection());
    }

    /**
     * @test
     */
    public function has_valid_api_connection_with_expected_option()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->assertFalse(Utils::has_valid_api_connection());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function has_valid_api_connection_without_expected_option()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $this->assertTrue(Utils::has_valid_api_connection());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function print_settings_errors_without_errors()
    {
        $html = $this->capture_output(function () {
            Settings::print_settings_errors();
        });

        $this->assertSame('', $html);
    }

    /**
     * @test
     */
    public function print_settings_errors_with_errors()
    {
        $errors = [];
        $errors['Settings/Test1'] = 'Errors test 1';
        $errors['Settings/Test2'] = 'Errors test 2';
        $errors['Settings/Test3'] = 'Errors test 3';

        wp_cache_set('beyondwords_settings_errors', $errors, 'beyondwords');

        $html = $this->capture_output(function () {
            Settings::print_settings_errors();
        });

        $this->assertStringContainsString('<li>Errors test 1</li>', $html);
        $this->assertStringContainsString('<li>Errors test 2</li>', $html);
        $this->assertStringContainsString('<li>Errors test 3</li>', $html);
    }

    /**
     * @test
     */
    public function print_missing_api_creds_warning_without_api_creds()
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $html = $this->capture_output(function () {
            Settings::maybe_print_missing_creds_warning();
        });

        $this->assertNotEmpty($html);

        $crawler = new Crawler($html);

        $this->assertSame('To use BeyondWords, please update the plugin settings.', $crawler->filter('div.notice.notice-info > p > strong')->text());
        $this->assertStringEndsWith('/wp-admin/options-general.php?page=beyondwords', $crawler->filter('div.notice.notice-info > p > strong > a')->attr('href'));

        $this->assertStringContainsString('Don’t have a BeyondWords account yet?', $html);
        $this->assertStringContainsString('Sign up free', $html);
    }

    /**
     * @test
     */
    public function print_missing_api_creds_warning_without_api_key()
    {
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->capture_output(function () {
            Settings::maybe_print_missing_creds_warning();
        });

        $this->assertNotEmpty($html);

        $crawler = new Crawler($html);

        $this->assertSame('To use BeyondWords, please update the plugin settings.', $crawler->filter('div.notice.notice-info > p > strong')->text());
        $this->assertStringEndsWith('/wp-admin/options-general.php?page=beyondwords', $crawler->filter('div.notice.notice-info > p > strong > a')->attr('href'));

        $this->assertStringContainsString('Don’t have a BeyondWords account yet?', $html);
        $this->assertStringContainsString('Sign up free', $html);

        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function print_missing_api_creds_warning_without_project_id()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $html = $this->capture_output(function () {
            Settings::maybe_print_missing_creds_warning();
        });

        $this->assertNotEmpty($html);

        $crawler = new Crawler($html);

        $this->assertSame('To use BeyondWords, please update the plugin settings.', $crawler->filter('div.notice.notice-info > p > strong')->text());
        $this->assertStringEndsWith('/wp-admin/options-general.php?page=beyondwords', $crawler->filter('div.notice.notice-info > p > strong > a')->attr('href'));

        $this->assertStringContainsString('Don’t have a BeyondWords account yet?', $html);
        $this->assertStringContainsString('Sign up free', $html);

        delete_option('beyondwords_api_key');
    }

    /**
     * @test
     */
    public function print_missing_api_creds_warning_with_api_creds()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->capture_output(function () {
            Settings::maybe_print_missing_creds_warning();
        });

        $this->assertSame('', $html);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function print_settings_errors_with_missing_api_creds()
    {
        $errors = [];
        $errors['Settings/Test1'] = 'Errors test 1';
        $errors['Settings/Test2'] = 'Errors test 2';
        $errors['Settings/Test3'] = 'Errors test 3';

        wp_cache_set('beyondwords_settings_errors', $errors, 'beyondwords');

        $html = $this->capture_output(function () {
            Settings::print_settings_errors();
        });

        $this->assertStringContainsString('<li>Errors test 1</li>', $html);
        $this->assertStringContainsString('<li>Errors test 2</li>', $html);
        $this->assertStringContainsString('<li>Errors test 3</li>', $html);
    }

    /**
     * @test
     */
    public function get_visible_tabs()
    {
        $tabs = array(
            'authentication' => 'Authentication',
            'integration'    => 'Integration',
            'preferences'    => 'Preferences',
        );

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $this->assertSame($tabs, \BeyondWords\Settings\Tabs::get_visible_tabs());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_preselect');
        delete_option('beyondwords_valid_api_connection');

        // Without a valid API connection, only the Authentication tab is visible.
        $this->assertSame(
            array( 'authentication' => 'Authentication' ),
            \BeyondWords\Settings\Tabs::get_visible_tabs()
        );
    }

    /**
     * @test
     */
    public function rest_api_init_callback()
    {
        // Initiating the REST API.
        global $wp_rest_server;
        $server = $wp_rest_server = new \WP_REST_Server;
        do_action('rest_api_init');

        $userId = self::factory()->user->create(['role' => 'editor']);

        wp_set_current_user($userId);

        $postId = self::factory()->post->create([
            'post_title' => 'SettingsTest::rest_api_init_callback()',
            'post_status' => 'publish',
            'post_author' => $userId
        ]);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1', 'page' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        Settings::register_rest_routes();

        $request  = new \WP_REST_Request('GET', '/beyondwords/v1/settings');
        $response = $server->dispatch($request);
        $data     = $response->get_data();

        $this->assertInstanceOf(\WP_REST_Response::class, $response);

        $this->assertSame(BEYONDWORDS_TESTS_API_KEY, $data['apiKey']);
        $this->assertSame(['post' => '1', 'page' => '1'], $data['preselect']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_preselect');
        delete_option('beyondwords_valid_api_connection');

        wp_delete_post($postId);
        wp_delete_user($userId);
    }

    /**
     * @test
     */
    public function rest_api_response()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1', 'page' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $reponse = Settings::rest_settings_response();

        $this->assertInstanceOf(\WP_REST_Response::class, $reponse);

        $data = $reponse->get_data();

        $this->assertSame(BEYONDWORDS_TESTS_API_KEY, $data['apiKey']);
        $this->assertSame(['post' => '1', 'page' => '1'], $data['preselect']);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_preselect');
        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function maybe_print_plugin_review_notice_with_no_options()
    {
        $html = $this->capture_output(function () {
            Settings::maybe_print_review_notice();
        });

        $this->assertSame('', $html);
    }

    /**
     * @test
     */
    public function maybe_print_plugin_review_notice_with_recent_date()
    {
        update_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-13 days')));

        $html = $this->capture_output(function () {
            Settings::maybe_print_review_notice();
        });

        $this->assertSame('', $html);

        delete_option('beyondwords_date_activated');
    }

    /**
     * @test
     */
    public function maybe_print_plugin_review_notice_with_already_dismissed()
    {
        update_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-15 days')));
        update_option('beyondwords_notice_review_dismissed', gmdate(\DateTime::ATOM, strtotime('-1 second')));

        $html = $this->capture_output(function () {
            Settings::maybe_print_review_notice();
        });

        $this->assertSame('', $html);

        delete_option('beyondwords_date_activated');
        delete_option('beyondwords_notice_review_dismissed');
    }

    /**
     * @test
     */
    public function maybe_print_plugin_review_notice_with_expected_conditions()
    {
        update_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-15 days')));

        // The notice only renders on the BeyondWords settings page screen.
        set_current_screen('settings_page_beyondwords');

        $html = $this->capture_output(function () {
            Settings::maybe_print_review_notice();
        });

        $crawler = new Crawler($html);

        $field = $crawler->filter('#beyondwords_notice_review.notice.notice-info.is-dismissible');

        $this->assertCount(1, $field);

        delete_option('beyondwords_date_activated');
    }

    /**
     * @test
     */
    public function add_options_page_registers_under_settings()
    {
        global $submenu;

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        // Reset Settings submenu so we can detect a fresh registration.
        if (isset($submenu['options-general.php'])) {
            $submenu['options-general.php'] = array_filter(
                $submenu['options-general.php'],
                static fn($entry) => ($entry[2] ?? null) !== Settings::PAGE_SLUG
            );
        }

        Settings::add_options_page();

        $this->assertIsArray($submenu['options-general.php'] ?? null);

        $slugs = array_column($submenu['options-general.php'], 2);
        $this->assertContains(Settings::PAGE_SLUG, $slugs);
    }

    /**
     * @test
     */
    public function maybe_validate_api_creds_runs_on_authentication_tab()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        delete_option('beyondwords_valid_api_connection');

        // Authentication is the first/default tab when no `tab` query is set.
        $filter = function ($preempt, $args, $url) {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => '{"id":' . BEYONDWORDS_TESTS_PROJECT_ID . '}',
                'headers'  => [],
                'cookies'  => [],
            ];
        };
        add_filter('pre_http_request', $filter, 10, 3);

        Settings::maybe_validate_api_creds();

        remove_filter('pre_http_request', $filter, 10);

        $this->assertTrue(Utils::has_valid_api_connection());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function maybe_validate_api_creds_skips_when_not_on_auth_tab()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $_GET['tab'] = \BeyondWords\Settings\Tabs::TAB_PREFERENCES;

        // No HTTP filter — if validation ran it would attempt a real call and
        // overwrite the option; our assertion is that the option is untouched.
        Settings::maybe_validate_api_creds();

        $this->assertTrue(Utils::has_valid_api_connection());

        unset($_GET['tab']);
        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function rest_dismiss_review_notice_stores_timestamp()
    {
        delete_option('beyondwords_notice_review_dismissed');

        $response = Settings::rest_dismiss_review_notice();

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $stored = get_option('beyondwords_notice_review_dismissed');
        $this->assertNotEmpty($stored);
        $this->assertNotFalse(strtotime((string) $stored));

        delete_option('beyondwords_notice_review_dismissed');
    }
}
