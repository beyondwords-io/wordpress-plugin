<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Core\ApiClient;
use \Symfony\Component\DomCrawler\Crawler;

class SettingsPlayerStyleTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle
     */
    private $_instance;


    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $apiClient = new ApiClient();
        $this->_instance = new PlayerStyle($apiClient);

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
        $this->assertArrayHasKey('beyondwords-player-style', $wp_settings_fields['beyondwords_player']['player']);

        $field = $wp_settings_fields['beyondwords_player']['player']['beyondwords-player-style'];

        $this->assertSame('beyondwords-player-style', $field['id']);
        $this->assertSame('Player style', $field['title']);
        $this->assertSame(array($this->_instance, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $this->_instance->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $field = $crawler->filter('select[name="beyondwords_player_style"]');
        $field = $crawler->filter('select[name="beyondwords_player_style"] option[value="standard"]');
        $field = $crawler->filter('select[name="beyondwords_player_style"] option[value="small"]');
        $field = $crawler->filter('select[name="beyondwords_player_style"] option[value="video"]');

        $this->assertCount(1, $field);
    }

    /**
     * @test
     */
    public function getCachedPlayerStyles()
    {
        $playerStyles = [
            'standard' => [
                'value' => 'standard',
                'label' => 'Standard',
                'default' => false,
            ]
        ];

        set_transient('beyondwords_player_styles[1234]', $playerStyles);
        $this->assertEquals($playerStyles, $this->_instance->getCachedPlayerStyles('1234'));

        set_transient('beyondwords_player_styles[1234]', 'Not an array');
        $this->assertEquals([], $this->_instance->getCachedPlayerStyles('1234'));

        delete_transient('beyondwords_player_styles[1234]');
        $this->assertEquals([], $this->_instance->getCachedPlayerStyles('1234'));
    }
}
