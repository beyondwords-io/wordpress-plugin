/* global cy, beforeEach, context, it */

context( 'Settings', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'renders the three v7 settings tabs', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=authentication'
		);
		cy.get( '.nav-tab-active' ).contains( 'Authentication' );
		cy.get( 'input[name="beyondwords_api_key"]' ).should( 'exist' );
		cy.get( 'input[name="beyondwords_project_id"]' ).should( 'exist' );

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=integration'
		);
		cy.get( '.nav-tab-active' ).contains( 'Integration' );
		cy.get( 'select[name="beyondwords_integration_method"]' ).should(
			'exist'
		);

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.get( '.nav-tab-active' ).contains( 'Preferences' );
		cy.get( 'select[name="beyondwords_player_ui"]' ).should( 'exist' );
		cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'exist' );
		cy.get( 'input[name^="beyondwords_preselect"]' ).should( 'exist' );
	} );

	it( 'falls back to Authentication when an unknown tab is requested', () => {
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=does-not-exist'
		);
		cy.get( '.nav-tab-active' ).contains( 'Authentication' );
	} );
} );
