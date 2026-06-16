/**
 * @group settings
 * @covers src/settings/class-settings.php
 */

/* global Cypress, cy, beforeEach, afterEach, context, it */

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

			// Verify written values are reflected back into the form.
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
				'input[name="beyondwords_preselect[post][mode]"][value="all"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[page][mode]"][value="all"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[cpt_active][mode]"][value="all"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[cpt_inactive][mode]"][value="off"]'
			).check();
			cy.get(
				'input[name="beyondwords_preselect[cpt_unsupported][mode]"]'
			).should( 'not.exist' );
			cy.get( 'select[name="beyondwords_player_ui"]' ).select(
				'Enabled'
			);

			cy.get( 'input[type=submit]' ).click();
			cy.get( '.notice-success' );

			// Re-load and verify Preferences persisted.
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);
			cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.be.checked' );
			cy.get(
				'input[name="beyondwords_preselect[post][mode]"][value="all"]'
			).should( 'be.checked' );
			cy.get(
				'input[name="beyondwords_preselect[cpt_active][mode]"][value="all"]'
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

			// Choose term-gating for posts; preselect.js enables the tree.
			cy.get(
				'input[name="beyondwords_preselect[post][mode]"][value="terms"]'
			).check();
			cy.get(
				`input[name="beyondwords_preselect[post][terms][category][]"][value="${ newsId }"]`
			).check();

			cy.get( 'input[type=submit]' ).click();
			cy.get( '.notice-success' );

			// Reload and verify the mode + term persisted.
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
			);
			cy.get(
				'input[name="beyondwords_preselect[post][mode]"][value="terms"]'
			).should( 'be.checked' );
			cy.get(
				`input[name="beyondwords_preselect[post][terms][category][]"][value="${ newsId }"]`
			).should( 'be.checked' );
		} );
	} );
} );
