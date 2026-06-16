<?php

declare(strict_types=1);

use BeyondWords\Settings\Preselect;
use \Symfony\Component\DomCrawler\Crawler;

class PreselectTest extends TestCase
{
    /**
     * Custom taxonomies registered during a test, unregistered in tearDown.
     *
     * @var string[]
     */
    private array $registered_taxonomies = [];

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        delete_option(Preselect::OPTION_NAME);

        foreach ($this->registered_taxonomies as $taxonomy) {
            unregister_taxonomy($taxonomy);
        }
        $this->registered_taxonomies = [];

        wp_dequeue_script('beyondwords-settings--preselect');
        wp_deregister_script('beyondwords-settings--preselect');

        parent::tearDown();
    }

    /**
     * Register a hierarchical taxonomy attached to the given post type and
     * remember it for teardown.
     */
    private function register_hierarchical_taxonomy(string $taxonomy, string $post_type = 'post', bool $show_ui = true): void
    {
        register_taxonomy($taxonomy, $post_type, [
            'hierarchical' => true,
            'show_ui'      => $show_ui,
            'public'       => true,
            'label'        => ucfirst($taxonomy),
        ]);
        $this->registered_taxonomies[] = $taxonomy;
    }

    /* ---------------------------------------------------------------------
     * Registration
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function init()
    {
        Preselect::init();

        $this->assertSame(10, has_action('admin_init', [Preselect::class, 'register']));
        $this->assertSame(10, has_action('admin_enqueue_scripts', [Preselect::class, 'enqueue_assets']));
    }

    /**
     * @test
     */
    public function enqueue_assets_enqueues_on_settings_page()
    {
        Preselect::enqueue_assets('settings_page_' . \BeyondWords\Settings\Settings::PAGE_SLUG);

        $this->assertTrue(wp_script_is('beyondwords-settings--preselect', 'enqueued'));
    }

    /**
     * @test
     */
    public function enqueue_assets_skips_other_admin_pages()
    {
        Preselect::enqueue_assets('plugins.php');

        $this->assertFalse(wp_script_is('beyondwords-settings--preselect', 'enqueued'));
    }

    /**
     * @test
     */
    public function register_registers_setting_and_field()
    {
        global $wp_registered_settings, $wp_settings_fields;

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

    /* ---------------------------------------------------------------------
     * get()
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function get_returns_default_when_option_unset()
    {
        delete_option(Preselect::OPTION_NAME);
        $this->assertSame(['post' => ['mode' => 'all']], Preselect::get());
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
        $value = [
            'post' => ['mode' => 'all'],
            'page' => ['mode' => 'terms', 'terms' => ['category' => [1, 2]]],
        ];
        update_option(Preselect::OPTION_NAME, $value);
        $this->assertSame($value, Preselect::get());
    }

    /* ---------------------------------------------------------------------
     * get_mode()
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function get_mode_off_for_missing_post_type()
    {
        $this->assertSame('off', Preselect::get_mode('post', []));
    }

    /**
     * @test
     */
    public function get_mode_all_for_all_mode()
    {
        $this->assertSame('all', Preselect::get_mode('post', ['post' => ['mode' => 'all']]));
    }

    /**
     * @test
     */
    public function get_mode_terms_for_terms_mode()
    {
        $preselect = ['post' => ['mode' => 'terms', 'terms' => ['category' => [1]]]];
        $this->assertSame('terms', Preselect::get_mode('post', $preselect));
    }

    /**
     * @test
     */
    public function get_mode_off_for_unknown_mode_string()
    {
        $this->assertSame('off', Preselect::get_mode('post', ['post' => ['mode' => 'bogus']]));
    }

    /**
     * @test
     */
    public function get_mode_treats_legacy_one_as_all()
    {
        // Pre-7.0.0 whole-post-type flag, before the migration runs.
        $this->assertSame('all', Preselect::get_mode('post', ['post' => '1']));
    }

    /**
     * @test
     */
    public function get_mode_treats_legacy_taxonomy_array_as_terms()
    {
        // Pre-7.0.0 term-gated shape (no 'mode' key), before the migration runs.
        $this->assertSame('terms', Preselect::get_mode('post', ['post' => ['category' => [1, 2]]]));
    }

    /**
     * @test
     */
    public function get_mode_off_for_legacy_empty_array()
    {
        $this->assertSame('off', Preselect::get_mode('post', ['post' => []]));
    }

    /* ---------------------------------------------------------------------
     * get_selected_terms()
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function get_selected_terms_returns_taxonomy_term_map_cast_to_ints()
    {
        $preselect = ['post' => ['mode' => 'terms', 'terms' => ['category' => ['1', '2']]]];
        $this->assertSame(['category' => [1, 2]], Preselect::get_selected_terms('post', $preselect));
    }

    /**
     * @test
     */
    public function get_selected_terms_empty_for_all_mode()
    {
        $this->assertSame([], Preselect::get_selected_terms('post', ['post' => ['mode' => 'all']]));
    }

    /**
     * @test
     */
    public function get_selected_terms_reads_legacy_taxonomy_array()
    {
        $this->assertSame(['category' => [1, 42]], Preselect::get_selected_terms('post', ['post' => ['category' => ['1', '42']]]));
    }

    /* ---------------------------------------------------------------------
     * should_preselect_for_post()
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function should_preselect_false_when_post_type_off()
    {
        update_option(Preselect::OPTION_NAME, []);
        $post_id = self::factory()->post->create();
        $this->assertFalse(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_true_for_all_mode()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);
        $post_id = self::factory()->post->create();
        $this->assertTrue(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_true_when_post_has_a_listed_category()
    {
        $term_id = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'News']);
        $post_id = self::factory()->post->create();
        wp_set_post_terms($post_id, [$term_id], 'category');

        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [$term_id]]],
        ]);

        $this->assertTrue(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_false_when_post_lacks_listed_terms()
    {
        $listed   = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'News']);
        $assigned = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'Sport']);
        $post_id  = self::factory()->post->create();
        wp_set_post_terms($post_id, [$assigned], 'category');

        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [$listed]]],
        ]);

        $this->assertFalse(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_matches_a_custom_hierarchical_taxonomy()
    {
        $this->register_hierarchical_taxonomy('genre');
        $term_id = self::factory()->term->create(['taxonomy' => 'genre', 'name' => 'Reviews']);
        $post_id = self::factory()->post->create();
        wp_set_post_terms($post_id, [$term_id], 'genre');

        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['genre' => [$term_id]]],
        ]);

        $this->assertTrue(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_exact_match_does_not_descend_to_children()
    {
        $parent = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'News']);
        $child  = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'Politics', 'parent' => $parent]);
        $post_id = self::factory()->post->create();
        wp_set_post_terms($post_id, [$child], 'category');

        // Only the parent is listed; the post has only the child → no match.
        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [$parent]]],
        ]);

        $this->assertFalse(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_is_tolerant_of_unregistered_taxonomy()
    {
        // A taxonomy that is not currently registered must be skipped, not error.
        $post_id = self::factory()->post->create();

        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['no_longer_here' => [999]]],
        ]);

        $this->assertFalse(Preselect::should_preselect_for_post($post_id));
    }

    /**
     * @test
     */
    public function should_preselect_accepts_a_wp_post_object()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);
        $post = get_post(self::factory()->post->create());
        $this->assertTrue(Preselect::should_preselect_for_post($post));
    }

    /* ---------------------------------------------------------------------
     * sanitize()
     * ------------------------------------------------------------------- */

    /**
     * @test
     */
    public function sanitize_stores_all_mode()
    {
        $clean = Preselect::sanitize(['post' => ['mode' => 'all']]);
        $this->assertSame(['mode' => 'all'], $clean['post']);
    }

    /**
     * @test
     */
    public function sanitize_drops_post_type_when_mode_off()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);
        $clean = Preselect::sanitize(['post' => ['mode' => 'off']]);
        $this->assertArrayNotHasKey('post', $clean);
    }

    /**
     * @test
     */
    public function sanitize_keeps_terms_for_rendered_taxonomies_and_casts_ids()
    {
        $clean = Preselect::sanitize([
            'post' => ['mode' => 'terms', 'terms' => ['category' => ['1', '2']]],
        ]);

        $this->assertSame(
            ['mode' => 'terms', 'terms' => ['category' => [1, 2]]],
            $clean['post']
        );
    }

    /**
     * @test
     */
    public function sanitize_drops_unknown_post_types()
    {
        $clean = Preselect::sanitize([
            'post'         => ['mode' => 'all'],
            'unknown-type' => ['mode' => 'all'],
        ]);

        $this->assertArrayHasKey('post', $clean);
        $this->assertArrayNotHasKey('unknown-type', $clean);
    }

    /**
     * @test
     */
    public function sanitize_drops_non_hierarchical_taxonomy_terms()
    {
        // post_tag is non-hierarchical and must never be stored.
        $clean = Preselect::sanitize([
            'post' => ['mode' => 'terms', 'terms' => ['post_tag' => [5], 'category' => [1]]],
        ]);

        $this->assertSame(['category' => [1]], $clean['post']['terms']);
    }

    /**
     * @test
     */
    public function sanitize_preserves_terms_for_currently_unregistered_taxonomy()
    {
        // 'genre' is NOT registered now, but was configured previously.
        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['genre' => [7], 'category' => [1]]],
        ]);

        // The form only submits category (genre's checkboxes weren't rendered).
        $clean = Preselect::sanitize([
            'post' => ['mode' => 'terms', 'terms' => ['category' => [2]]],
        ]);

        // genre is preserved; category is updated from the form.
        $this->assertSame([7], $clean['post']['terms']['genre']);
        $this->assertSame([2], $clean['post']['terms']['category']);
    }

    /**
     * @test
     */
    public function sanitize_preserves_config_for_currently_incompatible_post_type()
    {
        // A CPT that is not registered in this request must keep its config.
        update_option(Preselect::OPTION_NAME, [
            'post'    => ['mode' => 'all'],
            'cpt_gone' => ['mode' => 'all'],
        ]);

        $clean = Preselect::sanitize(['post' => ['mode' => 'all']]);

        $this->assertArrayHasKey('cpt_gone', $clean);
        $this->assertSame(['mode' => 'all'], $clean['cpt_gone']);
    }

    /**
     * @test
     */
    public function sanitize_returns_existing_when_value_is_not_array()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);
        $clean = Preselect::sanitize('not-an-array');
        $this->assertSame(['post' => ['mode' => 'all']], $clean);
    }

    /**
     * @test
     */
    public function sanitize_terms_mode_to_all_clears_stored_terms()
    {
        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [1]]],
        ]);

        $clean = Preselect::sanitize(['post' => ['mode' => 'all']]);

        $this->assertSame(['mode' => 'all'], $clean['post']);
    }

    /**
     * @test
     */
    public function sanitize_all_mode_to_terms_replaces_with_submitted_terms()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);

        $clean = Preselect::sanitize([
            'post' => ['mode' => 'terms', 'terms' => ['category' => [2]]],
        ]);

        $this->assertSame(
            ['mode' => 'terms', 'terms' => ['category' => [2]]],
            $clean['post']
        );
    }

    /**
     * @test
     */
    public function sanitize_does_not_leak_default_when_option_unset_and_post_filtered_out()
    {
        // Fresh install: option unset, and 'post' filtered out of compatible types.
        delete_option(Preselect::OPTION_NAME);
        $filter = static fn() => ['page'];
        add_filter('beyondwords_settings_post_types', $filter);

        $clean = Preselect::sanitize(['page' => ['mode' => 'all']]);

        remove_filter('beyondwords_settings_post_types', $filter);

        // DEFAULT_VALUE's 'post' => all must NOT leak into the saved value.
        $this->assertArrayNotHasKey('post', $clean);
        $this->assertSame(['mode' => 'all'], $clean['page']);
    }

    /* ---------------------------------------------------------------------
     * render()
     * ------------------------------------------------------------------- */

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
    public function render_outputs_three_radios_per_post_type_with_correct_state()
    {
        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        $crawler = new Crawler($html);

        // off / all / terms radios for "post".
        $radios = $crawler->filter('input[type="radio"][name="' . Preselect::OPTION_NAME . '[post][mode]"]');
        $this->assertSame(3, $radios->count());

        // The "all" radio is the checked one.
        $checked = $crawler->filter('input[type="radio"][name="' . Preselect::OPTION_NAME . '[post][mode]"][value="all"]');
        $this->assertSame('checked', $checked->attr('checked'));
    }

    /**
     * @test
     */
    public function render_outputs_hierarchical_term_checkboxes_with_checked_state()
    {
        $news = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'News']);

        update_option(Preselect::OPTION_NAME, [
            'post' => ['mode' => 'terms', 'terms' => ['category' => [$news]]],
        ]);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        $crawler = new Crawler($html);

        $checkbox = $crawler->filter(
            'input[type="checkbox"][name="' . Preselect::OPTION_NAME . '[post][terms][category][]"][value="' . $news . '"]'
        );
        $this->assertCount(1, $checkbox);
        $this->assertSame('checked', $checkbox->attr('checked'));
    }

    /**
     * @test
     */
    public function render_does_not_output_non_hierarchical_taxonomy_checkboxes()
    {
        self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Breaking']);

        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        $crawler = new Crawler($html);

        $tagCheckboxes = $crawler->filter(
            'input[name="' . Preselect::OPTION_NAME . '[post][terms][post_tag][]"]'
        );
        $this->assertCount(0, $tagCheckboxes);
    }

    /**
     * @test
     */
    public function render_outputs_custom_hierarchical_taxonomy_terms()
    {
        $this->register_hierarchical_taxonomy('genre');
        $reviews = self::factory()->term->create(['taxonomy' => 'genre', 'name' => 'Reviews']);

        update_option(Preselect::OPTION_NAME, ['post' => ['mode' => 'all']]);

        $html = $this->capture_output(function () {
            Preselect::render();
        });

        $crawler = new Crawler($html);

        $checkbox = $crawler->filter(
            'input[type="checkbox"][name="' . Preselect::OPTION_NAME . '[post][terms][genre][]"][value="' . $reviews . '"]'
        );
        $this->assertCount(1, $checkbox);
    }
}
