/* global cy, before, beforeEach, context, it */

context( 'Settings > Player > Call-to-action', () => {
	before( () => {
		cy.task( 'setupDatabase' );
		// One-time setup for all tests
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	const values = [ 'Listen', 'Play' ];

	values.forEach( ( value ) => {
		it( `sets "${ value }"`, () => {
			cy.saveMinimalPluginSettings();

			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=player'
			);
			cy.get( 'input[name="beyondwords_player_call_to_action"]' )
				.clear()
				.type( value );
			cy.get( 'input[type="submit"]' ).click();

			// Check for value in WordPress options
			cy.visit( '/wp-admin/options.php' );
			cy.get( '#beyondwords_player_call_to_action' ).should(
				'have.value',
				value
			);

			// Check for value in Site Health
			cy.visitPluginSiteHealth();
			cy.getSiteHealthValue( 'Call-to-action' ).should(
				'have.text',
				value
			);
		} );
	} );
} );
