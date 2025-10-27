/* global Cypress, cy, beforeEach, context, it */

context( 'Settings > Pronunciations', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'has the "Manage pronunciations" button', () => {
		cy.saveMinimalPluginSettings();

		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=pronunciations'
		);
		cy.get( '.button.button-primary' )
			.eq( 0 )
			.invoke( 'text' )
			.then( ( text ) => text.trim() )
			.should( 'equal', 'Manage pronunciations' );
		cy.get( '.button.button-primary' )
			.eq( 0 )
			.should(
				'have.attr',
				'href',
				`https://dash.beyondwords.io/dashboard/project/${ Cypress.env(
					'projectId'
				) }/settings?tab=rules`
			);
	} );
} );
