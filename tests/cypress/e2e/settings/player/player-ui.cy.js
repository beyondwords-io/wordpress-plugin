/* global cy, before, beforeEach, context, expect, it */

context( 'Settings > Player UI', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'uses "Enabled" Player UI setting', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Enabled' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Enabled" Player UI' } );

		// Admin should have latest player
		cy.hasPlayerInstances( 1 );

		// Frontend should have a player div
		cy.viewPostViaSnackbar();
		cy.getPlayerScriptTag().should( 'exist' );
		cy.hasPlayerInstances( 1, {
			showUserInterface: true,
		} );
	} );

	it( 'uses "Headless" Player UI setting', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Headless' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Headless" Player UI' } );

		// Admin should have latest player
		cy.hasPlayerInstances( 1 );

		// Frontend should have a player div without a UI
		cy.viewPostViaSnackbar();
		cy.get( '.beyondwords-player.bwp' ).should( 'exist' );
		cy.get( '.beyondwords-player .user-interface' ).should( 'not.exist' );

		// Frontend should have a player without a UI
		cy.hasPlayerInstances( 1, {
			showUserInterface: false,
		} );
	} );

	it( 'uses "Disabled" Player UI setting', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Disabled' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Disabled" Player UI' } );

		// Admin should have latest player
		cy.hasPlayerInstances( 1 );

		// Frontend should not have a player div
		cy.viewPostViaSnackbar();
		cy.hasPlayerInstances( 0 );
	} );
} );
