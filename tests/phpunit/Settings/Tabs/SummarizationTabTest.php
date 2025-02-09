<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Summarization\Summarization;

/**
 * @group settings
 * @group settings-tabs
 * @group settings-tabs-summarization
 */
class SummarizationTabTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Tabs\Summarization\Summarization
     * @static
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        wp_cache_delete('beyondwords_settings_errors', 'beyondwords');

        $this->_instance = new Summarization();

        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);
        update_option('beyondwords_valid_api_connection', gmdate(\DateTime::ATOM), false);
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
    public function init()
    {
        $this->_instance->init();

        // Actions
        $this->assertEquals(5, has_action('admin_init', array($this->_instance, 'addSettingsSection')));
    }

    /**
     * @test
     */
    public function addSettingsSection()
    {
        global $wp_settings_sections;
        $wp_settings_sections = null;

        $this->_instance->addSettingsSection();

        $this->assertArrayHasKey('beyondwords_summarization', $wp_settings_sections);
        $this->assertArrayHasKey('summarization', $wp_settings_sections['beyondwords_summarization']);
        // $this->assertArrayHasKey('player', $wp_settings_fields['beyondwords_player']);

        $this->assertArrayHasKey('beyondwords_summarization', $wp_settings_sections);
        $this->assertArrayHasKey('summarization', $wp_settings_sections['beyondwords_summarization']);
        $this->assertSame('summarization', $wp_settings_sections['beyondwords_summarization']['summarization']['id']);
        $this->assertSame('Summarization', $wp_settings_sections['beyondwords_summarization']['summarization']['title']);
        $this->assertSame([$this->_instance, 'sectionCallback'], $wp_settings_sections['beyondwords_summarization']['summarization']['callback']);
    }
}
