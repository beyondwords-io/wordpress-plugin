<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Pronunciations\Pronunciations;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-pronunciations
 */
class PronunciationsTabTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Pronunciations\Pronunciations
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        Pronunciations::init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array(Pronunciations::class, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_sections;
        $wp_settings_sections = null;

        Pronunciations::addSettingsSection();

        $this->assertArrayHasKey('beyondwords_pronunciations', $wp_settings_sections);
        $this->assertArrayHasKey('pronunciations', $wp_settings_sections['beyondwords_pronunciations']);
        // $this->assertArrayHasKey('player', $wp_settings_fields['beyondwords_player']);

        $this->assertArrayHasKey('beyondwords_pronunciations', $wp_settings_sections);
        $this->assertArrayHasKey('pronunciations', $wp_settings_sections['beyondwords_pronunciations']);
        $this->assertSame('pronunciations', $wp_settings_sections['beyondwords_pronunciations']['pronunciations']['id']);
        $this->assertSame('Pronunciations', $wp_settings_sections['beyondwords_pronunciations']['pronunciations']['title']);
        $this->assertSame([Pronunciations::class, 'sectionCallback'], $wp_settings_sections['beyondwords_pronunciations']['pronunciations']['callback']);
    }

    /**
     * @test
     */
    public function sectionCallback_outputs_description()
    {
        $html = $this->captureOutput(function () {
            Pronunciations::sectionCallback();
        });

        $this->assertStringContainsString(
            'Create a custom pronunciation rule for any word or phrase',
            $html
        );
        $this->assertStringContainsString('Manage pronunciations', $html);
        $this->assertStringContainsString('tab=rules', $html);
        $this->assertStringContainsString((string)BEYONDWORDS_TESTS_PROJECT_ID, $html);
    }
}
