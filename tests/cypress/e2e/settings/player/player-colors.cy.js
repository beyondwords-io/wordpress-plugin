/* global cy, before, beforeEach, context, expect, it */

context( 'Settings > Player > Player colors', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	const lightTheme = {
		background_color: '#100',
		icon_color: '#200',
		text_color: '#300',
		highlight_color: '#400',
	};

	const darkTheme = {
		background_color: '#500',
		icon_color: '#600',
		text_color: '#700',
		highlight_color: '#800',
	};

	const videoTheme = {
		background_color: '#900',
		icon_color: '#a00',
		text_color: '#b00',
	};

	it( `sets Player colors"`, () => {
		cy.saveMinimalPluginSettings();

		cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );

		cy.get(
			'input[name="beyondwords_player_theme_light[background_color]"]'
		)
			.clear()
			.type( lightTheme.background_color );
		cy.get( 'input[name="beyondwords_player_theme_light[icon_color]"]' )
			.clear()
			.type( lightTheme.icon_color );
		cy.get( 'input[name="beyondwords_player_theme_light[text_color]"]' )
			.clear()
			.type( lightTheme.text_color );

		cy.get(
			'input[name="beyondwords_player_theme_dark[background_color]"]'
		)
			.clear()
			.type( darkTheme.background_color );
		cy.get( 'input[name="beyondwords_player_theme_dark[icon_color]"]' )
			.clear()
			.type( darkTheme.icon_color );
		cy.get( 'input[name="beyondwords_player_theme_dark[text_color]"]' )
			.clear()
			.type( darkTheme.text_color );

		cy.get(
			'input[name="beyondwords_player_theme_video[background_color]"]'
		)
			.clear()
			.type( videoTheme.background_color );
		cy.get( 'input[name="beyondwords_player_theme_video[icon_color]"]' )
			.clear()
			.type( videoTheme.icon_color );
		cy.get( 'input[name="beyondwords_player_theme_video[text_color]"]' )
			.clear()
			.type( videoTheme.text_color );

		cy.get(
			'input[name="beyondwords_player_theme_light[highlight_color]"]'
		)
			.clear()
			.type( lightTheme.highlight_color );
		cy.get( 'input[name="beyondwords_player_theme_dark[highlight_color]"]' )
			.clear()
			.type( darkTheme.highlight_color );

		cy.get( 'input[type="submit"]' ).click().wait( 100 );

		// Only check for value in Site Health
		cy.visitPluginSiteHealth();

		cy.getSiteHealthValue( 'Light theme' ).then( ( val ) => {
			const data = JSON.parse( val.text() );
			expect( data ).to.deep.equal( lightTheme );
		} );

		cy.getSiteHealthValue( 'Dark theme' ).then( ( val ) => {
			const data = JSON.parse( val.text() );
			expect( data ).to.deep.equal( darkTheme );
		} );

		cy.getSiteHealthValue( 'Video theme' ).then( ( val ) => {
			const data = JSON.parse( val.text() );
			expect( data ).to.deep.equal( videoTheme );
		} );
	} );
} );
