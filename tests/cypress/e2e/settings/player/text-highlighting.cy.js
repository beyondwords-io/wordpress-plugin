/* global cy, before, beforeEach, context, it */

context( 'Settings > Player > Text highlighting', () => {
	before( () => {
		// One-time setup for all tests
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	it( `sets "Text highlighting"`, () => {
		cy.saveMinimalPluginSettings();

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
