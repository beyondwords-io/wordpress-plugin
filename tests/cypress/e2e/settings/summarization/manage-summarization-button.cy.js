/* global Cypress, cy, before, beforeEach, context, it */

context( 'Settings > Summarization', () => {
	before( () => {
		cy.task( 'setupDatabase' );
		// One-time setup for all tests
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	it( 'has the "Manage summarization" button', () => {
		cy.saveMinimalPluginSettings();

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=summarization'
		);
		cy.get( '.button.button-primary' )
			.eq( 0 )
			.invoke( 'text' )
			.then( ( text ) => text.trim() )
			.should( 'equal', 'Manage summarization' );
		cy.get( '.button.button-primary' )
			.eq( 0 )
			.should(
				'have.attr',
				'href',
				`https://dash.beyondwords.io/dashboard/project/${ Cypress.env(
					'projectId'
				) }/settings?tab=summarization`
			);
	} );
} );
