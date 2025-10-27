/* global cy, beforeEach, context, it */

context( 'Settings > Player > Text highlighting', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( `sets "Text highlighting"`, () => {
		// Check
		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( '#beyondwords_player_highlight_sections' ).check();
		cy.get( 'input[type="submit"]' ).click();

		// Check for value in WordPress options
		cy.visit( '/wp-admin/options.php' );
		cy.get( '#beyondwords_player_highlight_sections' ).should(
			'have.value',
			'body'
		);

		// Check for value in Site Health
		cy.visitPluginSiteHealth();
		cy.getSiteHealthValue( 'Text highlighting' ).should(
			'have.text',
			'Yes'
		);

		// Uncheck
		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( '#beyondwords_player_highlight_sections' ).uncheck();
		cy.get( 'input[type="submit"]' ).click();

		// Check for value in WordPress options
		cy.visit( '/wp-admin/options.php' );
		cy.get( '#beyondwords_player_highlight_sections' ).should(
			'have.value',
			''
		);

		// Check for value in Site Health
		cy.visitPluginSiteHealth();
		cy.getSiteHealthValue( 'Text highlighting' ).should(
			'have.text',
			'No'
		);
	} );
} );
