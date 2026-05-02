<?php

declare(strict_types=1);

use BeyondWords\Settings\Tabs;

class TabsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        unset($_GET['tab']);
    }

    public function tearDown(): void
    {
        unset($_GET['tab']);
        delete_option('beyondwords_valid_api_connection');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Tabs::init();

        $this->assertSame(10, has_action('admin_init', [Tabs::class, 'register_sections']));
    }

    /**
     * @test
     */
    public function register_sections_creates_one_section_per_tab()
    {
        global $wp_settings_sections;

        $wp_settings_sections = [];

        Tabs::register_sections();

        $this->assertArrayHasKey(Tabs::PAGE_AUTHENTICATION, $wp_settings_sections);
        $this->assertArrayHasKey(Tabs::PAGE_INTEGRATION, $wp_settings_sections);
        $this->assertArrayHasKey(Tabs::PAGE_PREFERENCES, $wp_settings_sections);

        $this->assertArrayHasKey(
            Tabs::SECTION_AUTHENTICATION,
            $wp_settings_sections[Tabs::PAGE_AUTHENTICATION]
        );
        $this->assertArrayHasKey(
            Tabs::SECTION_INTEGRATION,
            $wp_settings_sections[Tabs::PAGE_INTEGRATION]
        );
        $this->assertArrayHasKey(
            Tabs::SECTION_PREFERENCES,
            $wp_settings_sections[Tabs::PAGE_PREFERENCES]
        );
    }

    /**
     * @test
     */
    public function get_visible_tabs_hides_all_but_auth_without_valid_connection()
    {
        delete_option('beyondwords_valid_api_connection');

        $tabs = Tabs::get_visible_tabs();

        $this->assertSame([Tabs::TAB_AUTHENTICATION], array_keys($tabs));
    }

    /**
     * @test
     */
    public function get_visible_tabs_shows_all_three_with_valid_connection()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));

        $tabs = Tabs::get_visible_tabs();

        $this->assertSame(
            [Tabs::TAB_AUTHENTICATION, Tabs::TAB_INTEGRATION, Tabs::TAB_PREFERENCES],
            array_keys($tabs)
        );
    }

    /**
     * @test
     */
    public function get_active_tab_falls_back_to_first_visible_when_query_string_missing()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));

        $this->assertSame(Tabs::TAB_AUTHENTICATION, Tabs::get_active_tab());
    }

    /**
     * @test
     */
    public function get_active_tab_returns_requested_tab_when_visible()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $_GET['tab'] = Tabs::TAB_PREFERENCES;

        $this->assertSame(Tabs::TAB_PREFERENCES, Tabs::get_active_tab());
    }

    /**
     * @test
     */
    public function get_active_tab_ignores_unknown_tab_slug()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $_GET['tab'] = 'not-a-real-tab';

        $this->assertSame(Tabs::TAB_AUTHENTICATION, Tabs::get_active_tab());
    }

    /**
     * @test
     */
    public function get_active_tab_ignores_hidden_tab_slug()
    {
        delete_option('beyondwords_valid_api_connection');
        $_GET['tab'] = Tabs::TAB_INTEGRATION;

        $this->assertSame(Tabs::TAB_AUTHENTICATION, Tabs::get_active_tab());
    }

    /**
     * @test
     */
    public function get_active_page_and_group_for_authentication()
    {
        delete_option('beyondwords_valid_api_connection');

        $this->assertSame(
            ['page' => Tabs::PAGE_AUTHENTICATION, 'group' => Tabs::SETTINGS_GROUP_AUTHENTICATION],
            Tabs::get_active_page_and_group()
        );
    }

    /**
     * @test
     */
    public function get_active_page_and_group_for_integration()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $_GET['tab'] = Tabs::TAB_INTEGRATION;

        $this->assertSame(
            ['page' => Tabs::PAGE_INTEGRATION, 'group' => Tabs::SETTINGS_GROUP_INTEGRATION],
            Tabs::get_active_page_and_group()
        );
    }

    /**
     * @test
     */
    public function get_active_page_and_group_for_preferences()
    {
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
        $_GET['tab'] = Tabs::TAB_PREFERENCES;

        $this->assertSame(
            ['page' => Tabs::PAGE_PREFERENCES, 'group' => Tabs::SETTINGS_GROUP_PREFERENCES],
            Tabs::get_active_page_and_group()
        );
    }
}
