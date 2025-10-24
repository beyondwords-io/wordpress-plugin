/* global Cypress, cy, before, beforeEach, context, it */

context( 'Settings', () => {
	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	it( 'shows the tab headings', () => {
		cy.saveMinimalPluginSettings();

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=credentials'
		);
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Credentials' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=content'
		);
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Content' );

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' );
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Voices' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=summarization'
		);
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Summarization' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=pronunciations'
		);
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Pronunciations' );
	} );

	context( 'Fresh Install', () => {
		before( () => {
			// This test requires a fresh database WITHOUT credentials
			// to test the voice settings sync on first credential entry
			cy.task( 'setupFreshDatabase' );
		} );

		it( 'has synced the voice settings on install', () => {
			cy.visit( '/wp-admin/options-general.php?page=beyondwords' );

			// Enter only a valid API Key & Project ID
			cy.get( 'input#beyondwords_api_key' )
				.clear()
				.type( Cypress.env( 'apiKey' ) );
			cy.get( 'input#beyondwords_project_id' )
				.clear()
				.type( Cypress.env( 'projectId' ) );
			cy.get( 'input[type="submit"]' ).click();

			// The language and voices from the mock API response should be synced
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=voices'
			);
			cy.get( 'select#beyondwords_project_language_code' )
				.find( ':selected' )
				.contains( 'English (American)' );
			cy.get( 'select#beyondwords_project_title_voice_id' )
				.find( ':selected' )
				.contains( 'Ava (Multilingual)' );
			cy.get( 'select#beyondwords_project_body_voice_id' )
				.find( ':selected' )
				.contains( 'Ava (Multilingual)' );
		} );
	} );

	// @todo unskip test and add a URL param to force syncing
	it.skip( 'syncs the settings from the Dashboard to WordPress', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options.php' );

		// Clear existing plugin data.
		cy.get( '#beyondwords_player_call_to_action' ).clear();
		cy.get( '#beyondwords_player_clickable_sections' ).clear();
		cy.get( '#beyondwords_player_skip_button_style' ).clear();
		cy.get( '#beyondwords_player_style' ).clear();
		cy.get( '#beyondwords_player_theme' ).clear();
		cy.get( '#beyondwords_player_widget_position' ).clear();
		cy.get( '#beyondwords_player_widget_style' ).clear();
		cy.get( '#beyondwords_project_body_voice_id' ).clear();
		cy.get( '#beyondwords_project_body_voice_speaking_rate' ).clear();
		cy.get( '#beyondwords_project_language_code' ).clear();
		cy.get( '#beyondwords_project_title_enabled' ).clear();
		cy.get( '#beyondwords_project_title_voice_id' ).clear();
		cy.get( '#beyondwords_project_title_voice_speaking_rate' ).clear();

		// @todo themes cannot be cleared using .clear() because they are serialized data
		// cy.get( '#beyondwords_player_theme_dark' ).clear()
		// cy.get( '#beyondwords_player_theme_light' ).clear()
		// cy.get( '#beyondwords_player_theme_video' ).clear()

		cy.get( 'form#all-options' ).submit();

		cy.visit( '/wp-admin/options.php' );

		// These should be repopulated using the Mock API response data.
		cy.get( '#beyondwords_player_call_to_action' ).should(
			'have.value',
			'Listen to this article'
		);
		cy.get( '#beyondwords_player_clickable_sections' ).should(
			'have.value',
			'1'
		);
		cy.get( '#beyondwords_player_skip_button_style' ).should(
			'have.value',
			'auto'
		);
		cy.get( '#beyondwords_player_style' ).should(
			'have.value',
			'standard'
		);
		cy.get( '#beyondwords_player_theme' ).should( 'have.value', 'light' );
		cy.get( '#beyondwords_player_widget_position' ).should(
			'have.value',
			'auto'
		);
		cy.get( '#beyondwords_player_widget_style' ).should(
			'have.value',
			'standard'
		);
		cy.get( '#beyondwords_project_body_voice_id' ).should(
			'have.value',
			'2517'
		);
		cy.get( '#beyondwords_project_body_voice_speaking_rate' ).should(
			'have.value',
			'95'
		);
		cy.get( '#beyondwords_project_language_code' ).should(
			'have.value',
			'en_US'
		);
		cy.get( '#beyondwords_project_title_enabled' ).should(
			'have.value',
			'1'
		);
		cy.get( '#beyondwords_project_title_voice_id' ).should(
			'have.value',
			'2517'
		);
		cy.get( '#beyondwords_project_title_voice_speaking_rate' ).should(
			'have.value',
			'90'
		);

		// @todo themes cannot be tested using this method because they are serialized data
		// cy.get( '#beyondwords_player_theme_dark' ).should( 'have.value', '' )
		// cy.get( '#beyondwords_player_theme_light' ).should( 'have.value', '' )
		// cy.get( '#beyondwords_player_theme_video' ).should( 'have.value', '' )
	} );

	it( 'removes the plugin settings when uninstalled', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options.php' );

		cy.get( '#beyondwords_api_key' );
		cy.get( '#beyondwords_project_body_voice_speaking_rate' );
		cy.get( '#beyondwords_project_title_enabled' );
		cy.get( '#beyondwords_player_call_to_action' );
		cy.get( '#beyondwords_player_clickable_sections' );
		cy.get( '#beyondwords_player_theme_dark' );
		cy.get( '#beyondwords_player_theme_light' );
		cy.get( '#beyondwords_player_skip_button_style' );
		cy.get( '#beyondwords_player_style' );
		cy.get( '#beyondwords_player_theme' );
		cy.get( '#beyondwords_player_theme_video' );
		cy.get( '#beyondwords_player_widget_position' );
		cy.get( '#beyondwords_player_widget_style' );
		cy.get( '#beyondwords_project_body_voice_id' );
		cy.get( '#beyondwords_project_id' );
		cy.get( '#beyondwords_project_language_code' );
		cy.get( '#beyondwords_project_title_voice_id' );
		cy.get( '#beyondwords_project_title_voice_speaking_rate' );
		cy.get( '#beyondwords_valid_api_connection' );
		cy.get( '#beyondwords_version' );

		// The plugin files will not be deleted. Only the uninstall procedure will be run.
		cy.uninstallPlugin( '--skip-delete speechkit' );

		cy.visit( '/wp-admin/options.php' );

		cy.get( '#beyondwords_api_key' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_body_voice_speaking_rate' ).should(
			'not.exist'
		);
		cy.get( '#beyondwords_project_title_enabled' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_call_to_action' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_clickable_sections' ).should(
			'not.exist'
		);
		cy.get( '#beyondwords_player_theme_dark' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_highlight_sections' ).should(
			'not.exist'
		);
		cy.get( '#beyondwords_player_theme_light' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_skip_button_style' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_style' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_theme' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_theme_video' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_widget_position' ).should( 'not.exist' );
		cy.get( '#beyondwords_player_widget_style' ).should( 'not.exist' );
		cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.exist' );
		cy.get( '#beyondwords_preselect' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_body_voice_id' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_id' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_language_code' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_language_id' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_title_voice_id' ).should( 'not.exist' );
		cy.get( '#beyondwords_project_title_voice_speaking_rate' ).should(
			'not.exist'
		);
		cy.get( '#beyondwords_valid_api_connection' ).should( 'not.exist' );
		cy.get( '#beyondwords_version' ).should( 'not.exist' );
	} );
} );
