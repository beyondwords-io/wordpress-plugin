/**
 * @group site-health
 * @covers src/site-health/class-site-health.php
 */

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
			cy.getSiteHealthValue( 'Plugin version' )
				.invoke( 'text' )
				.should( 'match', semverRegex );

			cy.getSiteHealthValue( 'REST API URL' ).should(
				'have.text',
				Cypress.expose('apiUrl')
			);

			cy.getSiteHealthValue( 'Communication with REST API' ).should(
				'have.text',
				'BeyondWords API is reachable'
			);

			cy.getSiteHealthValue( 'Compatible post types' ).should(
				'have.text',
				'post, page, cpt_active, cpt_inactive'
			);

			cy.getSiteHealthValue( 'Integration method' ).should(
				'have.text',
				'rest-api'
			);

			// API Key — masked except last 4 chars
			cy.env( [ 'apiKey' ] ).then( ( { apiKey } ) => {
				cy.getSiteHealthValue( 'API Key' )
					.invoke( 'text' )
					.should( 'match', new RegExp( `[X]+${ apiKey.slice( -4 ) }$` ) );
			} );

			cy.getSiteHealthValue( 'Project ID' ).should(
				'have.text',
				Cypress.expose('projectId')
			);

			cy.getSiteHealthValue( 'Include excerpt' ).should(
				'have.text',
				'No'
			);

			cy.getSiteHealthValue( 'Player UI' ).should(
				'have.text',
				'enabled'
			);

			cy.getSiteHealthValue( 'Preselect ‘Generate audio’' ).should(
				'have.text',
				'{\n    "post": {\n        "mode": "all"\n    },\n    "page": {\n        "mode": "all"\n    },\n    "cpt_active": {\n        "mode": "all"\n    }\n}'
			);

			cy.getSiteHealthValue( 'Registered filters' ).should(
				'have.text',
				'None'
			);
			cy.getSiteHealthValue( 'Registered deprecated filters' ).should(
				'have.text',
				'None'
			);

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
