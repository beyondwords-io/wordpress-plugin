/* global Cypress, cy, before, context, expect, it */

context( 'Site Health', () => {
	before( () => {
		cy.login();
		// Setting options directly (not via the form) — the form-submission
		// path is exclusively covered by tests/cypress/e2e/settings/save-settings.cy.js.
		cy.task( 'updateOption', {
			name: 'beyondwords_prepend_excerpt',
			value: '0',
		} );
		cy.task( 'updateOption', {
			name: 'beyondwords_player_ui',
			value: 'enabled',
		} );
	} );

	const semverRegex =
		// eslint-disable-next-line max-len
		/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/;

	it( 'has BeyondWords site health info', () => {
		cy.visitPluginSiteHealth();

		cy.get( '#health-check-accordion-block-beyondwords' ).within( () => {
			// Plugin version
			cy.getSiteHealthValue( 'Plugin version' )
				.invoke( 'text' )
				.should( 'match', semverRegex );

			// REST API URL
			cy.getSiteHealthValue( 'REST API URL' ).should(
				'have.text',
				Cypress.env( 'apiUrl' )
			);

			// Communication with REST API
			cy.getSiteHealthValue( 'Communication with REST API' ).should(
				'have.text',
				'BeyondWords API is reachable'
			);

			// Compatible post types
			cy.getSiteHealthValue( 'Compatible post types' ).should(
				'have.text',
				'post, page, cpt_active, cpt_inactive'
			);

			// Integration method
			cy.getSiteHealthValue( 'Integration method' ).should(
				'have.text',
				'rest-api'
			);

			// API Key — masked except last 4 chars
			cy.getSiteHealthValue( 'API Key' )
				.invoke( 'text' )
				.then( ( text ) => {
					const visibleChars = Cypress.env( 'apiKey' ).slice( -4 );
					expect( text )
						.to.be.a( 'string' )
						.and.match( new RegExp( `[X]+${ visibleChars }$` ) );
				} );

			// Project ID
			cy.getSiteHealthValue( 'Project ID' ).should(
				'have.text',
				Cypress.env( 'projectId' )
			);

			// Include excerpt (Preferences tab)
			cy.getSiteHealthValue( 'Include excerpt' ).should(
				'have.text',
				'No'
			);

			// Player UI (Preferences tab)
			cy.getSiteHealthValue( 'Player UI' ).should(
				'have.text',
				'enabled'
			);

			// Preselect 'Generate audio' (Preferences tab)
			cy.getSiteHealthValue( 'Preselect ‘Generate audio’' ).should(
				'have.text',
				'{\n    "post": "1",\n    "page": "1",\n    "cpt_active": "1"\n}'
			);

			// Filter registries
			cy.getSiteHealthValue( 'Registered filters' ).should(
				'have.text',
				'None'
			);
			cy.getSiteHealthValue( 'Registered deprecated filters' ).should(
				'have.text',
				'None'
			);

			// Notice timestamps
			cy.getSiteHealthValue( 'Date Activated' )
				.invoke( 'text' )
				.should( 'satisfy', ( val ) => ! isNaN( Date.parse( val ) ) );
			cy.getSiteHealthValue( 'Review Notice Dismissed' ).should(
				'have.text',
				''
			);

			// Constants surfaced via add_constant()
			cy.getSiteHealthValue( 'BEYONDWORDS_AUTOREGENERATE' ).should(
				'have.text',
				'Undefined'
			);
		} );
	} );
} );
