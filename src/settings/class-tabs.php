<?php
/**
 * BeyondWords settings tabs.
 *
 * Defines the three tabs (Authentication, Integration, Preferences),
 * their slugs, settings groups and section IDs. Field registration lives
 * in `Fields` and `Preselect`; this class is the single source of truth
 * for which tab a field belongs to.
 *
 * @package BeyondWords\Settings
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings tabs.
 *
 * `register()` runs on `admin_init` and creates the settings sections that
 * the field renderers attach to.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Tabs {

	const TAB_AUTHENTICATION = 'authentication';
	const TAB_INTEGRATION    = 'integration';
	const TAB_PREFERENCES    = 'preferences';

	const PAGE_AUTHENTICATION = 'beyondwords_authentication';
	const PAGE_INTEGRATION    = 'beyondwords_integration';
	const PAGE_PREFERENCES    = 'beyondwords_preferences';

	const SETTINGS_GROUP_AUTHENTICATION = 'beyondwords_authentication_settings';
	const SETTINGS_GROUP_INTEGRATION    = 'beyondwords_integration_settings';
	const SETTINGS_GROUP_PREFERENCES    = 'beyondwords_preferences_settings';

	const SECTION_AUTHENTICATION = 'authentication';
	const SECTION_INTEGRATION    = 'integration';
	const SECTION_PREFERENCES    = 'preferences';

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register_sections' ) );
	}

	/**
	 * Register a settings section per tab.
	 */
	public static function register_sections(): void {
		add_settings_section(
			self::SECTION_AUTHENTICATION,
			'',
			'__return_false',
			self::PAGE_AUTHENTICATION
		);

		add_settings_section(
			self::SECTION_INTEGRATION,
			'',
			'__return_false',
			self::PAGE_INTEGRATION
		);

		add_settings_section(
			self::SECTION_PREFERENCES,
			'',
			'__return_false',
			self::PAGE_PREFERENCES
		);
	}

	/**
	 * Tabs visible on the settings page.
	 *
	 * Until valid API credentials are present we only show Authentication —
	 * the other tabs need an API connection to be useful.
	 *
	 * @return array<string,string> Map of tab slug to display label.
	 */
	public static function get_visible_tabs(): array {
		$tabs = array(
			self::TAB_AUTHENTICATION => __( 'Authentication', 'speechkit' ),
			self::TAB_INTEGRATION    => __( 'Integration', 'speechkit' ),
			self::TAB_PREFERENCES    => __( 'Preferences', 'speechkit' ),
		);

		if ( ! Utils::has_valid_api_connection() ) {
			return array( self::TAB_AUTHENTICATION => $tabs[ self::TAB_AUTHENTICATION ] );
		}

		return $tabs;
	}

	/**
	 * Resolve the active tab slug from `$_GET`, falling back to the first.
	 */
	public static function get_active_tab(): string {
		$tabs = self::get_visible_tabs();

		if ( empty( $tabs ) ) {
			return '';
		}

		$default = (string) array_key_first( $tabs );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		return ( '' !== $requested && array_key_exists( $requested, $tabs ) ) ? $requested : $default;
	}

	/**
	 * Resolve the page slug + settings group for the active tab.
	 *
	 * @return array{page:string,group:string}
	 */
	public static function get_active_page_and_group(): array {
		switch ( self::get_active_tab() ) {
			case self::TAB_INTEGRATION:
				return array(
					'page'  => self::PAGE_INTEGRATION,
					'group' => self::SETTINGS_GROUP_INTEGRATION,
				);
			case self::TAB_PREFERENCES:
				return array(
					'page'  => self::PAGE_PREFERENCES,
					'group' => self::SETTINGS_GROUP_PREFERENCES,
				);
			case self::TAB_AUTHENTICATION:
			default:
				return array(
					'page'  => self::PAGE_AUTHENTICATION,
					'group' => self::SETTINGS_GROUP_AUTHENTICATION,
				);
		}
	}
}
