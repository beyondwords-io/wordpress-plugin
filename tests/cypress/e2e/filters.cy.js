/* global Cypress, cy, before, beforeEach, describe, expect, it */

describe( 'WordPress Filters', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `can filter Player SDK params for a ${ postType.name }`, () => {
				cy.activatePlugin( 'beyondwords-filter-player-sdk-params' );

				cy.publishPostWithAudio( {
					postType,
					title: `I can filter Player SDK params for a ${ postType.name }`,
				} );

				// Admin should have latest player
				cy.getAdminPlayer().should( 'exist' );

				// Frontend should have a player div
				cy.viewPostViaSnackbar();
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.getFrontendPlayer().should( 'exist' );

				// window.BeyondWords should contain desired SDK params from
				// tests/fixtures/wp-content/plugins/beyondwords-filter-player-sdk-params
				cy.window().then( ( win ) => {
					cy.wait( 500 );
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].iconColor
					).to.eq( 'rgb(234, 75, 151)' );
					expect(
						win.BeyondWords.Player.instances()[ 0 ]
							.highlightSections
					).to.eq( 'all-none' );
					expect(
						win.BeyondWords.Player.instances()[ 0 ]
							.clickableSections
					).to.eq( 'none' );
					expect(
						win.BeyondWords.Player.instances()[ 0 ]
							.segmentWidgetSections
					).to.eq( 'body' );
					expect(
						win.BeyondWords.Player.instances()[ 0 ]
							.segmentWidgetPosition
					).to.eq( '10-oclock' );
				} );

				cy.deactivatePlugin( 'beyondwords-filter-player-sdk-params' );
			} );

			it( `can filter Player script onload for a ${ postType.name }`, () => {
				cy.activatePlugin( 'beyondwords-filter-player-script-onload' );

				cy.publishPostWithAudio( {
					postType,
					title: `I can filter Player script onload for a ${ postType.name }`,
				} );

				// Admin should have latest player
				cy.getAdminPlayer().should( 'exist' );

				// Frontend should have a player div
				cy.viewPostViaSnackbar();
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.getFrontendPlayer().should( 'exist' );

				// Check we have called console.log with expected values from testing plugin
				cy.get( '@consoleLog' ).should( ( log ) => {
					const spy = log.getCalls();
					const { args } = spy[ 0 ];

					expect( args[ 0 ] ).to.equal( '🔊' );

					const { projectId, contentId } = args[ 1 ];

					expect( projectId ).to.equal(
						parseInt( Cypress.env( 'projectId' ) )
					);
					expect( contentId ).to.equal( Cypress.env( 'contentId' ) );
				} );

				cy.deactivatePlugin( 'beyondwords-filter-player-sdk-params' );
			} );

			it( `can filter Player inline script tag for a ${ postType.name }`, () => {
				cy.activatePlugin(
					'beyondwords-filter-player-inline-script-tag'
				);

				cy.publishPostWithAudio( {
					postType,
					title: `I see the inline player script for a ${ postType.name }`,
				} );

				// Admin should have latest player
				cy.getAdminPlayer().should( 'exist' );

				// Frontend SHOULD NOT have enqueued player script
				cy.viewPostViaSnackbar();
				cy.getEnqueuedPlayerScriptTag().should( 'not.exist' );
				cy.getFrontendPlayer().should( 'exist' );

				cy.deactivatePlugin(
					'beyondwords-filter-player-inline-script-tag'
				);

				cy.publishPostWithAudio( {
					postType,
					title: `I see the legacy player script for a ${ postType.name }`,
				} );

				// Admin should have latest player
				cy.getAdminPlayer().should( 'exist' );

				// Frontend SHOULD have enqueued player script
				cy.viewPostViaSnackbar();
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.getFrontendPlayer().should( 'exist' );
			} );
		} );
} );
