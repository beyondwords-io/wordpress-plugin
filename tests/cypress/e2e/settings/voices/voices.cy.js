/* global cy, beforeEach, context, it */

context( 'Settings > Voices', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'opens the "Voices" tab', () => {
		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' );
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Voices' );
	} );
} );
