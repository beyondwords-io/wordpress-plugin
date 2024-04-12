<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\PlayerVersion\PlayerVersion;
use Beyondwords\Wordpress\Core\ApiClient;
use \Symfony\Component\DomCrawler\Crawler;

class PlayerVersionTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\PlayerVersion\PlayerVersion
     */
    private $_instance;


    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $apiClient       = new ApiClient();
        $this->_instance = new PlayerVersion($apiClient);

        update_option('beyondwords_api_key', 'write_XXXXXXXXXXXXXXXX');
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(DATE_ISO8601));
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
        $this->assertArrayHasKey('beyondwords-player-version', $wp_settings_fields['beyondwords_player']['player']);

        $field = $wp_settings_fields['beyondwords_player']['player']['beyondwords-player-version'];

        $this->assertSame('beyondwords-player-version', $field['id']);
        $this->assertSame('Player version', $field['title']);
        $this->assertSame(array($this->_instance, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);

        delete_option('beyondwords_valid_api_connection');
    }

    /**
     * @test
     */
    public function render()
    {
        $this->_instance->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $field = $crawler->filter('select[name="beyondwords_player_version"]');
        $this->assertCount(1, $field);

        $this->assertCount(2, $field->filter('option'));

        $this->assertSame('1', $field->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Latest', $field->filter('option:nth-child(1)')->text());
        $this->assertSame('0', $field->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Legacy', $field->filter('option:nth-child(2)')->text());
    }
}
