<?php

declare( strict_types = 1 );

/**
 * BeyondWords "Add Player" component.
 *
 * @package BeyondWords\Editor\Components
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.2.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Editor\Components;

/**
 * AddPlayer
 *
 * @since 3.2.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class AddPlayer {

	// The CSS declaration block for the player preview in both Classic Editor and Block Editor.
	public const PLAYER_PREVIEW_STYLE_FORMAT = "iframe [data-beyondwords-player]:empty:after, .edit-post-visual-editor [data-beyondwords-player]:empty:after { content: '%s'; }"; // phpcs:ignore Generic.Files.LineLength.TooLong

	/**
	 * Init.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function init() {
		add_action( 'init', [ self::class, 'register_block'] );

		add_action( 'admin_head', [ self::class, 'add_editor_styles'] );
		add_filter( 'tiny_mce_before_init', [ self::class, 'filter_tiny_mce_settings'] );

		add_filter( 'mce_external_plugins', [ self::class, 'add_plugin'] );
		add_filter( 'mce_buttons', [ self::class, 'add_button'] );
		add_filter( 'mce_css', [ self::class, 'add_stylesheet'] );
	}

	/**
	 * Register Block.
	 *
	 * @since 3.2.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function register_block() {
		\register_block_type( __DIR__ );
	}

	/**
	 * Add TinyMCE buttons.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param array TinyMCE plugin array
	 */
	public static function add_plugin( $plugin_array ) {
		$plugin_array['beyondwords_player'] = BEYONDWORDS__PLUGIN_URI . 'src/editor/components/add-player/tinymce.js';
		return $plugin_array;
	}

	/**
	 * Register TinyMCE buttons.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param array TinyMCE buttons array
	 */
	public static function add_button( $buttons ) {
		$adv_index = array_search( 'wp_adv', $buttons );

		if ( $adv_index === false ) {
			$adv_index = count( $buttons );
		}

		array_splice( $buttons, $adv_index, 0, [ 'beyondwords_player'] );

		return $buttons;
	}

	/**
	 * Filters the comma-delimited list of stylesheets to load in TinyMCE.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param string $stylesheets Comma-delimited list of stylesheets.
	 *
	 * @return string Comma-delimited list of stylesheets with the "Add Player" CSS appended.
	 */
	public static function add_stylesheet( $stylesheets ) {
		return $stylesheets . ',' . BEYONDWORDS__PLUGIN_URI . 'src/editor/components/add-player/add-player.css';
	}

	/**
	 * "Player Preview" i18n styles.
	 *
	 * Player preview uses the CSS :after to set the content so we pass the CSS through WordPress i18n functions here.
	 *
	 * @since 3.3.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @return string CSS Block for player preview i18n delcerations.
	 */
	public static function player_preview_i18n_styles() {
		return sprintf(
			self::PLAYER_PREVIEW_STYLE_FORMAT,
			esc_attr__( 'Player placeholder: The position of the audio player.', 'speechkit' )
		);
	}

	/**
	 * Tiny MCE before init.
	 *
	 * Adds i18n-compatible TinyMCE Classic Editor CSS for the player placeholder.
	 *
	 * @since 3.3.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param mixed[] $setings An array with TinyMCE config.
	 *
	 * @return mixed[] An array with TinyMCE config.
	 */
	public static function filter_tiny_mce_settings( $settings ) {
		if ( isset( $settings['content_style'] ) ) {
			$settings['content_style'] .= ' ' . self::player_preview_i18n_styles() . ' ';
		} else {
			$settings['content_style'] = self::player_preview_i18n_styles() . ' ';
		}

		return $settings;
	}

	/**
	 * Add editor styles.
	 *
	 * Adds i18n-compatible Block Editor CSS for the player placeholder.
	 *
	 * @since 3.3.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function add_editor_styles() {
		$allowed_html = [
			'style' => [],
		];

		echo wp_kses(
			sprintf( '<style>%s</style>', self::player_preview_i18n_styles() ),
			$allowed_html
		);
	}
}
