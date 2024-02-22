<?php

/**
 * BeyondWords Add Player element.
 *
 * Text Domain: speechkit
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

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
        $addPlayer = new AddPlayer();
        $addPlayer->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('init', array($addPlayer, 'registerBlock')));
        $this->assertEquals(10, has_action('enqueue_block_editor_assets', array($addPlayer, 'addBlockEditorStylesheet')));

        $this->assertEquals(10, has_action('admin_head', array($addPlayer, 'addEditorStyles')));
        $this->assertEquals(10, has_filter('tiny_mce_before_init', array($addPlayer, 'filterTinyMceSettings')));

        $this->assertEquals(10, has_filter('mce_external_plugins', array($addPlayer, 'addPlugin')));
        $this->assertEquals(10, has_filter('mce_buttons', array($addPlayer, 'addButton')));
        $this->assertEquals(10, has_filter('mce_css', array($addPlayer, 'addStylesheet')));
    }

    /**
     * @test
     */
    public function addPlugin()
    {
        $addPlayer = new AddPlayer();

        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/tinymce.js';

        $this->assertSame(['beyondwords_player' => $url], $addPlayer->addPlugin([]));

        $this->assertSame(['beyondwords_player' => $url], $addPlayer->addPlugin(['beyondwords_player' => 'foo']));

        $this->assertSame(['foo' => 'bar', 'beyondwords_player' => $url], $addPlayer->addPlugin(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function addButton()
    {
        $addPlayer = new AddPlayer();

        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/tinymce.js';

        $this->assertSame(['beyondwords_player'], $addPlayer->addButton([]));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player'], $addPlayer->addButton(['button-1', 'button-2']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv'], $addPlayer->addButton(['button-1', 'button-2', 'wp_adv']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv', 'button-extra'], $addPlayer->addButton(['button-1', 'button-2', 'wp_adv', 'button-extra']));
    }

    /**
     * @test
     */
    public function addStylesheet()
    {
        $addPlayer = new AddPlayer();

        $url = BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/AddPlayer/AddPlayer.css';

        $this->assertSame(sprintf('https://example.com/style.css,%s', $url), $addPlayer->addStylesheet('https://example.com/style.css'));
    }

    /**
     * @test
     */
    public function playerPreviewI18nStyles()
    {
        $addPlayer = new AddPlayer();

        $expect = "iframe [data-beyondwords-player]:empty:after, .edit-post-visual-editor [data-beyondwords-player]:empty:after { content: 'Player placeholder: The position of the audio player.'; }";

        $this->assertSame($expect, $addPlayer->playerPreviewI18nStyles());
    }

    /**
     * @test
     */
    public function filterTinyMceSettings()
    {
        $addPlayer = new AddPlayer();

        // No existing styles
        $settings = $addPlayer->filterTinyMceSettings([]);
        $this->assertSame($addPlayer->playerPreviewI18nStyles() . ' ', $settings['content_style']);

        // Existing styles
        $settings = $addPlayer->filterTinyMceSettings(['content_style' => 'p { color: red; }']);
        $this->assertSame('p { color: red; } ' . $addPlayer->playerPreviewI18nStyles() . ' ', $settings['content_style']);
    }

    /**
     * @test
     */
    public function addEditorStyles()
    {
        $addPlayer = new AddPlayer();

        $addPlayer->addEditorStyles();

        $html = $this->getActualOutput();

        $this->assertSame('<style>' . $addPlayer->playerPreviewI18nStyles() . '</style>', $html);
    }
}
