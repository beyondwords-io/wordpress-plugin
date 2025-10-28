<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Player\Player;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-player
 */
class PlayerTabTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Player\Player
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
        Player::init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array(Player::class, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_sections;
        $wp_settings_sections = null;

        Player::addSettingsSection();

        $this->assertArrayHasKey('beyondwords_player', $wp_settings_sections);
        $this->assertArrayHasKey('player', $wp_settings_sections['beyondwords_player']);
        // $this->assertArrayHasKey('player', $wp_settings_fields['beyondwords_player']);

        $this->assertArrayHasKey('beyondwords_player', $wp_settings_sections);
        $this->assertArrayHasKey('player', $wp_settings_sections['beyondwords_player']);
        $this->assertSame('player', $wp_settings_sections['beyondwords_player']['player']['id']);
        $this->assertSame('Player', $wp_settings_sections['beyondwords_player']['player']['title']);
        $this->assertSame([Player::class, 'sectionCallback'], $wp_settings_sections['beyondwords_player']['player']['callback']);

        // Verify styling section was also added
        $this->assertArrayHasKey('styling', $wp_settings_sections['beyondwords_player']);
        $this->assertSame('Styling', $wp_settings_sections['beyondwords_player']['styling']['title']);
    }

    /**
     * @test
     */
    public function sectionCallback_outputs_description()
    {
        $html = $this->captureOutput(function () {
            Player::sectionCallback();
        });

        $this->assertStringContainsString(
            'By default, these settings are applied to the BeyondWords player for all existing and future posts.',
            $html
        );

        $this->assertStringContainsString(
            'Unique player settings per-post is supported via the <a href="https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_player_sdk_params" target="_blank" rel="nofollow">beyondwords_player_sdk_params</a> filter.',
            $html
        );
    }
}
