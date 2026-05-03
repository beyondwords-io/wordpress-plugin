<?php

use BeyondWords\Post\AddPlayer;

class AddPlayerTest extends TestCase
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

        $this->assertEquals(10, has_action('init', array(AddPlayer::class, 'register_block')));

        $this->assertEquals(10, has_action('admin_head', array(AddPlayer::class, 'add_editor_styles')));
        $this->assertEquals(10, has_filter('tiny_mce_before_init', array(AddPlayer::class, 'filter_tiny_mce_settings')));

        $this->assertEquals(10, has_filter('mce_external_plugins', array(AddPlayer::class, 'add_plugin')));
        $this->assertEquals(10, has_filter('mce_buttons', array(AddPlayer::class, 'add_button')));
        $this->assertEquals(10, has_filter('mce_css', array(AddPlayer::class, 'add_stylesheet')));
    }

    /**
     * @test
     */
    public function add_plugin()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/post/add-player/tinymce.js';

        $this->assertSame(['beyondwords_player' => $url], AddPlayer::add_plugin([]));

        $this->assertSame(['beyondwords_player' => $url], AddPlayer::add_plugin(['beyondwords_player' => 'foo']));

        $this->assertSame(['foo' => 'bar', 'beyondwords_player' => $url], AddPlayer::add_plugin(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function add_button()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/post/add-player/tinymce.js';

        $this->assertSame(['beyondwords_player'], AddPlayer::add_button([]));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player'], AddPlayer::add_button(['button-1', 'button-2']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv'], AddPlayer::add_button(['button-1', 'button-2', 'wp_adv']));

        $this->assertSame(['button-1', 'button-2', 'beyondwords_player', 'wp_adv', 'button-extra'], AddPlayer::add_button(['button-1', 'button-2', 'wp_adv', 'button-extra']));
    }

    /**
     * @test
     */
    public function add_stylesheet()
    {
        $url = BEYONDWORDS__PLUGIN_URI . 'src/post/add-player/AddPlayer.css';

        $this->assertSame(sprintf('https://example.com/style.css,%s', $url), AddPlayer::add_stylesheet('https://example.com/style.css'));
    }

    /**
     * @test
     */
    public function player_preview_i18n_styles()
    {
        $expect = "iframe [data-beyondwords-player]:empty:after, .edit-post-visual-editor [data-beyondwords-player]:empty:after { content: 'Player placeholder: The position of the audio player.'; }";

        $this->assertSame($expect, AddPlayer::player_preview_i18n_styles());
    }

    /**
     * @test
     */
    public function filter_tiny_mce_settings()
    {
        // No existing styles
        $settings = AddPlayer::filter_tiny_mce_settings([]);
        $this->assertSame(AddPlayer::player_preview_i18n_styles() . ' ', $settings['content_style']);

        // Existing styles
        $settings = AddPlayer::filter_tiny_mce_settings(['content_style' => 'p { color: red; }']);
        $this->assertSame('p { color: red; } ' . AddPlayer::player_preview_i18n_styles() . ' ', $settings['content_style']);
    }

    /**
     * @test
     */
    public function add_editor_styles()
    {
        $html = $this->capture_output(function () {
            AddPlayer::add_editor_styles();
        });

        $this->assertSame('<style>' . AddPlayer::player_preview_i18n_styles() . '</style>', $html);
    }

    /**
     * @test
     */
    public function register_block_registers_the_block_type()
    {
        $registry = \WP_Block_Type_Registry::get_instance();

        if ($registry->is_registered('beyondwords/player')) {
            $registry->unregister('beyondwords/player');
        }

        AddPlayer::register_block();

        $this->assertTrue($registry->is_registered('beyondwords/player'));
    }

}
