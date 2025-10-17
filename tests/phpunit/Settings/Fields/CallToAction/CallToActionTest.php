<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\CallToAction\CallToAction;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class CallToActionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(CallToAction::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(CallToAction::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        CallToAction::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [CallToAction::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        CallToAction::init();

        $this->assertTrue(
            has_action('pre_update_option_' . CallToAction::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        CallToAction::addSetting();

        $this->assertArrayHasKey(
            CallToAction::OPTION_NAME,
            $wp_registered_settings,
            'Should register the call-to-action setting'
        );

        $setting = $wp_registered_settings[CallToAction::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('', $setting['default']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        CallToAction::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-player-call-to-action',
            $wp_settings_fields['beyondwords_player']['styling'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-player-call-to-action'];

        $this->assertSame('beyondwords-player-call-to-action', $field['id']);
        $this->assertSame('Call-to-action', $field['title']);
        $this->assertSame([CallToAction::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_text_input_element(): void
    {
        $html = $this->captureOutput(function () {
            CallToAction::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('input[type="text"][name="' . CallToAction::OPTION_NAME . '"]'),
            'Should render a text input with correct name attribute'
        );

        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__player--call-to-action'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function render_input_has_correct_attributes(): void
    {
        $html = $this->captureOutput(function () {
            CallToAction::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="text"]');

        $this->assertSame('Listen to this article', $input->attr('placeholder'));
        $this->assertSame('50', $input->attr('size'));
    }

    /**
     * @test
     */
    public function render_displays_current_value(): void
    {
        $testValue = 'Listen to this post';
        update_option(CallToAction::OPTION_NAME, $testValue);

        $html = $this->captureOutput(function () {
            CallToAction::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="text"]');
        $this->assertSame($testValue, $input->attr('value'));
    }

    /**
     * @test
     */
    public function render_displays_empty_value_when_option_not_set(): void
    {
        delete_option(CallToAction::OPTION_NAME);

        $html = $this->captureOutput(function () {
            CallToAction::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="text"]');
        $this->assertSame('', $input->attr('value'));
    }

    /**
     * @test
     */
    public function render_escapes_html_in_value(): void
    {
        $testValue = '<script>alert("xss")</script>';
        update_option(CallToAction::OPTION_NAME, $testValue);

        $html = $this->captureOutput(function () {
            CallToAction::render();
        });

        // Should be escaped
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * @test
     * @dataProvider callToActionProvider
     */
    public function integration_setting_and_retrieving_call_to_action(string $callToAction): void
    {
        CallToAction::addSetting();

        update_option(CallToAction::OPTION_NAME, $callToAction);

        $this->assertSame($callToAction, get_option(CallToAction::OPTION_NAME));
    }

    public function callToActionProvider(): array
    {
        return [
            'default message' => ['Listen to this article'],
            'custom message' => ['Click to hear this story'],
            'with emoji' => ['🎧 Listen now'],
            'empty string' => [''],
            'long message' => ['Listen to the audio version of this article narrated by our AI voice'],
        ];
    }

    /**
     * @test
     */
    public function integration_default_value_is_empty_string(): void
    {
        CallToAction::addSetting();

        delete_option(CallToAction::OPTION_NAME);

        $this->assertSame('', get_option(CallToAction::OPTION_NAME));
    }

    /**
     * @test
     */
    public function integration_accepts_special_characters(): void
    {
        CallToAction::addSetting();

        $specialChars = 'Listen & subscribe – it\'s free!';
        update_option(CallToAction::OPTION_NAME, $specialChars);

        $this->assertSame($specialChars, get_option(CallToAction::OPTION_NAME));
    }
}
