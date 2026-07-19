/**
 * @group settings
 * @covers src/settings/class-fields.php,src/settings/class-tabs.php
 */

/* global cy, beforeEach, context, it */

context( 'Settings > Integration', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'lists rest-api and client-side as integration options', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=integration'
		);

		cy.get( 'select[name="beyondwords_integration_method"]' )
			.find( 'option' )
			.should( 'have.length', 2 );
		cy.get(
			'select[name="beyondwords_integration_method"] option[value="rest-api"]'
		).should( 'exist' );
		cy.get(
			'select[name="beyondwords_integration_method"] option[value="client-side"]'
		).should( 'exist' );
	} );

	it( 'persists Magic Embed (client-side) and round-trips back to REST API', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=integration'
		);
		cy.get( 'select[name="beyondwords_integration_method"]' ).select(
			'Magic Embed'
		);
		cy.get( 'input[type="submit"]' ).click();
		cy.get( '.notice-success' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=integration'
		);
		cy.get( 'select[name="beyondwords_integration_method"]' ).should(
			'have.value',
			'client-side'
		);

		cy.get( 'select[name="beyondwords_integration_method"]' ).select(
			'REST API'
		);
		cy.get( 'input[type="submit"]' ).click();
		cy.get( '.notice-success' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=integration'
		);
		cy.get( 'select[name="beyondwords_integration_method"]' ).should(
			'have.value',
			'rest-api'
		);
	} );
} );
