<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\WidgetStyle\WidgetStyle;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class WidgetStyleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(WidgetStyle::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(WidgetStyle::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        WidgetStyle::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [WidgetStyle::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        WidgetStyle::init();

        $this->assertTrue(
            has_action('pre_update_option_' . WidgetStyle::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        WidgetStyle::addSetting();

        $this->assertArrayHasKey(
            WidgetStyle::OPTION_NAME,
            $wp_registered_settings,
            'Should register the widget style setting'
        );

        $setting = $wp_registered_settings[WidgetStyle::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('', $setting['default']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        WidgetStyle::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-widget-style',
            $wp_settings_fields['beyondwords_player']['widget'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['widget']['beyondwords-widget-style'];

        $this->assertSame('beyondwords-widget-style', $field['id']);
        $this->assertSame('Widget style', $field['title']);
        $this->assertSame([WidgetStyle::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function getOptions_returns_correct_structure(): void
    {
        $options = WidgetStyle::getOptions();

        $this->assertIsArray($options);
        $this->assertCount(5, $options);

        // Verify each option has value and label
        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['value']);
            $this->assertIsString($option['label']);
        }
    }

    /**
     * @test
     */
    public function getOptions_returns_all_widget_styles(): void
    {
        $options = WidgetStyle::getOptions();
        $values = array_column($options, 'value');

        $this->assertContains('standard', $values);
        $this->assertContains('none', $values);
        $this->assertContains('small', $values);
        $this->assertContains('large', $values);
        $this->assertContains('video', $values);
    }

    /**
     * @test
     */
    public function render_outputs_select_element(): void
    {
        $html = $this->captureOutput(function () {
            WidgetStyle::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('select[name="' . WidgetStyle::OPTION_NAME . '"]'),
            'Should render a select element with correct name attribute'
        );

        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__widget-style'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function render_outputs_all_widget_style_options(): void
    {
        $html = $this->captureOutput(function () {
            WidgetStyle::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('option[value="standard"]'));
        $this->assertCount(1, $crawler->filter('option[value="none"]'));
        $this->assertCount(1, $crawler->filter('option[value="small"]'));
        $this->assertCount(1, $crawler->filter('option[value="large"]'));
        $this->assertCount(1, $crawler->filter('option[value="video"]'));
    }

    /**
     * @test
     */
    public function render_includes_description_with_documentation_link(): void
    {
        $html = $this->captureOutput(function () {
            WidgetStyle::render();
        });

        $this->assertStringContainsString(
            'The style of widget to display',
            $html,
            'Should include description text'
        );

        $this->assertStringContainsString(
            'widgetStyle setting',
            $html,
            'Should include documentation link text'
        );

        $this->assertStringContainsString(
            'https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md',
            $html,
            'Should include documentation URL'
        );
    }

    /**
     * @test
     */
    public function render_preselects_current_value(): void
    {
        update_option(WidgetStyle::OPTION_NAME, 'large');

        $html = $this->captureOutput(function () {
            WidgetStyle::render();
        });

        $this->assertStringContainsString(
            "value=\"large\"  selected='selected'",
            $html,
            'Should preselect the large widget style when it is the current value'
        );
    }

    /**
     * @test
     * @dataProvider widgetStyleProvider
     */
    public function integration_setting_and_retrieving_widget_styles(string $widgetStyle): void
    {
        WidgetStyle::addSetting();

        update_option(WidgetStyle::OPTION_NAME, $widgetStyle);

        $this->assertSame($widgetStyle, get_option(WidgetStyle::OPTION_NAME));
    }

    public function widgetStyleProvider(): array
    {
        return [
            'standard' => ['standard'],
            'none' => ['none'],
            'small' => ['small'],
            'large' => ['large'],
            'video' => ['video'],
        ];
    }

    /**
     * @test
     */
    public function integration_default_value_is_empty_string(): void
    {
        WidgetStyle::addSetting();

        // Delete option to test default
        delete_option(WidgetStyle::OPTION_NAME);

        $this->assertSame('', get_option(WidgetStyle::OPTION_NAME));
    }
}
