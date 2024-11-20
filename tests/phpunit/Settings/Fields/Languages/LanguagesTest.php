<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class LanguagesTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $languages = new Languages();
        $languages->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array($languages, 'addSetting')));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        $languages = new Languages();
        $languages->addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-languages', $wp_settings_fields['beyondwords_advanced']['advanced']);

        $field = $wp_settings_fields['beyondwords_advanced']['advanced']['beyondwords-languages'];

        $this->assertSame('beyondwords-languages', $field['id']);
        $this->assertSame('Multiple languages', $field['title']);
        $this->assertSame(array($languages, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $languages = new Languages();
        $languages->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $field = $crawler->filter('select[name="beyondwords_languages[]"]');
        $this->assertCount(1, $field);

        $this->assertSame('beyondwords_languages', $field->attr('id'));
        $this->assertSame('beyondwords_languages[]', $field->attr('name'));
        $this->assertSame('Add a language', $field->attr('placeholder'));
        $this->assertSame('multiple', $field->attr('multiple'));
        $this->assertSame('width: 500px;', $field->attr('style'));

        $this->assertCount(3, $field->filter('option'));

        // Options should be populated from Mock API response
        $this->assertSame('1', $field->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Language 1', $field->filter('option:nth-child(1)')->text());
        $this->assertSame('2', $field->filter('option:nth-child(1)')->attr('data-default-voice-title-id'));
        $this->assertSame('95', $field->filter('option:nth-child(1)')->attr('data-default-voice-title-speaking-rate'));
        $this->assertSame('3', $field->filter('option:nth-child(1)')->attr('data-default-voice-body-id'));
        $this->assertSame('105', $field->filter('option:nth-child(1)')->attr('data-default-voice-body-speaking-rate'));

        $this->assertSame('2', $field->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Language 2', $field->filter('option:nth-child(2)')->text());
        $this->assertSame('2', $field->filter('option:nth-child(2)')->attr('data-default-voice-title-id'));
        $this->assertSame('90', $field->filter('option:nth-child(2)')->attr('data-default-voice-title-speaking-rate'));
        $this->assertSame('3', $field->filter('option:nth-child(2)')->attr('data-default-voice-body-id'));
        $this->assertSame('110', $field->filter('option:nth-child(2)')->attr('data-default-voice-body-speaking-rate'));

        $this->assertSame('3', $field->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Language 3', $field->filter('option:nth-child(3)')->text());
        $this->assertSame('2', $field->filter('option:nth-child(3)')->attr('data-default-voice-title-id'));
        $this->assertSame('85', $field->filter('option:nth-child(3)')->attr('data-default-voice-title-speaking-rate'));
        $this->assertSame('3', $field->filter('option:nth-child(3)')->attr('data-default-voice-body-id'));
        $this->assertSame('115', $field->filter('option:nth-child(3)')->attr('data-default-voice-body-speaking-rate'));

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }
}
