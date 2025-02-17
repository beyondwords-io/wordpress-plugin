<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Settings;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use \Symfony\Component\DomCrawler\Crawler;

class SettingsTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Settings
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        $this->_instance = new Settings();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $settings = new Settings();
        $settings->init();

        do_action('wp_loaded');

        // Actions
        $this->assertSame(1, has_action('admin_menu', array($settings, 'addOptionsPage')));
        $this->assertSame(100, has_action('admin_notices', array($settings, 'printMissingApiCredsWarning')));
        $this->assertSame(200, has_action('admin_notices', array($settings, 'printSettingsErrors')));
        $this->assertSame(10, has_action('admin_notices', array($settings, 'maybePrintPluginReviewNotice')));
        $this->assertSame(10, has_action('admin_enqueue_scripts', array($settings, 'enqueueScripts')));
        $this->assertSame(10, has_action('load-settings_page_beyondwords', array($settings, 'maybeValidateApiCreds')));

        $this->assertSame(10, has_action('rest_api_init', array($settings, 'restApiInit')));

        $this->assertSame(10, has_filter('plugin_action_links_speechkit/speechkit.php', array($settings, 'addSettingsLinkToPluginPage')));
    }

    /**
     * @test
     */
    public function addSettingsLinkToPluginPage()
    {
        $links = [
            '<a href="#">Deactivate</a>'
        ];

        $expected = '<a href="' .
            esc_url(admin_url('options-general.php?page=beyondwords')) .
            '">' . __('Settings', 'speechkit') . '</a>';

        $newLinks = $this->_instance->addSettingsLinkToPluginPage($links);

        $this->assertSame($newLinks[0], $expected);
        $this->assertSame($newLinks[1], $links[0]);
    }

    /**
     * @test
     */
    public function createAdminInterface()
    {
        $this->_instance->createAdminInterface();

        $html = $this->getActualOutput();

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
    public function hasValidApiConnectionWithoutAnyField()
    {
        delete_option('beyondwords_valid_api_connection');
        $this->assertFalse(SettingsUtils::hasValidApiConnection());
    }

    /**
     * @test
     */
    public function hasValidApiConnectionWithExpectedOption()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->assertFalse(SettingsUtils::hasValidApiConnection());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function hasValidApiConnectionWithoutExpectedOption()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $this->assertTrue(SettingsUtils::hasValidApiConnection());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function printSettingsErrorsWithoutErrors()
    {
        $this->_instance->printSettingsErrors();

        $html = $this->getActualOutput();
        $this->assertSame('', $html);
    }

    /**
     * @test
     */
    public function printSettingsErrorsWithErrors()
    {
        $errors = [];
        $errors['Settings/Test1'] = 'Errors test 1';
        $errors['Settings/Test2'] = 'Errors test 2';
        $errors['Settings/Test3'] = 'Errors test 3';

        wp_cache_set('beyondwords_settings_errors', $errors, 'beyondwords');

        $this->_instance->printSettingsErrors();

        $html = $this->getActualOutput();

        $this->assertStringContainsString('<li>Errors test 1</li>', $html);
        $this->assertStringContainsString('<li>Errors test 2</li>', $html);
        $this->assertStringContainsString('<li>Errors test 3</li>', $html);
    }

    /**
     * @test
     */
    public function printMissingApiCredsWarningWithoutApiCreds()
    {
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        $this->_instance->printMissingApiCredsWarning();

        $html = $this->getActualOutput();
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
    public function printMissingApiCredsWarningWithoutApiKey()
    {
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->_instance->printMissingApiCredsWarning();

        $html = $this->getActualOutput();
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
    public function printMissingApiCredsWarningWithoutProjectId()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);

        $this->_instance->printMissingApiCredsWarning();

        $html = $this->getActualOutput();
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
    public function printMissingApiCredsWarningWithApiCreds()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->_instance->printMissingApiCredsWarning();

        $html = $this->getActualOutput();
        $this->assertSame('', $html);

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * @test
     */
    public function printSettingsErrorsWithMissingApiCreds()
    {
        $errors = [];
        $errors['Settings/Test1'] = 'Errors test 1';
        $errors['Settings/Test2'] = 'Errors test 2';
        $errors['Settings/Test3'] = 'Errors test 3';

        wp_cache_set('beyondwords_settings_errors', $errors, 'beyondwords');

        $this->_instance->printSettingsErrors();

        $html = $this->getActualOutput();

        $this->assertStringContainsString('<li>Errors test 1</li>', $html);
        $this->assertStringContainsString('<li>Errors test 2</li>', $html);
        $this->assertStringContainsString('<li>Errors test 3</li>', $html);
    }

    /**
     * @test
     */
    public function getTabs()
    {
        $tabs = array(
            'credentials'    => 'Credentials',
            'content'        => 'Content',
            'voices'         => 'Voices',
            'player'         => 'Player',
            'summarization'  => 'Summarization',
            'pronunciations' => 'Pronunciations',
            'advanced'       => 'Advanced',
        );

        $firstTab = array_filter($tabs, function($value, $key) {
            return $key === 0;
        });

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1', 'page' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $this->assertSame($tabs, $this->_instance->getTabs());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_preselect');
        delete_option('beyondwords_valid_api_connection');

        $this->assertSame($firstTab, $this->_instance->getTabs());
    }

    /**
     * @test
     */
    public function restApiInit()
    {
        // Initiating the REST API.
        global $wp_rest_server;
        $server = $wp_rest_server = new \WP_REST_Server;
        do_action('rest_api_init');

        $userId = self::factory()->user->create(['role' => 'editor']);

        wp_set_current_user($userId);

        $postId = self::factory()->post->create([
            'post_title' => 'SettingsTest::restApiInit()',
            'post_status' => 'publish',
            'post_author' => $userId
        ]);

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1', 'page' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $this->_instance->restApiInit();

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
    public function restApiResponse()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_preselect', ['post' => '1', 'page' => '1']);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);

        $reponse = $this->_instance->restApiResponse();

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
    public function maybePrintPluginReviewNoticeWithNoOptions()
    {
        $this->_instance->maybePrintPluginReviewNotice();
        $html = $this->getActualOutput();

        $this->assertSame('', $html);
    }

    /**
     * @test
     */
    public function maybePrintPluginReviewNoticeWithRecentDate()
    {
        update_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-13 days')));

        $this->_instance->maybePrintPluginReviewNotice();
        $html = $this->getActualOutput();

        $this->assertSame('', $html);

        delete_option('beyondwords_date_activated');
    }

    /**
     * @test
     */
    public function maybePrintPluginReviewNoticeWithAlreadyDismissed()
    {
        update_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-15 days')));
        update_option('beyondwords_notice_review_dismissed', gmdate(\DateTime::ATOM, strtotime('-1 second')));

        $this->_instance->maybePrintPluginReviewNotice();
        $html = $this->getActualOutput();

        $this->assertSame('', $html);

        delete_option('beyondwords_date_activated');
        delete_option('beyondwords_notice_review_dismissed');
    }

    /**
     * @test
     */
    public function maybePrintPluginReviewNoticeWithExpectedConditions()
    {
        set_option('beyondwords_date_activated', gmdate(\DateTime::ATOM, strtotime('-15 days')));

        $this->_instance->maybePrintPluginReviewNotice();
        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $field = $crawler->filter('#beyondwords_notice_review.notice.notice-info.is-dismissible');

        $this->assertCount(1, $field);

        delete_option('beyondwords_date_activated');
    }
}
