<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate\TitleVoiceSpeakingRate;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class TitleVoiceSpeakingRateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(TitleVoiceSpeakingRate::OPTION_NAME);
    }

    public function tearDown(): void
    {
        delete_option(TitleVoiceSpeakingRate::OPTION_NAME);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function option_name_constant_is_correct(): void
    {
        $this->assertSame(
            'beyondwords_project_title_voice_speaking_rate',
            TitleVoiceSpeakingRate::OPTION_NAME,
            'Option name constant should match expected value'
        );
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        TitleVoiceSpeakingRate::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [TitleVoiceSpeakingRate::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        TitleVoiceSpeakingRate::init();

        $this->assertTrue(
            has_action('pre_update_option_' . TitleVoiceSpeakingRate::OPTION_NAME) !== false,
            'Should register pre_update_option hook for syncing to dashboard'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        TitleVoiceSpeakingRate::addSetting();

        $this->assertArrayHasKey(
            TitleVoiceSpeakingRate::OPTION_NAME,
            $wp_registered_settings,
            'Should register the title voice speaking rate setting'
        );

        $setting = $wp_registered_settings[TitleVoiceSpeakingRate::OPTION_NAME];
        $this->assertSame('beyondwords_voices_settings', $setting['group']);
        $this->assertSame('integer', $setting['type']);
        $this->assertSame(100, $setting['default']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        TitleVoiceSpeakingRate::addSetting();

        $this->assertArrayHasKey(
            'beyondwords_voices',
            $wp_settings_fields,
            'Should add field to beyondwords_voices page'
        );

        $this->assertArrayHasKey(
            'voices',
            $wp_settings_fields['beyondwords_voices'],
            'Should add field to voices section'
        );

        $field = $wp_settings_fields['beyondwords_voices']['voices']['beyondwords-title-speaking-rate'];
        $this->assertSame('Title voice speaking rate', $field['title']);
        $this->assertSame([TitleVoiceSpeakingRate::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_range_input(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 100);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="range"]#' . TitleVoiceSpeakingRate::OPTION_NAME);
        $this->assertCount(1, $input, 'Should render a range input with correct ID');

        $this->assertSame(
            TitleVoiceSpeakingRate::OPTION_NAME,
            $input->attr('name'),
            'Input should have correct name attribute'
        );

        $this->assertSame('50', $input->attr('min'), 'Range should have min value of 50');
        $this->assertSame('200', $input->attr('max'), 'Range should have max value of 200');
        $this->assertSame('1', $input->attr('step'), 'Range should have step of 1');
    }

    /**
     * @test
     */
    public function render_outputs_current_value(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 150);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="range"]');
        $this->assertSame('150', $input->attr('value'), 'Range input should show current value');

        $output = $crawler->filter('output');
        $this->assertCount(1, $output, 'Should have output element');
        $this->assertStringContainsString('150%', $output->text(), 'Output should show percentage');
    }

    /**
     * @test
     */
    public function render_uses_default_value_when_option_not_set(): void
    {
        delete_option(TitleVoiceSpeakingRate::OPTION_NAME);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input[type="range"]');

        // WordPress get_option returns false when option doesn't exist
        // The template will use this value
        $this->assertNotNull($input->attr('value'));
    }

    /**
     * @test
     */
    public function render_outputs_description_text(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 100);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $this->assertStringContainsString(
            'Choose the default speaking rate for your title voice',
            $html,
            'Should contain description text'
        );

        $crawler = new Crawler($html);
        $description = $crawler->filter('p.description');
        $this->assertCount(1, $description, 'Should have description paragraph');
    }

    /**
     * @test
     */
    public function render_outputs_wrapper_div(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 100);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);
        $wrapper = $crawler->filter('div.beyondwords-setting__title-speaking-rate');
        $this->assertCount(1, $wrapper, 'Should have wrapper div with correct class');
    }

    /**
     * @test
     */
    public function render_has_javascript_attributes(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 100);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input[type="range"]');

        $this->assertNotNull($input->attr('oninput'), 'Should have oninput attribute for live updates');
        $this->assertNotNull($input->attr('onload'), 'Should have onload attribute');
    }

    /**
     * @test
     * @dataProvider validSpeakingRateProvider
     */
    public function accepts_valid_speaking_rates(int $rate): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, $rate);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input[type="range"]');

        $this->assertSame((string)$rate, $input->attr('value'));
    }

    public function validSpeakingRateProvider(): array
    {
        return [
            'minimum' => [50],
            'default' => [100],
            'faster' => [125],
            'slower' => [75],
            'maximum' => [200],
        ];
    }

    /**
     * @test
     */
    public function integration_full_workflow(): void
    {
        // Initialize hooks
        TitleVoiceSpeakingRate::init();

        // Register setting
        TitleVoiceSpeakingRate::addSetting();

        // Set a speaking rate
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 125);

        // Retrieve and verify
        $savedRate = get_option(TitleVoiceSpeakingRate::OPTION_NAME);
        $this->assertSame(125, $savedRate, 'Should save and retrieve speaking rate correctly');

        // Render and verify
        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $this->assertStringContainsString('125', $html, 'Rendered HTML should show saved rate');
        $this->assertStringContainsString('125%', $html, 'Rendered HTML should show percentage');
    }

    /**
     * @test
     */
    public function has_css_class_for_styling(): void
    {
        update_option(TitleVoiceSpeakingRate::OPTION_NAME, 100);

        $html = $this->captureOutput(function () {
            TitleVoiceSpeakingRate::render();
        });

        $crawler = new Crawler($html);
        $input = $crawler->filter('input.beyondwords_speaking_rate');
        $this->assertCount(1, $input, 'Should have beyondwords_speaking_rate class for JavaScript targeting');
    }

    /**
     * @test
     */
    public function title_speaking_rate_has_different_option_name_than_body(): void
    {
        $this->assertNotSame(
            'beyondwords_project_body_voice_speaking_rate',
            TitleVoiceSpeakingRate::OPTION_NAME,
            'TitleVoiceSpeakingRate should have different option name than BodyVoiceSpeakingRate'
        );
    }
}
