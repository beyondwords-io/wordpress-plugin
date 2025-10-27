/* global Cypress, cy, before, context, expect, it */

context( 'Site Health', () => {
	before( () => {
		// This test requires a fresh database WITHOUT credentials
		// to test the initial settings sync when credentials are first configured
		cy.task( 'setupFreshDatabase' );
		cy.login();
		cy.saveMinimalPluginSettings();
	} );

	const semverRegex =
		// eslint-disable-next-line max-len
		/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/;

	it( 'has BeyondWords site health info', () => {
		cy.visit( '/wp-admin/site-health.php?tab=debug' );

		cy.get(
			'button[aria-controls="health-check-accordion-block-beyondwords"]'
		).click();

		cy.get( '#health-check-accordion-block-beyondwords' ).within( () => {
			// Plugin version
			cy.get( 'tr' )
				.eq( 0 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Plugin version' );
					cy.get( 'td' )
						.invoke( 'text' )
						.should( 'match', semverRegex );
				} );
			// REST API URL
			cy.get( 'tr' )
				.eq( 1 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'REST API URL' );
					cy.get( 'td' ).should(
						'have.text',
						Cypress.env( 'apiUrl' )
					);
				} );
			// Communication with REST API
			cy.get( 'tr' )
				.eq( 2 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Communication with REST API'
					);
					cy.get( 'td' ).should(
						'have.text',
						'BeyondWords API is reachable'
					);
				} );
			// Compatible post types
			cy.get( 'tr' )
				.eq( 3 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Compatible post types'
					);
					cy.get( 'td' ).should(
						'have.text',
						'post, page, cpt_active, cpt_inactive'
					);
				} );
			// Incompatible post types
			cy.get( 'tr' )
				.eq( 4 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Incompatible post types'
					);
					cy.get( 'td' ).should( 'have.text', 'cpt_unsupported' );
				} );
			// Integration method
			cy.get( 'tr' )
				.eq( 5 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Integration method' );
					cy.get( 'td' ).should( 'have.text', 'rest-api' );
				} );
			// API Key
			cy.get( 'tr' )
				.eq( 6 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'API Key' );
					cy.get( 'td' )
						.invoke( 'text' )
						.then( ( text ) => {
							const visibleChars =
								Cypress.env( 'apiKey' ).slice( -4 );
							expect( text )
								.to.be.a( 'string' )
								.and.match(
									new RegExp( `[X]{34}${ visibleChars }` )
								);
						} );
				} );
			// Project ID
			cy.get( 'tr' )
				.eq( 7 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Project ID' );
					cy.get( 'td' ).should(
						'have.text',
						Cypress.env( 'projectId' )
					);
				} );
			// Include title in audio
			cy.get( 'tr' )
				.eq( 8 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Include title in audio'
					);
					cy.get( 'td' ).should( 'have.text', 'Yes' );
				} );
			// Include excerpts in audio
			cy.get( 'tr' )
				.eq( 9 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Auto-publish audio' );
					cy.get( 'td' ).should( 'have.text', 'Yes' );
				} );
			// Include excerpts in audio
			cy.get( 'tr' )
				.eq( 10 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Include excerpts in audio'
					);
					cy.get( 'td' ).should( 'have.text', 'No' );
				} );
			// Preselect 'Generate audio'
			cy.get( 'tr' )
				.eq( 11 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Preselect \u2018Generate audio\u2019'
					);
					cy.get( 'td' ).should(
						'have.text',
						'{\n    "post": "1",\n    "page": "1"\n}'
					);
				} );
			// Default language code
			cy.get( 'tr' )
				.eq( 12 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Default language code'
					);
					cy.get( 'td' ).should( 'have.text', 'en_US' );
				} );
			// Default language ID
			cy.get( 'tr' )
				.eq( 13 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Default language ID' );
					cy.get( 'td' ).should( 'have.text', '' );
				} );
			// Title voice ID
			cy.get( 'tr' )
				.eq( 14 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Title voice ID' );
					cy.get( 'td' ).should( 'have.text', '2517' );
				} );
			// Title voice speaking rate
			cy.get( 'tr' )
				.eq( 15 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Title voice speaking rate'
					);
					cy.get( 'td' ).should( 'have.text', '90' );
				} );
			// Body voice ID
			cy.get( 'tr' )
				.eq( 16 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Body voice ID' );
					cy.get( 'td' ).should( 'have.text', '2517' );
				} );
			// Body voice speaking rate
			cy.get( 'tr' )
				.eq( 17 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Body voice speaking rate'
					);
					cy.get( 'td' ).should( 'have.text', '95' );
				} );
			// Player UI
			cy.get( 'tr' )
				.eq( 18 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Player UI' );
					cy.get( 'td' ).should( 'have.text', 'enabled' );
				} );
			// Player style
			cy.get( 'tr' )
				.eq( 19 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Player style' );
					cy.get( 'td' ).should( 'have.text', 'standard' );
				} );
			// Player theme
			cy.get( 'tr' )
				.eq( 20 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Player theme' );
					cy.get( 'td' ).should( 'have.text', 'light' );
				} );
			// Light theme
			cy.get( 'tr' )
				.eq( 21 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Light theme' );
					cy.get( 'td' ).should(
						'have.text',
						// eslint-disable-next-line max-len
						'{\n    "background_color": "#f5f5f5",\n    "icon_color": "#000",\n    "text_color": "#111",\n    "highlight_color": "#eee"\n}'
					);
				} );
			// Dark theme
			cy.get( 'tr' )
				.eq( 22 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Dark theme' );
					cy.get( 'td' ).should(
						'have.text',
						// eslint-disable-next-line max-len
						'{\n    "background_color": "transparent",\n    "icon_color": "#fff",\n    "text_color": "#fff",\n    "highlight_color": "#444"\n}'
					);
				} );
			// Video theme
			cy.get( 'tr' )
				.eq( 23 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Video theme' );
					cy.get( 'td' ).should(
						'have.text',
						// eslint-disable-next-line max-len
						'{\n    "background_color": "#f5f5f5",\n    "icon_color": "#000",\n    "text_color": "#111"\n}'
					);
				} );
			// Call-to-action
			cy.get( 'tr' )
				.eq( 24 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Call-to-action' );
					cy.get( 'td' ).should(
						'have.text',
						'Listen to this article'
					);
				} );
			// Text highlighting
			cy.get( 'tr' )
				.eq( 25 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Text highlighting' );
					cy.get( 'td' ).should( 'have.text', 'No' );
				} );
			// Playback from segments
			cy.get( 'tr' )
				.eq( 26 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Playback from segments'
					);
					cy.get( 'td' ).should( 'have.text', 'Yes' );
				} );
			// Widget style
			cy.get( 'tr' )
				.eq( 27 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Widget style' );
					cy.get( 'td' ).should( 'have.text', 'standard' );
				} );
			// Widget position
			cy.get( 'tr' )
				.eq( 28 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Widget position' );
					cy.get( 'td' ).should( 'have.text', 'auto' );
				} );
			// Skip button style
			cy.get( 'tr' )
				.eq( 29 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Skip button style' );
					cy.get( 'td' ).should( 'have.text', 'auto' );
				} );
			// Registered filters
			cy.get( 'tr' )
				.eq( 30 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Registered filters' );
					cy.get( 'td' ).should( 'have.text', 'None' );
				} );
			// Registered deprecated filters
			cy.get( 'tr' )
				.eq( 31 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Registered deprecated filters'
					);
					cy.get( 'td' ).should( 'have.text', 'None' );
				} );
			// Review Notice Dismissed
			cy.get( 'tr' )
				.eq( 32 )
				.within( () => {
					cy.get( 'th' ).should( 'have.text', 'Date Activated' );
					cy.get( 'td' ).should(
						'satisfy',
						( val ) => NaN !== Date.parse( val )
					);
				} );
			// Review Notice Dismissed
			cy.get( 'tr' )
				.eq( 33 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'Review Notice Dismissed'
					);
					cy.get( 'td' ).should( 'have.text', '' );
				} );
			// BEYONDWORDS_AUTO_SYNC_SETTINGS
			cy.get( 'tr' )
				.eq( 34 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'BEYONDWORDS_AUTO_SYNC_SETTINGS'
					);
					cy.get( 'td' ).should( 'have.text', 'False' );
				} );
			// BEYONDWORDS_AUTOREGENERATE
			cy.get( 'tr' )
				.eq( 35 )
				.within( () => {
					cy.get( 'th' ).should(
						'have.text',
						'BEYONDWORDS_AUTOREGENERATE'
					);
					cy.get( 'td' ).should( 'have.text', 'Undefined' );
				} );
		} );
	} );
} );
