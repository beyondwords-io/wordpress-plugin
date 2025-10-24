<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\Voice\BodyVoice;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 * @group settings-fields
 */
class BodyVoiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option(BodyVoice::OPTION_NAME);
        delete_option('beyondwords_project_language_code');
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    public function tearDown(): void
    {
        delete_option(BodyVoice::OPTION_NAME);
        delete_option('beyondwords_project_language_code');
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
            'beyondwords_project_body_voice_id',
            BodyVoice::OPTION_NAME,
            'Option name constant should match expected value'
        );
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        BodyVoice::init();

        $this->assertEquals(
            10,
            has_action('admin_init', [BodyVoice::class, 'addSetting']),
            'Should register addSetting on admin_init'
        );
    }

    /**
     * @test
     */
    public function init_registers_pre_update_option_hook(): void
    {
        BodyVoice::init();

        $this->assertTrue(
            has_action('pre_update_option_' . BodyVoice::OPTION_NAME) !== false,
            'Should register pre_update_option hook for syncing to dashboard'
        );
    }

    /**
     * @test
     */
    public function addSetting_registers_setting_correctly(): void
    {
        global $wp_registered_settings;

        BodyVoice::addSetting();

        $this->assertArrayHasKey(
            BodyVoice::OPTION_NAME,
            $wp_registered_settings,
            'Should register the body voice setting'
        );

        $setting = $wp_registered_settings[BodyVoice::OPTION_NAME];
        $this->assertSame('beyondwords_voices_settings', $setting['group']);
        $this->assertSame('absint', $setting['sanitize_callback']);
    }

    /**
     * @test
     */
    public function addSetting_registers_settings_field(): void
    {
        global $wp_settings_fields;

        BodyVoice::addSetting();

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

        $field = $wp_settings_fields['beyondwords_voices']['voices']['beyondwords-body-voice'];
        $this->assertSame('Body voice', $field['title']);
        $this->assertSame([BodyVoice::class, 'render'], $field['callback']);
    }

    /**
     * @test
     */
    public function render_outputs_select_element(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $crawler = new Crawler($html);

        $select = $crawler->filter('select#' . BodyVoice::OPTION_NAME);
        $this->assertCount(1, $select, 'Should render a select element with correct ID');

        $this->assertSame(
            BodyVoice::OPTION_NAME,
            $select->attr('name'),
            'Select should have correct name attribute'
        );

        $this->assertSame(
            'beyondwords_project_voice',
            $select->attr('class'),
            'Select should have correct class'
        );
    }

    /**
     * @test
     */
    public function render_outputs_description_text(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $this->assertStringContainsString(
            'Choose the default voice for your article body sections.',
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
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $crawler = new Crawler($html);
        $wrapper = $crawler->filter('div.beyondwords-setting__body-voice');
        $this->assertCount(1, $wrapper, 'Should have wrapper div with correct class');
    }

    /**
     * @test
     */
    public function render_outputs_loading_spinner(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $crawler = new Crawler($html);
        $spinner = $crawler->filter('img.beyondwords-settings__loader');
        $this->assertCount(1, $spinner, 'Should have loading spinner image');
        $this->assertStringContainsString('display:none', $spinner->attr('style'), 'Spinner should be hidden by default');
    }

    /**
     * @test
     */
    public function render_populates_options_from_api(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $crawler = new Crawler($html);
        $options = $crawler->filter('select option');

        $this->assertGreaterThan(
            0,
            count($options),
            'Should populate select with voice options from API'
        );

        // Verify each option has required attributes
        $options->each(function (Crawler $option) {
            $this->assertNotEmpty($option->attr('value'), 'Option should have value attribute');
            $this->assertNotEmpty($option->text(), 'Option should have text content');
            $this->assertNotNull($option->attr('data-language-code'), 'Option should have data-language-code attribute');
        });
    }

    /**
     * @test
     */
    public function render_preselects_current_option(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Get first available voice
        $voices = BodyVoice::getOptions();
        if (count($voices) > 0) {
            $selectedVoiceId = $voices[0]['value'];
            update_option(BodyVoice::OPTION_NAME, $selectedVoiceId);

            $html = $this->captureOutput(function () {
                BodyVoice::render();
            });

            $crawler = new Crawler($html);
            $selectedOption = $crawler->filter('select option[selected]');

            $this->assertCount(1, $selectedOption, 'Should have one selected option');
            $this->assertSame(
                (string)$selectedVoiceId,
                $selectedOption->attr('value'),
                'Selected option should match current setting value'
            );
        }
    }

    /**
     * @test
     */
    public function render_handles_no_voices_gracefully(): void
    {
        // Don't set language code or API credentials
        delete_option('beyondwords_project_language_code');

        $html = $this->captureOutput(function () {
            BodyVoice::render();
        });

        $crawler = new Crawler($html);
        $select = $crawler->filter('select#' . BodyVoice::OPTION_NAME);
        $this->assertCount(1, $select, 'Should still render select element');

        $options = $crawler->filter('select option');
        $this->assertSame(0, count($options), 'Should have no options when no voices available');
    }

    /**
     * @test
     */
    public function sanitization_uses_absint(): void
    {
        global $wp_registered_settings;

        BodyVoice::addSetting();

        $setting = $wp_registered_settings[BodyVoice::OPTION_NAME];
        $sanitizeCallback = $setting['sanitize_callback'];

        $this->assertSame('absint', $sanitizeCallback, 'Should use absint for sanitization');

        // Test absint behavior
        $this->assertSame(123, absint('123'));
        $this->assertSame(123, absint(123));
        $this->assertSame(0, absint('invalid'));
        $this->assertSame(5, absint(-5)); // absint returns absolute value
    }

    /**
     * @test
     */
    public function integration_full_workflow(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Initialize hooks
        BodyVoice::init();

        // Register setting
        BodyVoice::addSetting();

        // Get available voices
        $voices = BodyVoice::getOptions();
        $this->assertNotEmpty($voices, 'Should have voices available');

        // Save a voice ID
        if (count($voices) > 0) {
            $voiceId = $voices[0]['value'];
            update_option(BodyVoice::OPTION_NAME, $voiceId);

            // Retrieve and verify
            $savedVoiceId = get_option(BodyVoice::OPTION_NAME);
            $this->assertSame($voiceId, $savedVoiceId, 'Should save and retrieve voice ID correctly');

            // Render and verify selection
            $html = $this->captureOutput(function () {
                BodyVoice::render();
            });

            $this->assertStringContainsString('selected', $html, 'Rendered HTML should have selected option');
        }
    }

    /**
     * @test
     */
    public function extends_voice_class(): void
    {
        $reflection = new \ReflectionClass(BodyVoice::class);
        $parent = $reflection->getParentClass();

        $this->assertNotFalse($parent, 'BodyVoice should extend a parent class');
        $this->assertSame(
            'Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice',
            $parent->getName(),
            'BodyVoice should extend Voice class'
        );
    }

    /**
     * @test
     */
    public function getOptions_method_inherited_from_parent(): void
    {
        $reflection = new \ReflectionClass(BodyVoice::class);
        $method = $reflection->getMethod('getOptions');

        // Method should be defined in parent class
        $this->assertSame(
            'Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice',
            $method->getDeclaringClass()->getName(),
            'getOptions should be inherited from Voice parent class'
        );
    }
}
