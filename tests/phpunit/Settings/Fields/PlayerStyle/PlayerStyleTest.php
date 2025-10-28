<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class SettingsPlayerStyleTest extends TestCase
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
        PlayerStyle::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(PlayerStyle::class, 'addSetting')));
        $this->assertTrue(has_action('pre_update_option_beyondwords_player_style'));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        PlayerStyle::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-player-style', $wp_settings_fields['beyondwords_player']['styling']);

        $field = $wp_settings_fields['beyondwords_player']['styling']['beyondwords-player-style'];

        $this->assertSame('beyondwords-player-style', $field['id']);
        $this->assertSame('Player style', $field['title']);
        $this->assertSame(array(PlayerStyle::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $html = $this->captureOutput(function () {
            PlayerStyle::render();
        });

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('select[name="beyondwords_player_style"]'));
        $this->assertCount(1, $crawler->filter('select[name="beyondwords_player_style"] option[value="standard"]'));
        $this->assertCount(1, $crawler->filter('select[name="beyondwords_player_style"] option[value="small"]'));
        $this->assertCount(1, $crawler->filter('select[name="beyondwords_player_style"] option[value="video"]'));
    }
}
