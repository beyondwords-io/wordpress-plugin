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

		// Frontend should have a player div
		cy.viewPostViaSnackbar();

		cy.hasPlayerInstances( 1, {
			showUserInterface: undefined,
		} );
	} );

	it( 'uses "Headless" Player UI setting', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Headless' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Headless" Player UI' } );

		cy.viewPostViaSnackbar();

		// Frontend should have a player with showUserInterface set to false
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

		// Frontend should not have a player div
		cy.viewPostViaSnackbar();
		cy.hasPlayerInstances( 0 );
	} );
} );
