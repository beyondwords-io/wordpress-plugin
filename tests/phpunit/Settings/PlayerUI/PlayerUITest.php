<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use \Symfony\Component\DomCrawler\Crawler;

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
        $this->_instance = new PlayerUI();

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
        $this->assertArrayHasKey('beyondwords-player-ui', $wp_settings_fields['beyondwords_player']['player']);

        $field = $wp_settings_fields['beyondwords_player']['player']['beyondwords-player-ui'];

        $this->assertSame('beyondwords-player-ui', $field['id']);
        $this->assertSame('Player UI', $field['title']);
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

        $field = $crawler->filter('select[name="beyondwords_player_ui"]');
        $this->assertCount(1, $field);

        $this->assertCount(3, $field->filter('option'));

        $this->assertSame('enabled', $field->filter('option:nth-child(1)')->attr('value'));
        $this->assertSame('Enabled', $field->filter('option:nth-child(1)')->text());
        $this->assertSame('headless', $field->filter('option:nth-child(2)')->attr('value'));
        $this->assertSame('Headless', $field->filter('option:nth-child(2)')->text());
        $this->assertSame('disabled', $field->filter('option:nth-child(3)')->attr('value'));
        $this->assertSame('Disabled', $field->filter('option:nth-child(3)')->text());
    }

    /**
     * @test
     */
    public function enqueueScripts()
    {
        global $wp_scripts;

        $this->assertNull($wp_scripts);

        $this->_instance->enqueueScripts( null );
        $this->assertNull($wp_scripts);

        $this->_instance->enqueueScripts( 'edit.php' );
        $this->assertNull($wp_scripts);

        $this->_instance->enqueueScripts( 'post.php' );
        $this->assertNull($wp_scripts);

        $this->_instance->enqueueScripts( 'post-new.php' );
        $this->assertNull($wp_scripts);

        $this->_instance->enqueueScripts( 'settings_page_beyondwords' );
        $this->assertContains('beyondwords-settings--player-ui', $wp_scripts->queue);

        $wp_scripts = null;
    }
}
