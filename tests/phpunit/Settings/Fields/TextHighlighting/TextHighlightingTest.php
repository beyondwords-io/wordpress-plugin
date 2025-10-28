<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\TextHighlighting\TextHighlighting;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class TextHighlightingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(TextHighlighting::OPTION_NAME);
        delete_option('beyondwords_player_theme_light');
        delete_option('beyondwords_player_theme_dark');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(TextHighlighting::OPTION_NAME);
        delete_option('beyondwords_player_theme_light');
        delete_option('beyondwords_player_theme_dark');
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        TextHighlighting::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [TextHighlighting::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        TextHighlighting::init();

        $this->assertTrue(
            has_action('pre_update_option_' . TextHighlighting::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        TextHighlighting::addSetting();

        $this->assertArrayHasKey(
            TextHighlighting::OPTION_NAME,
            $wp_registered_settings,
            'Should register the text highlighting setting'
        );

        $setting = $wp_registered_settings[TextHighlighting::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('string', $setting['type']);
        $this->assertSame('', $setting['default']);
        $this->assertSame([TextHighlighting::class, 'sanitize'], $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        TextHighlighting::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-text-highlighting',
            $wp_settings_fields['beyondwords_player']['styling'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-text-highlighting'];

        $this->assertSame('beyondwords-text-highlighting', $field['id']);
        $this->assertSame('Text highlighting', $field['title']);
        $this->assertSame([TextHighlighting::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function default_value_constant_is_empty_string(): void
    {
        $this->assertSame('', TextHighlighting::DEFAULT_VALUE);
    }

    /**
     * @test
     */
    public function render_outputs_checkbox_element(): void
    {
        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('input[type="checkbox"][name="' . TextHighlighting::OPTION_NAME . '"]'),
            'Should render a checkbox with correct name attribute'
        );

        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__player--text-highlighting'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function render_outputs_hidden_field_for_unchecked_state(): void
    {
        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $crawler = new Crawler($html);

        // Should have hidden input to ensure value is sent even when unchecked
        $this->assertCount(
            1,
            $crawler->filter('input[type="hidden"][name="' . TextHighlighting::OPTION_NAME . '"]'),
            'Should have hidden input for unchecked state'
        );
    }

    /**
     * @test
     */
    public function render_checkbox_has_correct_value(): void
    {
        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $crawler = new Crawler($html);

        $checkbox = $crawler->filter('input[type="checkbox"]');
        $this->assertSame('1', $checkbox->attr('value'));
    }

    /**
     * @test
     */
    public function render_includes_descriptive_label(): void
    {
        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $this->assertStringContainsString(
            'Highlight the current paragraph during audio playback',
            $html,
            'Should include descriptive label text'
        );
    }

    /**
     * @test
     */
    public function render_checks_checkbox_when_value_is_body(): void
    {
        update_option(TextHighlighting::OPTION_NAME, 'body');

        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $this->assertStringContainsString(
            "checked='checked'",
            $html,
            'Should check the checkbox when value is "body"'
        );
    }

    /**
     * @test
     */
    public function render_does_not_check_checkbox_when_value_is_empty(): void
    {
        update_option(TextHighlighting::OPTION_NAME, '');

        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $this->assertStringNotContainsString(
            "checked='checked'",
            $html,
            'Should not check the checkbox when value is empty'
        );
    }

    /**
     * @test
     */
    public function render_includes_light_theme_color_input(): void
    {
        update_option('beyondwords_player_theme_light', [
            'highlight_color' => '#eeeeee',
        ]);

        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $this->assertStringContainsString('Light theme settings', $html);
        $this->assertStringContainsString('Highlight color', $html);
        $this->assertStringContainsString('beyondwords_player_theme_light[highlight_color]', $html);
    }

    /**
     * @test
     */
    public function render_includes_dark_theme_color_input(): void
    {
        update_option('beyondwords_player_theme_dark', [
            'highlight_color' => '#333333',
        ]);

        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        $this->assertStringContainsString('Dark theme settings', $html);
        $this->assertStringContainsString('Highlight color', $html);
        $this->assertStringContainsString('beyondwords_player_theme_dark[highlight_color]', $html);
    }

    /**
     * @test
     */
    public function render_handles_missing_theme_options_gracefully(): void
    {
        delete_option('beyondwords_player_theme_light');
        delete_option('beyondwords_player_theme_dark');

        $html = $this->captureOutput(function () {
            TextHighlighting::render();
        });

        // Should not cause errors and should still render
        $this->assertStringContainsString('Light theme settings', $html);
        $this->assertStringContainsString('Dark theme settings', $html);
    }

    /**
     * @test
     * @dataProvider sanitizeProvider
     */
    public function sanitize_returns_correct_value(mixed $input, string $expected): void
    {
        $result = TextHighlighting::sanitize($input);

        $this->assertSame(
            $expected,
            $result,
            sprintf('Input "%s" should sanitize to "%s"', var_export($input, true), $expected)
        );
    }

    public function sanitizeProvider(): array
    {
        return [
            'truthy string "1"' => ['1', 'body'],
            'truthy string "body"' => ['body', 'body'],
            'truthy string "yes"' => ['yes', 'body'],
            'truthy int 1' => [1, 'body'],
            'truthy bool true' => [true, 'body'],
            'falsy string ""' => ['', ''],
            'falsy string "0"' => ['0', ''],
            'falsy int 0' => [0, ''],
            'falsy bool false' => [false, ''],
            'null' => [null, ''],
        ];
    }

    /**
     * @test
     */
    public function sanitize_returns_body_for_truthy_values(): void
    {
        $this->assertSame('body', TextHighlighting::sanitize('1'));
        $this->assertSame('body', TextHighlighting::sanitize(1));
        $this->assertSame('body', TextHighlighting::sanitize(true));
        $this->assertSame('body', TextHighlighting::sanitize('any-string'));
    }

    /**
     * @test
     */
    public function sanitize_returns_empty_string_for_falsy_values(): void
    {
        $this->assertSame('', TextHighlighting::sanitize(''));
        $this->assertSame('', TextHighlighting::sanitize(0));
        $this->assertSame('', TextHighlighting::sanitize(false));
        $this->assertSame('', TextHighlighting::sanitize(null));
    }

    /**
     * @test
     */
    public function integration_setting_and_retrieving_enabled_state(): void
    {
        TextHighlighting::addSetting();

        update_option(TextHighlighting::OPTION_NAME, '1');

        $this->assertSame('body', get_option(TextHighlighting::OPTION_NAME));
    }

    /**
     * @test
     */
    public function integration_setting_and_retrieving_disabled_state(): void
    {
        TextHighlighting::addSetting();

        update_option(TextHighlighting::OPTION_NAME, '');

        $this->assertSame('', get_option(TextHighlighting::OPTION_NAME));
    }

    /**
     * @test
     */
    public function integration_sanitize_callback_is_applied_on_update(): void
    {
        TextHighlighting::addSetting();

        // When checkbox is checked, form sends "1"
        update_option(TextHighlighting::OPTION_NAME, '1');

        // Should be sanitized to "body"
        $this->assertSame('body', get_option(TextHighlighting::OPTION_NAME));
    }

    /**
     * @test
     */
    public function integration_default_value_is_empty_string(): void
    {
        TextHighlighting::addSetting();

        delete_option(TextHighlighting::OPTION_NAME);

        $this->assertSame('', get_option(TextHighlighting::OPTION_NAME));
    }
}
