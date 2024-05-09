<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;
use Beyondwords\Wordpress\Core\ApiClient;
use \Symfony\Component\DomCrawler\Crawler;

class LanguagesTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages
     */
    private $_instance;


    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $apiClient       = new ApiClient();
        $this->_instance = new Languages($apiClient);

        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addSettingsField()
    {
        global $wp_settings_fields;

        $this->_instance->addSettingsField();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-languages', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-languages'];

        $this->assertSame('beyondwords-languages', $field['id']);
        $this->assertSame('Languages', $field['title']);
        $this->assertSame(array($this->_instance, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM));

        $this->_instance->render();

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
        $this->assertSame('2', $field->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Language 2', $field->filter('option:nth-child(2)')->text());
        $this->assertSame('3', $field->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Language 3', $field->filter('option:nth-child(3)')->text());

        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
        delete_option('beyondwords_valid_api_connection');
    }
}
