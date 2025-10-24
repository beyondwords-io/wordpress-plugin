/* global Cypress, cy, before, beforeEach, describe, expect, it */

describe( 'WordPress Filters', () => {
	before( () => {
		cy.task( 'setupDatabase' );
		// Setup plugin settings once for all tests
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
		// Clean up test posts from previous test (fast - 100-500ms)
		cy.cleanupTestPosts();
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

				// Frontend should have a player div with expected SDK params from
				// tests/fixtures/wp-content/plugins/beyondwords-filter-player-sdk-params
				cy.viewPostViaSnackbar();
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1, {
					iconColor: 'rgb(234, 75, 151)',
					highlightSections: 'all-none',
					clickableSections: 'none',
					segmentWidgetSections: 'body',
					segmentWidgetPosition: '10-oclock',
				} );

				cy.deactivatePlugin( 'beyondwords-filter-player-sdk-params' );
			} );

			it( `can filter Player script onload for a ${ postType.name }`, () => {
				cy.activatePlugin( 'beyondwords-filter-player-script-onload' );

				cy.publishPostWithAudio( {
					postType,
					title: `I can filter Player script onload for a ${ postType.name }`,
				} );

				// Frontend should have a player div
				cy.viewPostViaSnackbar();
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

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
		} );
} );
