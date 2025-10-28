/* global cy, beforeEach, context, it */

context( 'Settings > Player > Player theme', () => {
	beforeEach( () => {
		cy.updateOption( 'beyondwords_player_ui', 'enabled' );
		cy.login();
	} );

	const themes = [
		{
			value: 'light',
			label: 'Light (default)',
		},
		{
			value: 'dark',
			label: 'Dark',
		},
		{
			value: 'auto',
			label: 'Auto',
		},
	];

	themes.forEach( ( theme ) => {
		it( `sets "${ theme.label }"`, () => {
			cy.visit(
				'/wp-admin/options-general.php?page=beyondwords&tab=player'
			);
			cy.get( 'select[name="beyondwords_player_theme"]' ).select(
				theme.label
			);
			cy.get( 'input[type="submit"]' ).click();

			// Check for value in WordPress options
			cy.visit( '/wp-admin/options.php' );
			cy.get( '#beyondwords_player_theme' ).should(
				'have.value',
				theme.value
			);

			// Check for value in Site Health
			cy.visitPluginSiteHealth();
			cy.getSiteHealthValue( 'Player theme' ).should(
				'have.text',
				theme.value
			);
		} );
	} );
} );
