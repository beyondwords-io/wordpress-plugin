/* global Cypress, cy, before, beforeEach, context, it */

context( 'Settings > Summarization', () => {
	before( () => {
		// cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
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
