/* global cy, before, beforeEach, context, it */

context( 'Settings > Voices', () => {
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

	it( 'opens the "Voices" tab', () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' );
		cy.get( '#beyondwords-plugin-settings > h2' )
			.eq( 0 )
			.should( 'have.text', 'Voices' );
	} );
} );
