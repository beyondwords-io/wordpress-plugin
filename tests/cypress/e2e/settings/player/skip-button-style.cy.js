/* global cy, before, beforeEach, context, it */

context( 'Settings > Player > Skip button style', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	const values = [ 'seconds-5-10', 'segments' ];

	values.forEach( ( value ) => {
		it( `sets "${ value }"`, () => {
			cy.saveMinimalPluginSettings();

			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=player'
			);
			cy.get( 'input[name="beyondwords_player_skip_button_style"]' )
				.clear()
				.type( value );
			cy.get( 'input[type="submit"]' ).click().wait( 100 );

			// Check for value in WordPress options
			cy.visit( '/wp-admin/options.php' );
			cy.get( '#beyondwords_player_skip_button_style  ' ).should(
				'have.value',
				value
			);

			// Check for value in Site Health
			cy.visitPluginSiteHealth();
			cy.getSiteHealthValue( 'Skip button style' ).should(
				'have.text',
				value
			);
		} );
	} );
} );
