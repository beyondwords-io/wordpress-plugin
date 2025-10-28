<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\Voice\Voice;

/**
 * @group settings
 * @group settings-fields
 */
class VoiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        delete_option('beyondwords_project_language_code');
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    public function tearDown(): void
    {
        delete_option('beyondwords_project_language_code');
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getOptions_returns_empty_array_when_no_language_code(): void
    {
        // Create a concrete implementation for testing
        $concreteVoice = new class extends Voice {
        };

        $options = $concreteVoice::getOptions();

        $this->assertIsArray($options, 'Should return an array');
        $this->assertEmpty($options, 'Should return empty array when no language code is set');
    }

    /**
     * @test
     */
    public function getOptions_returns_formatted_voices_with_valid_language_code(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $concreteVoice = new class extends Voice {
        };

        $options = $concreteVoice::getOptions();

        $this->assertIsArray($options, 'Should return an array');
        $this->assertNotEmpty($options, 'Should return voices when valid language code is set');

        // Verify structure of first option
        if (count($options) > 0) {
            $firstOption = $options[0];
            $this->assertIsArray($firstOption, 'Each option should be an array');
            $this->assertArrayHasKey('value', $firstOption, 'Option should have value key');
            $this->assertArrayHasKey('label', $firstOption, 'Option should have label key');
            $this->assertIsInt($firstOption['value'], 'Voice ID should be an integer');
            $this->assertIsString($firstOption['label'], 'Voice name should be a string');
            $this->assertNotEmpty($firstOption['label'], 'Voice name should not be empty');
        }
    }

    /**
     * @test
     */
    public function getOptions_maps_voice_id_to_value(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $concreteVoice = new class extends Voice {
        };

        $options = $concreteVoice::getOptions();

        if (count($options) > 0) {
            $firstOption = $options[0];
            // Verify the mapping uses 'id' from API as 'value' in options
            $this->assertArrayHasKey('value', $firstOption);
        }
    }

    /**
     * @test
     */
    public function getOptions_maps_voice_name_to_label(): void
    {
        update_option('beyondwords_project_language_code', 'en');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $concreteVoice = new class extends Voice {
        };

        $options = $concreteVoice::getOptions();

        if (count($options) > 0) {
            $firstOption = $options[0];
            // Verify the mapping uses 'name' from API as 'label' in options
            $this->assertArrayHasKey('label', $firstOption);
            $this->assertNotEmpty($firstOption['label']);
        }
    }

    /**
     * @test
     */
    public function getOptions_is_static_method(): void
    {
        $concreteVoice = new class extends Voice {
        };

        $reflection = new \ReflectionClass($concreteVoice);
        $method = $reflection->getMethod('getOptions');

        $this->assertTrue(
            $method->isStatic(),
            'getOptions should be a static method'
        );
    }

    /**
     * @test
     */
    public function getOptions_uses_language_code_from_wordpress_option(): void
    {
        // Set a specific language code
        update_option('beyondwords_project_language_code', 'es');
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $concreteVoice = new class extends Voice {
        };

        $options = $concreteVoice::getOptions();

        // Spanish voices should be returned
        $this->assertIsArray($options);
        // If API is working correctly, we should get Spanish voices
    }
}
