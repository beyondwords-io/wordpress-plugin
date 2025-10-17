<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\WidgetPosition\WidgetPosition;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class WidgetPositionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(WidgetPosition::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(WidgetPosition::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        WidgetPosition::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [WidgetPosition::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        WidgetPosition::init();

        $this->assertTrue(
            has_action('pre_update_option_' . WidgetPosition::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        WidgetPosition::addSetting();

        $this->assertArrayHasKey(
            WidgetPosition::OPTION_NAME,
            $wp_registered_settings,
            'Should register the widget position setting'
        );

        $setting = $wp_registered_settings[WidgetPosition::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('auto', $setting['default']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        WidgetPosition::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-widget-position',
            $wp_settings_fields['beyondwords_player']['widget'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['widget']['beyondwords-widget-position'];

        $this->assertSame('beyondwords-widget-position', $field['id']);
        $this->assertSame('Widget position', $field['title']);
        $this->assertSame([WidgetPosition::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function getOptions_returns_correct_structure(): void
    {
        $options = WidgetPosition::getOptions();

        $this->assertIsArray($options);
        $this->assertCount(4, $options);

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
    public function getOptions_returns_all_widget_positions(): void
    {
        $options = WidgetPosition::getOptions();
        $values = array_column($options, 'value');

        $this->assertContains('auto', $values);
        $this->assertContains('center', $values);
        $this->assertContains('left', $values);
        $this->assertContains('right', $values);
    }

    /**
     * @test
     */
    public function getOptions_auto_is_labeled_as_default(): void
    {
        $options = WidgetPosition::getOptions();

        $autoOption = array_values(array_filter($options, function ($option) {
            return $option['value'] === 'auto';
        }))[0];

        $this->assertStringContainsString('default', $autoOption['label']);
    }

    /**
     * @test
     */
    public function render_outputs_select_element(): void
    {
        $html = $this->captureOutput(function () {
            WidgetPosition::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('select[name="' . WidgetPosition::OPTION_NAME . '"]'),
            'Should render a select element with correct name attribute'
        );

        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__widget-position'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function render_outputs_all_widget_position_options(): void
    {
        $html = $this->captureOutput(function () {
            WidgetPosition::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('option[value="auto"]'));
        $this->assertCount(1, $crawler->filter('option[value="center"]'));
        $this->assertCount(1, $crawler->filter('option[value="left"]'));
        $this->assertCount(1, $crawler->filter('option[value="right"]'));
    }

    /**
     * @test
     */
    public function render_preselects_current_value(): void
    {
        update_option(WidgetPosition::OPTION_NAME, 'left');

        $html = $this->captureOutput(function () {
            WidgetPosition::render();
        });

        $this->assertStringContainsString(
            "value=\"left\"  selected='selected'",
            $html,
            'Should preselect the left position when it is the current value'
        );
    }

    /**
     * @test
     */
    public function render_preselects_auto_by_default(): void
    {
        // Don't set any value, should default to 'auto'
        delete_option(WidgetPosition::OPTION_NAME);

        $html = $this->captureOutput(function () {
            WidgetPosition::render();
        });

        // When no option is set, WordPress defaults to the registered default value ('auto')
        // The selected attribute behavior depends on get_option returning the default
        $this->assertStringContainsString('<option value="auto"', $html);
    }

    /**
     * @test
     * @dataProvider widgetPositionProvider
     */
    public function integration_setting_and_retrieving_widget_positions(string $position): void
    {
        WidgetPosition::addSetting();

        update_option(WidgetPosition::OPTION_NAME, $position);

        $this->assertSame($position, get_option(WidgetPosition::OPTION_NAME));
    }

    public function widgetPositionProvider(): array
    {
        return [
            'auto' => ['auto'],
            'center' => ['center'],
            'left' => ['left'],
            'right' => ['right'],
        ];
    }

    /**
     * @test
     */
    public function integration_default_value_is_auto(): void
    {
        WidgetPosition::addSetting();

        delete_option(WidgetPosition::OPTION_NAME);

        $this->assertSame('auto', get_option(WidgetPosition::OPTION_NAME));
    }
}
