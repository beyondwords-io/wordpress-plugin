/**
 * @group settings
 * @covers src/settings/class-settings.php
 */

/* global Cypress, cy, beforeEach, afterEach, context, it */

/**
 * Exercises the settings-form submission paths end-to-end.
 *
 * The single spec driving real <form> submissions (sanitisers, validators,
 * register_setting callbacks) — wire new tabs/fields in here, not other specs.
 */
context( 'Settings: form submission', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'persists Authentication and Preferences via the settings form', () => {
		cy.env( [ 'apiKey' ] ).then( ( { apiKey } ) => {
			// Authentication tab — API key + project ID.
			cy.visit( '/wp-admin/options-general.php?page=beyondwords' );
			cy.dismissPointers();

			cy.get( 'input[name="beyondwords_api_key"]' )
				.clear()
				.type( apiKey );
			cy.get( 'input[name="beyondwords_project_id"]' )
				.clear()
				.type( Cypress.expose( 'projectId' ) );
			cy.get( 'input[type=submit]' ).click();
			cy.get( '.notice-success' );

			cy.get( 'input[name="beyondwords_api_key"]' ).should(
				'have.value',
				apiKey
			);
			cy.get( 'input[name="beyondwords_project_id"]' ).should(
				'have.value',
				Cypress.expose( 'projectId' )
			);

			// Preferences tab — preselect + excerpt + player UI.
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);

			cy.get( '#beyondwords_prepend_excerpt' ).uncheck();
			cy.get(
				'input[name="beyondwords_preselect[post][enabled]"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[page][enabled]"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[cpt_active][enabled]"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[cpt_inactive][enabled]"]'
			).uncheck();
			cy.get(
				'input[name="beyondwords_preselect[cpt_unsupported][enabled]"]'
			).should( 'not.exist' );
			cy.get( 'select[name="beyondwords_player_ui"]' ).select(
				'Enabled'
			);

			cy.get( 'input[type=submit]' ).click();
			cy.get( '.notice-success' );

			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);
			cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.be.checked' );
			cy.get(
				'input[name="beyondwords_preselect[post][enabled]"]'
			).should( 'be.checked' );
			cy.get(
				'input[name="beyondwords_preselect[cpt_active][enabled]"]'
			).should( 'be.checked' );
			cy.get( 'select[name="beyondwords_player_ui"]' ).should(
				'have.value',
				'enabled'
			);
		} );
	} );

	afterEach( () => {
		// Restore the default preselect seed so later specs are unaffected.
		cy.task( 'updateOptionJson', {
			name: 'beyondwords_preselect',
			value: {
				post: { mode: 'all' },
				page: { mode: 'all' },
				cpt_active: { mode: 'all' },
			},
		} );
	} );

	it( 'persists term-gated preselect via the settings form', () => {
		cy.task( 'createTerm', {
			taxonomy: 'category',
			name: 'CypressSettingsNews',
		} ).then( ( newsId ) => {
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);
			cy.dismissPointers();

			cy.get(
				'input[name="beyondwords_preselect[post][all]"]'
			).uncheck();
			cy.get(
				`input[name="beyondwords_preselect[post][terms][category][]"][value="${ newsId }"]`
			).check();

			cy.get( 'input[type=submit]' ).click();
			cy.get( '.notice-success' );

			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);
			cy.get( 'input[name="beyondwords_preselect[post][all]"]' ).should(
				'not.be.checked'
			);
			cy.get(
				`input[name="beyondwords_preselect[post][terms][category][]"][value="${ newsId }"]`
			).should( 'be.checked' );
		} );
	} );
} );
