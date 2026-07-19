/**
 * @group settings
 * @covers src/settings/class-fields.php
 */

/* global cy, beforeEach, context, it */

context( 'Settings > Player UI', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'uses "Enabled" Player UI setting', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Enabled' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Enabled" Player UI' } );

		cy.viewPostViaSnackbar();

		cy.hasPlayerInstances( 1, {
			showUserInterface: undefined,
		} );
	} );

	it( 'uses "Headless" Player UI setting', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Headless' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Headless" Player UI' } );

		cy.viewPostViaSnackbar();

		cy.hasPlayerInstances( 1, {
			showUserInterface: false,
		} );
	} );

	it( 'uses "Disabled" Player UI setting', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Disabled' );
		cy.get( 'input[type="submit"]' ).click();

		cy.publishPostWithAudio( { title: '"Disabled" Player UI' } );

		cy.viewPostViaSnackbar();
		cy.hasPlayerInstances( 0 );
	} );
} );
