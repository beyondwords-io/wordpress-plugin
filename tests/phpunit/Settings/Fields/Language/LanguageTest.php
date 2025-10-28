<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\Language\Language;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class LanguageTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(Language::OPTION_NAME_CODE);
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        delete_option(Language::OPTION_NAME_CODE);
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function option_name_constant_is_correct(): void
    {
        $this->assertSame(
            'beyondwords_project_language_code',
            Language::OPTION_NAME_CODE,
            'Option name constant should match expected value'
        );
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        Language::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [Language::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        Language::init();

        $this->assertTrue(
            has_action('pre_update_option_' . Language::OPTION_NAME_CODE) !== false,
            'Should register pre_update_option hook for syncing to dashboard'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        Language::addSetting();

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

        $field = $wp_settings_fields['beyondwords_voices']['voices']['beyondwords-default-language'];
        $this->assertSame('Language', $field['title']);
        $this->assertSame([Language::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function getOptions_returns_array(): void
    {
        $options = Language::getOptions();

        $this->assertIsArray($options, 'Should return an array');
    }

    /**
     * @test
     */
    public function getOptions_returns_languages_from_api(): void
    {
        $options = Language::getOptions();

        $this->assertNotEmpty($options, 'Should return languages from API');

        // Verify structure of first option
        if (count($options) > 0) {
            $firstOption = $options[0];
            $this->assertArrayHasKey('value', $firstOption, 'Option should have value key');
            $this->assertArrayHasKey('label', $firstOption, 'Option should have label key');
            $this->assertArrayHasKey('voices', $firstOption, 'Option should have voices key');
            $this->assertIsString($firstOption['value'], 'Value should be a string (language code)');
            $this->assertIsString($firstOption['label'], 'Label should be a string');
        }
    }

    /**
     * @test
     */
    public function getOptions_formats_label_with_accent(): void
    {
        $options = Language::getOptions();

        // Look for languages with accents (e.g., "English (US)", "English (UK)")
        $labelsWithAccent = array_filter($options, function ($option) {
            return strpos($option['label'], '(') !== false;
        });

        // At least some languages should have accents
        $this->assertGreaterThan(0, count($labelsWithAccent), 'Some languages should have accent information');
    }

    /**
     * @test
     */
    public function getOptions_encodes_voices_as_json(): void
    {
        $options = Language::getOptions();

        if (count($options) > 0) {
            $firstOption = $options[0];
            // The voices should be JSON-encoded
            $this->assertIsString($firstOption['voices']);
            $decoded = json_decode($firstOption['voices'], true);
            // Should be valid JSON
            $this->assertNotNull($decoded, 'Voices should be valid JSON');
        }
    }

    /**
     * @test
     */
    public function render_outputs_select_element(): void
    {
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $crawler = new Crawler($html);

        $select = $crawler->filter('select#' . Language::OPTION_NAME_CODE);
        $this->assertCount(1, $select, 'Should render a select element with correct ID');

        $this->assertSame(
            Language::OPTION_NAME_CODE,
            $select->attr('name'),
            'Select should have correct name attribute'
        );
    }

    /**
     * @test
     */
    public function render_outputs_description_text(): void
    {
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $this->assertStringContainsString(
            'Choose the default language of your posts',
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
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $crawler = new Crawler($html);
        $wrapper = $crawler->filter('div.beyondwords-setting__default-language');
        $this->assertCount(1, $wrapper, 'Should have wrapper div with correct class');
    }

    /**
     * @test
     */
    public function render_populates_options_from_api(): void
    {
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $crawler = new Crawler($html);
        $options = $crawler->filter('select option');

        $this->assertGreaterThan(
            0,
            count($options),
            'Should populate select with language options from API'
        );

        // Verify each option has required attributes
        $options->each(function (Crawler $option) {
            $this->assertNotEmpty($option->attr('value'), 'Option should have value attribute');
            $this->assertNotEmpty($option->text(), 'Option should have text content');
            $this->assertNotNull($option->attr('data-voices'), 'Option should have data-voices attribute');
        });
    }

    /**
     * @test
     */
    public function render_preselects_current_option(): void
    {
        // Get available languages
        $languages = Language::getOptions();
        if (count($languages) > 0) {
            $selectedLanguageCode = $languages[0]['value'];
            update_option(Language::OPTION_NAME_CODE, $selectedLanguageCode);

            $html = $this->captureOutput(function () {
                Language::render();
            });

            $crawler = new Crawler($html);
            $selectedOption = $crawler->filter('select option[selected]');

            $this->assertCount(1, $selectedOption, 'Should have one selected option');
            $this->assertSame(
                $selectedLanguageCode,
                $selectedOption->attr('value'),
                'Selected option should match current setting value'
            );
        }
    }

    /**
     * @test
     */
    public function render_has_placeholder_attribute(): void
    {
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $crawler = new Crawler($html);
        $select = $crawler->filter('select');

        $this->assertNotNull($select->attr('placeholder'), 'Select should have placeholder attribute');
        $this->assertSame('Add a language', $select->attr('placeholder'));
    }

    /**
     * @test
     */
    public function render_has_autocomplete_off(): void
    {
        $html = $this->captureOutput(function () {
            Language::render();
        });

        $crawler = new Crawler($html);
        $select = $crawler->filter('select');

        $this->assertSame('off', $select->attr('autocomplete'), 'Select should have autocomplete="off"');
    }

    /**
     * @test
     */
    public function integration_full_workflow(): void
    {
        // Initialize hooks
        Language::init();

        // Register setting
        Language::addSetting();

        // Get available languages
        $languages = Language::getOptions();
        $this->assertNotEmpty($languages, 'Should have languages available');

        // Save a language code
        if (count($languages) > 0) {
            $languageCode = $languages[0]['value'];
            update_option(Language::OPTION_NAME_CODE, $languageCode);

            // Retrieve and verify
            $savedLanguageCode = get_option(Language::OPTION_NAME_CODE);
            $this->assertSame($languageCode, $savedLanguageCode, 'Should save and retrieve language code correctly');

            // Render and verify selection
            $html = $this->captureOutput(function () {
                Language::render();
            });

            $this->assertStringContainsString('selected', $html, 'Rendered HTML should have selected option');
        }
    }
}
