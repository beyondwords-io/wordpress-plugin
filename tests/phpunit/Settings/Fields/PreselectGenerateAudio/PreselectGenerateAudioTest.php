<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio;
use \Symfony\Component\DomCrawler\Crawler;

/**
 * @group settings
 */
class PreselectGenerateAudioTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio
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
        PreselectGenerateAudio::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_init', array(PreselectGenerateAudio::class, 'addSetting')));
        $this->assertEquals(10, has_action('admin_enqueue_scripts', array(PreselectGenerateAudio::class, 'enqueueScripts')));
    }

    /**
     * @test
     */
    public function addSetting()
    {
        global $wp_settings_fields;

        PreselectGenerateAudio::addSetting();

        // Check for add_settings_field() result
        $this->assertArrayHasKey('beyondwords-preselect', $wp_settings_fields['beyondwords_content']['content']);

        $field = $wp_settings_fields['beyondwords_content']['content']['beyondwords-preselect'];

        $this->assertSame('beyondwords-preselect', $field['id']);
        $this->assertSame('Preselect ‘Generate audio’', $field['title']);
        $this->assertSame(array(PreselectGenerateAudio::class, 'render'), $field['callback']);
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

        $html = $this->captureOutput(function () {
            PreselectGenerateAudio::render();
        });

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

        $this->assertNull($wp_scripts);

        PreselectGenerateAudio::enqueueScripts( null );
        $this->assertNull($wp_scripts);

        PreselectGenerateAudio::enqueueScripts( 'edit.php' );
        $this->assertNull($wp_scripts);

        PreselectGenerateAudio::enqueueScripts( 'post.php' );
        $this->assertContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts = null;

        PreselectGenerateAudio::enqueueScripts( 'post-new.php' );
        $this->assertContains('beyondwords-settings--preselect-post', $wp_scripts->queue);

        $wp_scripts = null;
    }
}
