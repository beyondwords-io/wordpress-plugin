<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerColors\PlayerColors;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class PlayerColorsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Clean up all player color options before each test
        delete_option(PlayerColors::OPTION_NAME_THEME);
        delete_option(PlayerColors::OPTION_NAME_LIGHT_THEME);
        delete_option(PlayerColors::OPTION_NAME_DARK_THEME);
        delete_option(PlayerColors::OPTION_NAME_VIDEO_THEME);

        // Set up required API credentials for any tests that might need them
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Clean up options
        delete_option(PlayerColors::OPTION_NAME_THEME);
        delete_option(PlayerColors::OPTION_NAME_LIGHT_THEME);
        delete_option(PlayerColors::OPTION_NAME_DARK_THEME);
        delete_option(PlayerColors::OPTION_NAME_VIDEO_THEME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_hooks(): void
    {
        PlayerColors::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [PlayerColors::class, 'addPlayerThemeSetting']),
            'Should register addPlayerThemeSetting on admin_init'
        );

        $this->assertEquals(
            10,
            has_action('admin_init', [PlayerColors::class, 'addPlayerColorsSetting']),
            'Should register addPlayerColorsSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_hooks_for_all_theme_options(): void
    {
        PlayerColors::init();

        $this->assertTrue(
            has_action('pre_update_option_' . PlayerColors::OPTION_NAME_THEME),
            'Should register pre_update_option hook for player theme'
        );

        $this->assertTrue(
            has_action('pre_update_option_' . PlayerColors::OPTION_NAME_LIGHT_THEME),
            'Should register pre_update_option hook for light theme'
        );

        $this->assertTrue(
            has_action('pre_update_option_' . PlayerColors::OPTION_NAME_DARK_THEME),
            'Should register pre_update_option hook for dark theme'
        );

        $this->assertTrue(
            has_action('pre_update_option_' . PlayerColors::OPTION_NAME_VIDEO_THEME),
            'Should register pre_update_option hook for video theme'
        );
    }

    /**
     * @test
     */
    public function addPlayerThemeSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        PlayerColors::addPlayerThemeSetting();

        $this->assertArrayHasKey(
            PlayerColors::OPTION_NAME_THEME,
            $wp_registered_settings,
            'Should register the player theme setting'
        );

        $setting = $wp_registered_settings[PlayerColors::OPTION_NAME_THEME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('', $setting['default']);
    }

    /**
     * @test
     */
    public function addPlayerThemeSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        PlayerColors::addPlayerThemeSetting();

        $this->assertArrayHasKey(
            'beyondwords-player-theme',
            $wp_settings_fields['beyondwords_player']['styling'],
            'Should register the settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-player-theme'];

        $this->assertSame('beyondwords-player-theme', $field['id']);
        $this->assertSame('Player theme', $field['title']);
        $this->assertSame([PlayerColors::class, 'renderPlayerThemeSetting'], $field['callback']);
    }

    /**
     * @test
     */
    public function addPlayerColorsSetting_registers_light_theme_setting(): void
    {
        global $wp_registered_settings;

        PlayerColors::addPlayerColorsSetting();

        $this->assertArrayHasKey(
            PlayerColors::OPTION_NAME_LIGHT_THEME,
            $wp_registered_settings,
            'Should register light theme colors setting'
        );

        $setting = $wp_registered_settings[PlayerColors::OPTION_NAME_LIGHT_THEME];

        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertArrayHasKey('default', $setting);
        $this->assertIsArray($setting['default']);
        $this->assertArrayHasKey('background_color', $setting['default']);
        $this->assertArrayHasKey('icon_color', $setting['default']);
        $this->assertArrayHasKey('text_color', $setting['default']);
        $this->assertArrayHasKey('highlight_color', $setting['default']);
        $this->assertSame([PlayerColors::class, 'sanitizeColorsArray'], $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addPlayerColorsSetting_registers_dark_theme_setting(): void
    {
        global $wp_registered_settings;

        PlayerColors::addPlayerColorsSetting();

        $this->assertArrayHasKey(
            PlayerColors::OPTION_NAME_DARK_THEME,
            $wp_registered_settings,
            'Should register dark theme colors setting'
        );

        $setting = $wp_registered_settings[PlayerColors::OPTION_NAME_DARK_THEME];
        $this->assertSame([PlayerColors::class, 'sanitizeColorsArray'], $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addPlayerColorsSetting_registers_video_theme_setting(): void
    {
        global $wp_registered_settings;

        PlayerColors::addPlayerColorsSetting();

        $this->assertArrayHasKey(
            PlayerColors::OPTION_NAME_VIDEO_THEME,
            $wp_registered_settings,
            'Should register video theme colors setting'
        );

        $setting = $wp_registered_settings[PlayerColors::OPTION_NAME_VIDEO_THEME];

        // Video theme has no highlight_color
        $this->assertArrayHasKey('background_color', $setting['default']);
        $this->assertArrayHasKey('icon_color', $setting['default']);
        $this->assertArrayHasKey('text_color', $setting['default']);
        $this->assertArrayNotHasKey('highlight_color', $setting['default']);
        $this->assertSame([PlayerColors::class, 'sanitizeColorsArray'], $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addPlayerColorsSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        PlayerColors::addPlayerColorsSetting();

        $this->assertArrayHasKey(
            'beyondwords-player-colors',
            $wp_settings_fields['beyondwords_player']['styling'],
            'Should register the player colors settings field'
        );

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-player-colors'];

        $this->assertSame('beyondwords-player-colors', $field['id']);
        $this->assertSame('Player colors', $field['title']);
        $this->assertSame([PlayerColors::class, 'renderPlayerColorsSetting'], $field['callback']);
    }

    /**
     * @test
     */
    public function getPlayerThemeOptions_returns_correct_structure(): void
    {
        $options = PlayerColors::getPlayerThemeOptions();

        $this->assertIsArray($options);
        $this->assertCount(3, $options);

        // Check light theme option
        $this->assertSame('light', $options[0]['value']);
        $this->assertSame('Light (default)', $options[0]['label']);

        // Check dark theme option
        $this->assertSame('dark', $options[1]['value']);
        $this->assertSame('Dark', $options[1]['label']);

        // Check auto theme option
        $this->assertSame('auto', $options[2]['value']);
        $this->assertSame('Auto', $options[2]['label']);
    }

    /**
     * @test
     */
    public function renderPlayerThemeSetting_outputs_select_element(): void
    {
        $html = $this->captureOutput(function () {
            PlayerColors::renderPlayerThemeSetting();
        });

        $crawler = new Crawler($html);

        // Should have a select element with correct name
        $this->assertCount(
            1,
            $crawler->filter('select[name="' . PlayerColors::OPTION_NAME_THEME . '"]'),
            'Should render a select element with correct name attribute'
        );

        // Should have wrapper div
        $this->assertCount(
            1,
            $crawler->filter('div.beyondwords-setting__player--player-colors'),
            'Should have correct wrapper class'
        );
    }

    /**
     * @test
     */
    public function renderPlayerThemeSetting_outputs_all_theme_options(): void
    {
        $html = $this->captureOutput(function () {
            PlayerColors::renderPlayerThemeSetting();
        });

        $crawler = new Crawler($html);

        // Should have all three theme options
        $this->assertCount(
            1,
            $crawler->filter('option[value="light"]'),
            'Should have light theme option'
        );

        $this->assertCount(
            1,
            $crawler->filter('option[value="dark"]'),
            'Should have dark theme option'
        );

        $this->assertCount(
            1,
            $crawler->filter('option[value="auto"]'),
            'Should have auto theme option'
        );
    }

    /**
     * @test
     */
    public function renderPlayerThemeSetting_preselects_current_value(): void
    {
        update_option(PlayerColors::OPTION_NAME_THEME, 'dark');

        $html = $this->captureOutput(function () {
            PlayerColors::renderPlayerThemeSetting();
        });

        // WordPress's selected() function outputs " selected='selected'"
        $this->assertStringContainsString(
            "value=\"dark\"  selected='selected'",
            $html,
            'Should preselect the dark theme when it is the current value'
        );
    }

    /**
     * @test
     */
    public function renderPlayerColorsSetting_outputs_all_theme_tables(): void
    {
        // Set up default values to avoid undefined index errors
        update_option(PlayerColors::OPTION_NAME_LIGHT_THEME, [
            'background_color' => '#f5f5f5',
            'icon_color' => '#000',
            'text_color' => '#111',
            'highlight_color' => '#eee',
        ]);

        update_option(PlayerColors::OPTION_NAME_DARK_THEME, [
            'background_color' => '#000',
            'icon_color' => '#fff',
            'text_color' => '#fff',
            'highlight_color' => '#333',
        ]);

        update_option(PlayerColors::OPTION_NAME_VIDEO_THEME, [
            'background_color' => '#000',
            'icon_color' => '#fff',
            'text_color' => '#fff',
        ]);

        $html = $this->captureOutput(function () {
            PlayerColors::renderPlayerColorsSetting();
        });

        $this->assertStringContainsString(
            'Light theme settings',
            $html,
            'Should render light theme section'
        );

        $this->assertStringContainsString(
            'Dark theme settings',
            $html,
            'Should render dark theme section'
        );

        $this->assertStringContainsString(
            'Video theme settings',
            $html,
            'Should render video theme section'
        );
    }

    /**
     * @test
     */
    public function playerColorsTable_renders_correct_structure(): void
    {
        $testColors = [
            'background_color' => '#ffffff',
            'icon_color' => '#000000',
            'text_color' => '#333333',
        ];

        $html = $this->captureOutput(function () use ($testColors) {
            PlayerColors::playerColorsTable(
                'Test Theme',
                'test_theme_name',
                $testColors
            );
        });

        $crawler = new Crawler($html);

        // Should have heading
        $this->assertCount(
            1,
            $crawler->filter('h3.subheading'),
            'Should have a subheading element'
        );

        $this->assertStringContainsString(
            'Test Theme',
            $html,
            'Should contain the theme title'
        );

        // Should have color picker wrapper
        $this->assertCount(
            1,
            $crawler->filter('div.color-pickers'),
            'Should have color pickers wrapper'
        );

        // Should have rows for each color input
        $this->assertGreaterThanOrEqual(
            3,
            $crawler->filter('div.row')->count(),
            'Should have at least 3 color input rows'
        );
    }

    /**
     * @test
     * @dataProvider validColorProvider
     */
    public function sanitizeColor_handles_valid_colors_correctly(string $input, string $expected): void
    {
        $result = PlayerColors::sanitizeColor($input);

        $this->assertSame(
            $expected,
            $result,
            sprintf('Color "%s" should be sanitized to "%s"', $input, $expected)
        );
    }

    public function validColorProvider(): array
    {
        return [
            'hex with hash' => ['#ffffff', '#ffffff'],
            'hex without hash' => ['ffffff', '#ffffff'],
            'uppercase hex' => ['FFFFFF', '#ffffff'],
            'mixed case hex' => ['FfFfFf', '#ffffff'],
            'short hex without hash' => ['fff', '#fff'],
            'short hex with hash' => ['#fff', '#fff'],
            'hex with leading/trailing spaces' => ['  #ffffff  ', '#ffffff'],
            'rgb color' => ['rgb(255, 255, 255)', 'rgb(255, 255, 255)'],
            'rgba color' => ['rgba(255, 255, 255, 0.5)', 'rgba(255, 255, 255, 0.5)'],
            'empty string' => ['', ''],
        ];
    }

    /**
     * @test
     */
    public function sanitizeColor_prepends_hash_to_hex_values(): void
    {
        $this->assertSame('#abc123', PlayerColors::sanitizeColor('abc123'));
        $this->assertSame('#000000', PlayerColors::sanitizeColor('000000'));
        $this->assertSame('#fff', PlayerColors::sanitizeColor('fff'));
    }

    /**
     * @test
     */
    public function sanitizeColor_lowercases_values(): void
    {
        $this->assertSame('#abcdef', PlayerColors::sanitizeColor('#ABCDEF'));
        $this->assertSame('#abcdef', PlayerColors::sanitizeColor('ABCDEF'));
    }

    /**
     * @test
     */
    public function sanitizeColor_trims_whitespace(): void
    {
        $this->assertSame('#ffffff', PlayerColors::sanitizeColor('  #ffffff  '));
        $this->assertSame('#ffffff', PlayerColors::sanitizeColor("\t#ffffff\n"));
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_returns_empty_array_for_non_array_input(): void
    {
        $this->assertSame([], PlayerColors::sanitizeColorsArray(null));
        $this->assertSame([], PlayerColors::sanitizeColorsArray('string'));
        $this->assertSame([], PlayerColors::sanitizeColorsArray(123));
        $this->assertSame([], PlayerColors::sanitizeColorsArray(true));
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_sanitizes_all_required_colors(): void
    {
        $input = [
            'background_color' => 'FFFFFF',
            'icon_color' => '000000',
            'text_color' => 'abc123',
        ];

        $result = PlayerColors::sanitizeColorsArray($input);

        $this->assertSame('#ffffff', $result['background_color']);
        $this->assertSame('#000000', $result['icon_color']);
        $this->assertSame('#abc123', $result['text_color']);
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_handles_highlight_color_when_present(): void
    {
        $input = [
            'background_color' => '#ffffff',
            'icon_color' => '#000000',
            'text_color' => '#333333',
            'highlight_color' => 'eeeeee',
        ];

        $result = PlayerColors::sanitizeColorsArray($input);

        $this->assertArrayHasKey('highlight_color', $result);
        $this->assertSame('#eeeeee', $result['highlight_color']);
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_omits_highlight_color_when_empty(): void
    {
        $input = [
            'background_color' => '#ffffff',
            'icon_color' => '#000000',
            'text_color' => '#333333',
            'highlight_color' => '',
        ];

        $result = PlayerColors::sanitizeColorsArray($input);

        // highlight_color will still be in the array but will be an empty string
        $this->assertSame('', $result['highlight_color']);
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_handles_missing_color_values(): void
    {
        $input = [
            'background_color' => '#ffffff',
            'icon_color' => '',
            'text_color' => '',
            // All keys present but some empty
        ];

        $result = PlayerColors::sanitizeColorsArray($input);

        // Should set empty colors to empty string
        $this->assertSame('#ffffff', $result['background_color']);
        $this->assertSame('', $result['icon_color']);
        $this->assertSame('', $result['text_color']);
    }

    /**
     * @test
     */
    public function sanitizeColorsArray_handles_null_color_values(): void
    {
        $input = [
            'background_color' => null,
            'icon_color' => null,
            'text_color' => null,
        ];

        $result = PlayerColors::sanitizeColorsArray($input);

        $this->assertSame('', $result['background_color']);
        $this->assertSame('', $result['icon_color']);
        $this->assertSame('', $result['text_color']);
    }

    /**
     * @test
     */
    public function integration_setting_and_retrieving_player_theme(): void
    {
        // Register the setting
        PlayerColors::addPlayerThemeSetting();

        // Update the option
        update_option(PlayerColors::OPTION_NAME_THEME, 'dark');

        // Retrieve and verify
        $this->assertSame('dark', get_option(PlayerColors::OPTION_NAME_THEME));
    }

    /**
     * @test
     */
    public function integration_setting_and_retrieving_light_theme_colors(): void
    {
        // Register the setting
        PlayerColors::addPlayerColorsSetting();

        $colors = [
            'background_color' => 'ffffff',
            'icon_color' => '000000',
            'text_color' => '333333',
            'highlight_color' => 'eeeeee',
        ];

        // Update the option (will trigger sanitization)
        update_option(PlayerColors::OPTION_NAME_LIGHT_THEME, $colors);

        // Retrieve and verify sanitization occurred
        $saved = get_option(PlayerColors::OPTION_NAME_LIGHT_THEME);

        $this->assertSame('#ffffff', $saved['background_color']);
        $this->assertSame('#000000', $saved['icon_color']);
        $this->assertSame('#333333', $saved['text_color']);
        $this->assertSame('#eeeeee', $saved['highlight_color']);
    }

    /**
     * @test
     */
    public function integration_all_color_settings_work_independently(): void
    {
        PlayerColors::addPlayerColorsSetting();

        $lightColors = [
            'background_color' => '#f5f5f5',
            'icon_color' => '#000',
            'text_color' => '#111',
        ];

        $darkColors = [
            'background_color' => '#000',
            'icon_color' => '#fff',
            'text_color' => '#fff',
        ];

        $videoColors = [
            'background_color' => '#222',
            'icon_color' => '#eee',
            'text_color' => '#eee',
        ];

        update_option(PlayerColors::OPTION_NAME_LIGHT_THEME, $lightColors);
        update_option(PlayerColors::OPTION_NAME_DARK_THEME, $darkColors);
        update_option(PlayerColors::OPTION_NAME_VIDEO_THEME, $videoColors);

        $this->assertSame('#f5f5f5', get_option(PlayerColors::OPTION_NAME_LIGHT_THEME)['background_color']);
        $this->assertSame('#000', get_option(PlayerColors::OPTION_NAME_DARK_THEME)['background_color']);
        $this->assertSame('#222', get_option(PlayerColors::OPTION_NAME_VIDEO_THEME)['background_color']);
    }
}
