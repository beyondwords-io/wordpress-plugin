/* global Cypress, cy, beforeEach, context, it */

/**
 * Exercises the settings-form submission paths end-to-end.
 *
 * Most tests bypass the UI and write options directly via WP-CLI tasks. This
 * spec is the single place that drives the actual <form> submissions, so the
 * sanitisers, validators, and `register_setting` callbacks all get hit. Keep
 * the tab-by-tab structure — if a new tab/field lands in the settings page,
 * it should be wired in here, not duplicated into other specs.
 */
context( 'Settings: form submission', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'persists Authentication and Preferences via the settings form', () => {
		// Authentication tab — API key + project ID.
		cy.visit( '/wp-admin/options-general.php?page=beyondwords' );
		cy.dismissPointers();

		cy.get( 'input[name="beyondwords_api_key"]' )
			.clear()
			.type( Cypress.env( 'apiKey' ) );
		cy.get( 'input[name="beyondwords_project_id"]' )
			.clear()
			.type( Cypress.env( 'projectId' ) );
		cy.get( 'input[type=submit]' ).click();
		cy.get( '.notice-success' );

		// Verify written values are reflected back into the form.
		cy.get( 'input[name="beyondwords_api_key"]' ).should(
			'have.value',
			Cypress.env( 'apiKey' )
		);
		cy.get( 'input[name="beyondwords_project_id"]' ).should(
			'have.value',
			Cypress.env( 'projectId' )
		);

		// Preferences tab — preselect + excerpt + player UI.
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);

		cy.get( '#beyondwords_prepend_excerpt' ).uncheck();
		cy.get( 'input[name="beyondwords_preselect[post]"]' ).check();
		cy.get( 'input[name="beyondwords_preselect[page]"]' ).check();
		cy.get( 'input[name="beyondwords_preselect[cpt_active]"]' ).check();
		cy.get( 'input[name="beyondwords_preselect[cpt_inactive]"]' ).uncheck();
		cy.get( 'input[name="beyondwords_preselect[cpt_unsupported]"]' ).should(
			'not.exist'
		);
		cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Enabled' );

		cy.get( 'input[type=submit]' ).click();
		cy.get( '.notice-success' );

		// Re-load and verify Preferences persisted.
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.be.checked' );
		cy.get( 'input[name="beyondwords_preselect[post]"]' ).should(
			'be.checked'
		);
		cy.get( 'input[name="beyondwords_preselect[cpt_active]"]' ).should(
			'be.checked'
		);
		cy.get( 'select[name="beyondwords_player_ui"]' ).should(
			'have.value',
			'enabled'
		);
	} );
} );
