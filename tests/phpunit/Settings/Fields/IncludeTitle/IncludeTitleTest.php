<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\IncludeTitle\IncludeTitle;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class IncludeTitleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(IncludeTitle::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(IncludeTitle::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function default_value_constant_is_true(): void
    {
        $this->assertTrue(IncludeTitle::DEFAULT_VALUE);
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        IncludeTitle::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [IncludeTitle::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        IncludeTitle::init();

        $this->assertTrue(
            has_action('pre_update_option_' . IncludeTitle::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function init_registers_option_filter(): void
    {
        IncludeTitle::init();

        $this->assertTrue(
            has_filter('option_' . IncludeTitle::OPTION_NAME),
            'Should register option filter for boolean sanitization'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        IncludeTitle::addSetting();

        $this->assertArrayHasKey(
            IncludeTitle::OPTION_NAME,
            $wp_registered_settings,
            'Should register the include title setting'
        );

        $setting = $wp_registered_settings[IncludeTitle::OPTION_NAME];
        $this->assertSame('beyondwords_content_settings', $setting['group']);
        $this->assertSame('boolean', $setting['type']);
        $this->assertTrue($setting['default']);
        $this->assertSame('rest_sanitize_boolean', $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        IncludeTitle::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-include-title',
            $wp_settings_fields['beyondwords_content']['content'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-include-title'];

        $this->assertSame('beyondwords-include-title', $field['id']);
        $this->assertSame('Title', $field['title']);
        $this->assertSame([IncludeTitle::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_checkbox_element(): void
    {
        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('input[type="checkbox"][name="' . IncludeTitle::OPTION_NAME . '"]'),
            'Should render a checkbox with correct name attribute'
        );
    }

    /**
     * @test
     */
    public function render_outputs_hidden_field_for_unchecked_state(): void
    {
        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('input[type="hidden"][name="' . IncludeTitle::OPTION_NAME . '"]'),
            'Should have hidden input for unchecked state'
        );
    }

    /**
     * @test
     */
    public function render_checkbox_has_value_one(): void
    {
        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $crawler = new Crawler($html);

        $checkbox = $crawler->filter('input[type="checkbox"]');
        $this->assertSame('1', $checkbox->attr('value'));
    }

    /**
     * @test
     */
    public function render_includes_descriptive_text(): void
    {
        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $this->assertStringContainsString(
            'Include title in audio',
            $html,
            'Should include descriptive label text'
        );
    }

    /**
     * @test
     */
    public function render_checks_checkbox_when_value_is_true(): void
    {
        update_option(IncludeTitle::OPTION_NAME, true);

        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $this->assertStringContainsString(
            "checked='checked'",
            $html,
            'Should check the checkbox when value is true'
        );
    }

    /**
     * @test
     */
    public function render_does_not_check_checkbox_when_value_is_false(): void
    {
        update_option(IncludeTitle::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            IncludeTitle::render();
        });

        $this->assertStringNotContainsString(
            "checked='checked'",
            $html,
            'Should not check the checkbox when value is false'
        );
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function integration_setting_and_retrieving_boolean_values(mixed $input, bool $expected): void
    {
        IncludeTitle::addSetting();

        update_option(IncludeTitle::OPTION_NAME, $input);

        $this->assertSame($expected, get_option(IncludeTitle::OPTION_NAME));
    }

    public function booleanProvider(): array
    {
        return [
            'true' => [true, true],
            'false' => [false, false],
            'int 1' => [1, true],
            'int 0' => [0, false],
            'string "1"' => ['1', true],
            'string "0"' => ['0', false],
            'string "true"' => ['true', true],
            'string "false"' => ['false', false],
        ];
    }

    /**
     * @test
     */
    public function integration_default_value_is_true(): void
    {
        IncludeTitle::addSetting();

        delete_option(IncludeTitle::OPTION_NAME);

        $this->assertTrue(get_option(IncludeTitle::OPTION_NAME));
    }

    /**
     * @test
     */
    public function integration_option_filter_sanitizes_to_boolean(): void
    {
        IncludeTitle::init();
        IncludeTitle::addSetting();

        update_option(IncludeTitle::OPTION_NAME, 'yes');

        $value = get_option(IncludeTitle::OPTION_NAME);

        $this->assertIsBool($value);
    }
}
