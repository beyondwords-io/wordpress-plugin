/* global cy, beforeEach, context, it */

context( 'Settings > Player > Playback from segments', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( `sets "Playback from segments"`, () => {
		// Check
		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( '#beyondwords_player_clickable_sections' ).check();
		cy.get( 'input[type="submit"]' ).click();

		// Check for value in WordPress options
		cy.visit( '/wp-admin/options.php' );
		cy.get( '#beyondwords_player_clickable_sections' ).should(
			'have.value',
			'1'
		);

		// Check for value in Site Health
		cy.visitPluginSiteHealth();
		cy.getSiteHealthValue( 'Playback from segments' ).should(
			'have.text',
			'Yes'
		);

		// Uncheck
		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
		cy.get( '#beyondwords_player_clickable_sections' ).uncheck();
		cy.get( 'input[type="submit"]' ).click();

		// Check for value in WordPress options
		cy.visit( '/wp-admin/options.php' );
		cy.get( '#beyondwords_player_clickable_sections' ).should(
			'have.value',
			''
		);

		// Check for value in Site Health
		cy.visitPluginSiteHealth();
		cy.getSiteHealthValue( 'Playback from segments' ).should(
			'have.text',
			'No'
		);
	} );
} );
