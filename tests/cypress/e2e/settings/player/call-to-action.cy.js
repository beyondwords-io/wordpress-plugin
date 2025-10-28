/* global cy, beforeEach, context, it */

context( 'Settings > Player > Call-to-action', () => {
	beforeEach( () => {
		cy.updateOption( 'beyondwords_player_ui', 'enabled' );
		cy.login();
	} );

	const values = [ 'Listen', 'Play' ];

	values.forEach( ( value ) => {
		it( `sets "${ value }"`, () => {
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
