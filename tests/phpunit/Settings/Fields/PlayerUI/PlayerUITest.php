<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class PlayerUITest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI
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
        PlayerUI::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(PlayerUI::class, 'addSetting')));
        $this->assertTrue(has_action('pre_update_option_beyondwords_player_ui'));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        PlayerUI::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-player-ui', $wp_settings_fields['beyondwords_player']['player']);

        $field = $wp_settings_fields['beyondwords_player']['player']['beyondwords-player-ui'];

        $this->assertSame('beyondwords-player-ui', $field['id']);
        $this->assertSame('Player UI', $field['title']);
        $this->assertSame(array(PlayerUI::class, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        PlayerUI::render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $field = $crawler->filter('select[name="beyondwords_player_ui"]');
        $this->assertCount(1, $field);

        $this->assertCount(3, $field->filter('option'));

        $this->assertSame(PlayerUI::ENABLED, $field->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Enabled', $field->filter('option:nth-child(1)')->text());

        $this->assertSame(PlayerUI::HEADLESS, $field->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Headless', $field->filter('option:nth-child(2)')->text());

        $this->assertSame(PlayerUI::DISABLED, $field->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Disabled', $field->filter('option:nth-child(3)')->text());
    }
}
