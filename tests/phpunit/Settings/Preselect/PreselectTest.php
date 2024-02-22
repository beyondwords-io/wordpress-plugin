<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Preselect\Preselect;
use \Symfony\Component\DomCrawler\Crawler;

class PreselectTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Preselect\Preselect
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new Preselect();

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
        $this->assertArrayHasKey('beyondwords-preselect', $wp_settings_fields['beyondwords']['generate-audio']);

        $field = $wp_settings_fields['beyondwords']['generate-audio']['beyondwords-preselect'];

        $this->assertSame('beyondwords-preselect', $field['id']);
        $this->assertSame('Preselect ‘Generate audio’', $field['title']);
        $this->assertSame(array($this->_instance, 'render'), $field['callback']);
        $this->assertSame([], $field['args']);
    }

    /**
     * @test
     */
    public function render()
    {
        $parentCategory = wp_insert_category(['cat_name' => 'Parent Category']);
        $childCategory  = wp_insert_category(['cat_name' => 'Child Category', 'cat_parent' => $parentCategory]);

        update_option('beyondwords_preselect', ['post' => ['category' => [$childCategory]]]);

        $this->_instance->render();

        $html = $this->getActualOutput();

        $crawler = new Crawler($html);

        $selector = sprintf('input[type="checkbox"][name="beyondwords_preselect[post][category][]"][value="%s"]:not(:checked)', $parentCategory);
        $this->assertCount(1, $crawler->filter($selector));

        $selector = sprintf('input[type="checkbox"][name="beyondwords_preselect[post][category][]"][value="%s"]:checked', $childCategory);
        $this->assertCount(1, $crawler->filter($selector));

        delete_option('beyondwords_preselect');
    }

    /**
     * @test
     */
    public function enqueueScripts()
    {
        global $wp_scripts;

        $wp_scripts->queue = [];

        $this->_instance->enqueueScripts( null );
        $this->assertNotContains('beyondwords-settings--preselect-settings', $wp_scripts->queue);
        $this->assertNotContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts->queue = [];

        $this->_instance->enqueueScripts( 'edit.php' );
        $this->assertNotContains('beyondwords-settings--preselect-settings', $wp_scripts->queue);
        $this->assertNotContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts->queue = [];

        $this->_instance->enqueueScripts( 'settings_page_beyondwords' );
        $this->assertContains('beyondwords-settings--preselect-settings', $wp_scripts->queue);

        $wp_scripts->queue = [];

        $this->_instance->enqueueScripts( 'post.php' );
        $this->assertContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts->queue = [];

        $this->_instance->enqueueScripts( 'post-new.php' );
        $this->assertContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts->queue = [];
    }
}
