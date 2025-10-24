<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlaybackFromSegments\PlaybackFromSegments;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class PlaybackFromSegmentsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(PlaybackFromSegments::OPTION_NAME);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(PlaybackFromSegments::OPTION_NAME);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function default_value_constant_is_false(): void
    {
        $this->assertFalse(PlaybackFromSegments::DEFAULT_VALUE);
    }

    /**
     * @test
     */
    public function option_name_constant_is_correct(): void
    {
        $this->assertSame(
            'beyondwords_player_clickable_sections',
            PlaybackFromSegments::OPTION_NAME,
            'Option name constant should match expected value'
        );
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        PlaybackFromSegments::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [PlaybackFromSegments::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        PlaybackFromSegments::init();

        $this->assertTrue(
            has_action('pre_update_option_' . PlaybackFromSegments::OPTION_NAME) !== false,
            'Should register pre_update_option hook for syncing to dashboard'
        );
    }

    /**
     * @test
     */
    public function init_registers_option_filter(): void
    {
        PlaybackFromSegments::init();

        $this->assertTrue(
            has_filter('option_' . PlaybackFromSegments::OPTION_NAME) !== false,
            'Should register option filter for boolean sanitization'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        PlaybackFromSegments::addSetting();

        $this->assertArrayHasKey(
            PlaybackFromSegments::OPTION_NAME,
            $wp_registered_settings,
            'Should register the playback from segments setting'
        );

        $setting = $wp_registered_settings[PlaybackFromSegments::OPTION_NAME];
        $this->assertSame('beyondwords_player_settings', $setting['group']);
        $this->assertSame('boolean', $setting['type']);
        $this->assertFalse($setting['default']);
        $this->assertSame('rest_sanitize_boolean', $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        PlaybackFromSegments::addSetting();

        $this->assertArrayHasKey(
            'beyondwords_player',
            $wp_settings_fields,
            'Should add field to beyondwords_player page'
        );

        $this->assertArrayHasKey(
            'styling',
            $wp_settings_fields['beyondwords_player'],
            'Should add field to styling section'
        );

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-playback-from-segments'];
        $this->assertSame('Playback from segments', $field['title']);
        $this->assertSame([PlaybackFromSegments::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_checkbox_input(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);

        $checkbox = $crawler->filter('input[type="checkbox"]#' . PlaybackFromSegments::OPTION_NAME);
        $this->assertCount(1, $checkbox, 'Should render a checkbox input with correct ID');

        $this->assertSame(
            PlaybackFromSegments::OPTION_NAME,
            $checkbox->attr('name'),
            'Checkbox should have correct name attribute'
        );

        $this->assertSame('1', $checkbox->attr('value'), 'Checkbox value should be "1"');
    }

    /**
     * @test
     */
    public function render_outputs_hidden_input_for_unchecked_state(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);

        $hidden = $crawler->filter('input[type="hidden"][name="' . PlaybackFromSegments::OPTION_NAME . '"]');
        $this->assertCount(1, $hidden, 'Should render a hidden input for unchecked state');
        $this->assertSame('', $hidden->attr('value'), 'Hidden input should have empty value');
    }

    /**
     * @test
     */
    public function render_checkbox_is_checked_when_option_is_true(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, true);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);
        $checkbox = $crawler->filter('input[type="checkbox"]');

        $this->assertNotNull($checkbox->attr('checked'), 'Checkbox should be checked when option is true');
    }

    /**
     * @test
     */
    public function render_checkbox_is_unchecked_when_option_is_false(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);
        $checkbox = $crawler->filter('input[type="checkbox"]');

        $this->assertNull($checkbox->attr('checked'), 'Checkbox should not be checked when option is false');
    }

    /**
     * @test
     */
    public function render_outputs_label_text(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $this->assertStringContainsString(
            'Allow readers to listen to a paragraph by clicking or tapping on it',
            $html,
            'Should contain label text'
        );

        $crawler = new Crawler($html);
        $label = $crawler->filter('label');
        $this->assertCount(1, $label, 'Should have label element');
    }

    /**
     * @test
     */
    public function render_outputs_wrapper_div(): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);
        $wrapper = $crawler->filter('div.beyondwords-setting__player-playback-from-segments');
        $this->assertCount(1, $wrapper, 'Should have wrapper div with correct class');
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     */
    public function handles_boolean_values_correctly(bool $value): void
    {
        update_option(PlaybackFromSegments::OPTION_NAME, $value);

        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $crawler = new Crawler($html);
        $checkbox = $crawler->filter('input[type="checkbox"]');

        if ($value) {
            $this->assertNotNull($checkbox->attr('checked'), 'Checkbox should be checked for true value');
        } else {
            $this->assertNull($checkbox->attr('checked'), 'Checkbox should not be checked for false value');
        }
    }

    public function booleanValueProvider(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }

    /**
     * @test
     */
    public function integration_full_workflow(): void
    {
        // Initialize hooks
        PlaybackFromSegments::init();

        // Register setting
        PlaybackFromSegments::addSetting();

        // Enable the feature
        update_option(PlaybackFromSegments::OPTION_NAME, true);

        // Retrieve and verify
        $savedValue = get_option(PlaybackFromSegments::OPTION_NAME);
        $this->assertTrue($savedValue, 'Should save and retrieve boolean value correctly');

        // Render and verify checkbox is checked
        $html = $this->captureOutput(function () {
            PlaybackFromSegments::render();
        });

        $this->assertStringContainsString('checked', $html, 'Rendered HTML should have checked checkbox');

        // Disable the feature
        update_option(PlaybackFromSegments::OPTION_NAME, false);

        // Verify it's actually saved as false
        $disabledValue = get_option(PlaybackFromSegments::OPTION_NAME);
        $this->assertFalse($disabledValue, 'Option should be false when disabled');
    }

    /**
     * @test
     */
    public function uses_default_value_when_option_not_set(): void
    {
        delete_option(PlaybackFromSegments::OPTION_NAME);

        // WordPress get_option returns false when option doesn't exist
        $value = get_option(PlaybackFromSegments::OPTION_NAME);

        // With rest_sanitize_boolean filter, false should remain false
        $this->assertFalse($value, 'Should use false as default when option is not set');
    }

    /**
     * @test
     */
    public function sanitization_uses_rest_sanitize_boolean(): void
    {
        global $wp_registered_settings;

        PlaybackFromSegments::addSetting();

        $setting = $wp_registered_settings[PlaybackFromSegments::OPTION_NAME];
        $sanitizeCallback = $setting['sanitize_callback'];

        $this->assertSame('rest_sanitize_boolean', $sanitizeCallback, 'Should use rest_sanitize_boolean for sanitization');

        // Test rest_sanitize_boolean behavior
        $this->assertTrue(rest_sanitize_boolean('1'));
        $this->assertTrue(rest_sanitize_boolean(1));
        $this->assertTrue(rest_sanitize_boolean('true'));
        $this->assertTrue(rest_sanitize_boolean(true));
        $this->assertFalse(rest_sanitize_boolean('0'));
        $this->assertFalse(rest_sanitize_boolean(0));
        $this->assertFalse(rest_sanitize_boolean('false'));
        $this->assertFalse(rest_sanitize_boolean(false));
    }
}
