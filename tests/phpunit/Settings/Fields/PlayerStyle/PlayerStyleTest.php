<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
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
        $this->_instance = new PlayerStyle();

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

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
        $playerStyle = new PlayerStyle();
        $playerStyle->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array($playerStyle, 'addSetting')));
        $this->assertTrue(has_action('pre_update_option_beyondwords_player_style'));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        $this->_instance->addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-player-style', $wp_settings_fields['beyondwords_player']['styling']);

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-player-style'];

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
}
