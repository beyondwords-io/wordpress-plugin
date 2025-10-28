<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlaybackControls\PlaybackControls;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class PlaybackControlsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(PlaybackControls::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(PlaybackControls::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function player_settings_docs_url_constant_exists(): void
    {
        $this->assertSame(
            'https://github.com/beyondwords-io/player/blob/main/doc/player-settings.md',
            PlaybackControls::PLAYER_SETTINGS_DOCS_URL
        );
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        PlaybackControls::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [PlaybackControls::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        PlaybackControls::init();

        $this->assertTrue(
            has_action('pre_update_option_' . PlaybackControls::OPTION_NAME),
            'Should register pre_update_option hook'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        PlaybackControls::addSetting();

        $this->assertArrayHasKey(
            PlaybackControls::OPTION_NAME,
            $wp_registered_settings,
            'Should register the skip button style setting'
        );

        $setting = $wp_registered_settings[PlaybackControls::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('', $setting['default']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        PlaybackControls::addSetting();

        $this->assertArrayHasKey(
            'beyondwords-player-skip-button-style',
            $wp_settings_fields['beyondwords_player']['playback-controls'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['playback-controls']['beyondwords-player-skip-button-style'];

        $this->assertSame('beyondwords-player-skip-button-style', $field['id']);
        $this->assertSame('Skip button style', $field['title']);
        $this->assertSame([PlaybackControls::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_text_input_element(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(
            1,
            $crawler->filter('input[type="text"][name="' . PlaybackControls::OPTION_NAME . '"]'),
            'Should render a text input with correct name attribute'
        );

        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__player-skip-button-style'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function render_input_has_correct_attributes(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="text"]');

        $this->assertSame('auto', $input->attr('placeholder'));
        $this->assertSame('20', $input->attr('size'));
    }

    /**
     * @test
     */
    public function render_displays_current_value(): void
    {
        $testValue = 'segments';
        update_option(PlaybackControls::OPTION_NAME, $testValue);

        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $crawler = new Crawler($html);

        $input = $crawler->filter('input[type="text"]');
        $this->assertSame($testValue, $input->attr('value'));
    }

    /**
     * @test
     */
    public function render_includes_description_with_possible_values(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $this->assertStringContainsString(
            'The style of skip buttons to show in the player',
            $html,
            'Should include description text'
        );

        $this->assertStringContainsString(
            '<code>auto</code>',
            $html,
            'Should mention auto option'
        );

        $this->assertStringContainsString(
            '<code>segments</code>',
            $html,
            'Should mention segments option'
        );

        $this->assertStringContainsString(
            '<code>seconds</code>',
            $html,
            'Should mention seconds option'
        );

        $this->assertStringContainsString(
            '<code>audios</code>',
            $html,
            'Should mention audios option'
        );
    }

    /**
     * @test
     */
    public function render_includes_examples_for_seconds_format(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $this->assertStringContainsString(
            '<code>seconds-15</code>',
            $html,
            'Should include example for single second value'
        );

        $this->assertStringContainsString(
            '<code>seconds-15-30</code>',
            $html,
            'Should include example for dual second values'
        );
    }

    /**
     * @test
     */
    public function render_includes_documentation_link(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $this->assertStringContainsString(
            PlaybackControls::PLAYER_SETTINGS_DOCS_URL,
            $html,
            'Should include Player Settings documentation URL'
        );

        $this->assertStringContainsString(
            'Player Settings',
            $html,
            'Should include link text'
        );

        $this->assertStringContainsString(
            'Refer to the',
            $html,
            'Should include introductory text for documentation link'
        );
    }

    /**
     * @test
     */
    public function render_description_uses_paragraph_elements(): void
    {
        $html = $this->captureOutput(function () {
            PlaybackControls::render();
        });

        $crawler = new Crawler($html);

        // Should have at least 2 description paragraphs
        $this->assertGreaterThanOrEqual(
            2,
            $crawler->filter('p.description')->count(),
            'Should have multiple description paragraphs'
        );
    }

    /**
     * @test
     * @dataProvider skipButtonStyleProvider
     */
    public function integration_setting_and_retrieving_skip_button_styles(string $style): void
    {
        PlaybackControls::addSetting();

        update_option(PlaybackControls::OPTION_NAME, $style);

        $this->assertSame($style, get_option(PlaybackControls::OPTION_NAME));
    }

    public function skipButtonStyleProvider(): array
    {
        return [
            'auto' => ['auto'],
            'segments' => ['segments'],
            'seconds' => ['seconds'],
            'audios' => ['audios'],
            'seconds with value' => ['seconds-15'],
            'seconds with two values' => ['seconds-15-30'],
            'seconds with custom values' => ['seconds-10-20'],
            'empty string' => [''],
        ];
    }

    /**
     * @test
     */
    public function integration_default_value_is_empty_string(): void
    {
        PlaybackControls::addSetting();

        delete_option(PlaybackControls::OPTION_NAME);

        $this->assertSame('', get_option(PlaybackControls::OPTION_NAME));
    }
}
