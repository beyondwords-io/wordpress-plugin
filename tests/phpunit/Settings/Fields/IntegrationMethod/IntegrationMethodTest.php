<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\IntegrationMethod\IntegrationMethod;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class IntegrationMethodTest extends TestCase
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
        IntegrationMethod::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(IntegrationMethod::class, 'addSetting')));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        IntegrationMethod::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-integration-method', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-integration-method'];

        $this->assertSame('beyondwords-integration-method', $field['id']);
        $this->assertSame('Integration method', $field['title']);
        $this->assertSame(array(IntegrationMethod::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $html = $this->captureOutput(function () {
            IntegrationMethod::render();
        });

        $crawler = new Crawler($html);

        $select = $crawler->filter('select#beyondwords_integration_method[name="beyondwords_integration_method"]');
        $this->assertCount(1, $select);

        $this->assertSame('rest-api', $select->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('REST API', $select->filter('option:nth-child(1)')->text());

        $this->assertSame('client-side', $select->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Magic Embed', $select->filter('option:nth-child(2)')->text());
    }

    /**
     * @test
     */
    public function getIntegrationMethodFromSetting()
    {
        update_option('beyondwords_integration_method', 'rest-api');

        $method = IntegrationMethod::getIntegrationMethod();

        $this->assertEquals('rest-api', $method);

        update_option('beyondwords_integration_method', 'client-side');

        $method = IntegrationMethod::getIntegrationMethod();

        $this->assertEquals('client-side', $method);
    }

    /**
     * @test
     * @dataProvider getIntegrationMethodFromPostProvider
     */
    public function getIntegrationMethodFromPost($expect, $metaValue, $optionValue)
    {
        update_option('beyondwords_integration_method', $optionValue);

        $post = self::factory()->post->create_and_get([
            'meta_input' => [
                'beyondwords_generate_audio' => '1',
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                'beyondwords_integration_method' => $metaValue,
            ],
        ]);

        $method = IntegrationMethod::getIntegrationMethod($post);

        $this->assertEquals($expect, $method);

        wp_delete_post($post->ID, true);

        delete_option('beyondwords_integration_method');
    }

    public function getIntegrationMethodFromPostProvider()
    {
        return [
            'empty meta, empty option' => [
                'expect' => 'rest-api',
                'meta_value' => '',
                'option_value' => '',
            ],
            'empty meta, rest-api option' => [
                'expect' => 'rest-api',
                'meta_value' => '',
                'option_value' => 'rest-api',
            ],
            'empty meta, client-side option' => [
                'expect' => 'client-side',
                'meta_value' => '',
                'option_value' => 'client-side',
            ],
            'rest-api meta, empty option' => [
                'expect' => 'rest-api',
                'meta_value' => 'rest-api',
                'option_value' => '',
            ],
            'rest-api meta, rest-api option' => [
                'expect' => 'rest-api',
                'meta_value' => 'rest-api',
                'option_value' => 'rest-api',
            ],
            'rest-api meta, client-side option' => [
                'expect' => 'rest-api',
                'meta_value' => 'rest-api',
                'option_value' => 'client-side',
            ],
            'client-side meta, empty option' => [
                'expect' => 'client-side',
                'meta_value' => 'client-side',
                'option_value' => '',
            ],
            'client-side meta, rest-api option' => [
                'expect' => 'client-side',
                'meta_value' => 'client-side',
                'option_value' => 'rest-api',
            ],
            'client-side meta, client-side option' => [
                'expect' => 'client-side',
                'meta_value' => 'client-side',
                'option_value' => 'client-side',
            ],
            'unknown meta, unknown option' => [
                'expect' => 'rest-api',
                'meta_value' => 'unknown',
                'option_value' => 'unknown',
            ],
            'unknown meta, empty option' => [
                'expect' => 'rest-api',
                'meta_value' => 'unknown',
                'option_value' => '',
            ],
            'empty meta, unknown option' => [
                'expect' => 'rest-api',
                'meta_value' => '',
                'option_value' => 'unknown',
            ],
        ];
    }
}
