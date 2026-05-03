<?php

declare(strict_types=1);

use BeyondWords\Settings\Preselect;
use \Symfony\Component\DomCrawler\Crawler;

class PreselectTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        delete_option(Preselect::OPTION_NAME);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Preselect::init();

        $this->assertSame(10, has_action('admin_init', [Preselect::class, 'register']));
    }

    /**
     * @test
     */
    public function register_registers_setting_and_field()
    {
        global $wp_registered_settings, $wp_settings_fields;

        // Reset WP settings registry, then re-register.
        unregister_setting(\BeyondWords\Settings\Tabs::SETTINGS_GROUP_PREFERENCES, Preselect::OPTION_NAME);
        $wp_settings_fields[\BeyondWords\Settings\Tabs::PAGE_PREFERENCES] = [];

        Preselect::register();

        $this->assertArrayHasKey(Preselect::OPTION_NAME, $wp_registered_settings);
        $this->assertArrayHasKey(\BeyondWords\Settings\Tabs::PAGE_PREFERENCES, $wp_settings_fields);
        $this->assertArrayHasKey(
            'beyondwords-preselect',
            $wp_settings_fields[\BeyondWords\Settings\Tabs::PAGE_PREFERENCES][\BeyondWords\Settings\Tabs::SECTION_PREFERENCES]
        );
    }

    /**
     * @test
     */
    public function sanitize_rejects_non_array()
    {
        $this->assertSame([], Preselect::sanitize('not-an-array'));
        $this->assertSame([], Preselect::sanitize(null));
        $this->assertSame([], Preselect::sanitize(42));
    }

    /**
     * @test
     */
    public function sanitize_keeps_compatible_post_types_and_coerces_to_one()
    {
        $clean = Preselect::sanitize([
            'post' => '1',
            'page' => 'truthy',
        ]);

        $this->assertSame(['post' => '1', 'page' => '1'], $clean);
    }

    /**
     * @test
     */
    public function sanitize_drops_unknown_post_types()
    {
        $clean = Preselect::sanitize([
            'post'         => '1',
            'unknown-type' => '1',
        ]);

        $this->assertSame(['post' => '1'], $clean);
    }

    /**
     * @test
     */
    public function sanitize_drops_empty_values()
    {
        $clean = Preselect::sanitize([
            'post' => '1',
            'page' => '',
        ]);

        $this->assertSame(['post' => '1'], $clean);
    }

    /**
     * @test
     */
    public function render_shows_notice_when_no_compatible_post_types_found()
    {
        $filter = static fn() => [];
        add_filter('beyondwords_settings_post_types', $filter);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        remove_filter('beyondwords_settings_post_types', $filter);

        $this->assertStringContainsString('No compatible post types found', $html);
    }

    /**
     * @test
     */
    public function render_outputs_a_checkbox_per_post_type()
    {
        update_option(Preselect::OPTION_NAME, ['post' => '1']);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        $crawler = new Crawler($html);

        $checkboxes = $crawler->filter('input[type="checkbox"]');
        $this->assertGreaterThan(0, $checkboxes->count());

        $postCheckbox = $crawler->filter('input[name="' . Preselect::OPTION_NAME . '[post]"]');
        $this->assertCount(1, $postCheckbox);
        $this->assertSame('checked', $postCheckbox->attr('checked'));

        $pageCheckbox = $crawler->filter('input[name="' . Preselect::OPTION_NAME . '[page]"]');
        $this->assertCount(1, $pageCheckbox);
        $this->assertNull($pageCheckbox->attr('checked'));
    }

    /**
     * @test
     */
    public function get_returns_default_when_option_unset()
    {
        delete_option(Preselect::OPTION_NAME);
        $this->assertSame(['post' => '1'], Preselect::get());
    }

    /**
     * @test
     */
    public function get_returns_empty_array_when_option_is_not_array()
    {
        update_option(Preselect::OPTION_NAME, 'corrupted-string');
        $this->assertSame([], Preselect::get());
    }

    /**
     * @test
     */
    public function get_returns_stored_array()
    {
        update_option(Preselect::OPTION_NAME, ['post' => '1', 'page' => '1']);
        $this->assertSame(['post' => '1', 'page' => '1'], Preselect::get());
    }

    /**
     * @test
     */
    public function is_post_type_selected_returns_false_for_missing_key()
    {
        $this->assertFalse(Preselect::is_post_type_selected('post', []));
    }

    /**
     * @test
     */
    public function is_post_type_selected_returns_true_for_one_value()
    {
        $this->assertTrue(Preselect::is_post_type_selected('post', ['post' => '1']));
    }

    /**
     * @test
     */
    public function is_post_type_selected_returns_true_for_legacy_array_value()
    {
        // Pre-v7, value could be an array of taxonomy term IDs.
        $this->assertTrue(Preselect::is_post_type_selected('post', ['post' => [123, 456]]));
    }

    /**
     * @test
     */
    public function is_post_type_selected_returns_false_for_legacy_empty_array()
    {
        $this->assertFalse(Preselect::is_post_type_selected('post', ['post' => []]));
    }

    /**
     * @test
     */
    public function is_post_type_selected_reads_option_when_preselect_arg_is_null()
    {
        update_option(Preselect::OPTION_NAME, ['post' => '1']);

        $this->assertTrue(Preselect::is_post_type_selected('post'));
        $this->assertFalse(Preselect::is_post_type_selected('page'));
    }
}
