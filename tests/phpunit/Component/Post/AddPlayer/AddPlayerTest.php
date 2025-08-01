<?php

use Beyondwords\Wordpress\Component\Post\AddPlayer\AddPlayer;

class AddPlayerTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        AddPlayer::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('init', array(AddPlayer::class, 'registerBlock')));
        $this->assertEquals(10, has_action('enqueue_block_editor_assets', array(AddPlayer::class, 'addBlockEditorStylesheet')));

        $this->assertEquals(10, has_action('admin_head', array(AddPlayer::class, 'addEditorStyles')));
        $this->assertEquals(10, has_filter('tiny_mce_before_init', array(AddPlayer::class, 'filterTinyMceSettings')));

        $this->assertEquals(10, has_filter('mce_external_plugins', array(AddPlayer::class, 'addPlugin')));
        $this->assertEquals(10, has_filter('mce_buttons', array(AddPlayer::class, 'addButton')));
        $this->assertEquals(10, has_filter('mce_css', array(AddPlayer::class, 'addStylesheet')));
    }

    /**
     * @test
     */
    public function addPlugin()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/tinymce.js';

        $this->assertSame(['beyondwords_player' => $url], AddPlayer::addPlugin([]));

        $this->assertSame(['beyondwords_player' => $url], AddPlayer::addPlugin(['beyondwords_player' => 'foo']));

        $this->assertSame(['foo' => 'bar', 'beyondwords_player' => $url], AddPlayer::addPlugin(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function addButton()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/tinymce.js';

        $this->assertSame(['beyondwords_player'], AddPlayer::addButton([]));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player'], AddPlayer::addButton(['button-1', 'button-2']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv'], AddPlayer::addButton(['button-1', 'button-2', 'wp_adv']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv', 'button-extra'], AddPlayer::addButton(['button-1', 'button-2', 'wp_adv', 'button-extra']));
    }

    /**
     * @test
     */
    public function addStylesheet()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/AddPlayer.css';

        $this->assertSame(sprintf('https://example.com/style.css,%s', $url), AddPlayer::addStylesheet('https://example.com/style.css'));
    }

    /**
     * @test
     */
    public function playerPreviewI18nStyles()
    {
        $expect = "iframe [data-beyondwords-player]:empty:after, .edit-post-visual-editor [data-beyondwords-player]:empty:after { content: 'Player placeholder: The position of the audio player.'; }";

        $this->assertSame($expect, AddPlayer::playerPreviewI18nStyles());
    }

    /**
     * @test
     */
    public function filterTinyMceSettings()
    {
        // No existing styles
        $settings = AddPlayer::filterTinyMceSettings([]);
        $this->assertSame(AddPlayer::playerPreviewI18nStyles() . ' ', $settings['content_style']);

        // Existing styles
        $settings = AddPlayer::filterTinyMceSettings(['content_style' => 'p { color: red; }']);
        $this->assertSame('p { color: red; } ' . AddPlayer::playerPreviewI18nStyles() . ' ', $settings['content_style']);
    }

    /**
     * @test
     */
    public function addEditorStyles()
    {
        AddPlayer::addEditorStyles();

        $html = $this->getActualOutput();

        $this->assertSame('<style>' . AddPlayer::playerPreviewI18nStyles() . '</style>', $html);
    }
}
