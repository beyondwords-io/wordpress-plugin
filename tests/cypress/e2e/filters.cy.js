/* global Cypress, cy, beforeEach, describe, expect, it */

describe( 'WordPress Filters', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `can filter Player SDK params for a ${ postType.name }`, () => {
				cy.task( 'activatePlugin', 'beyondwords-filter-player-sdk-params' );

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

				cy.task( 'deactivatePlugin', 'beyondwords-filter-player-sdk-params' );
			} );

			it( `can filter Player script onload for a ${ postType.name }`, () => {
				cy.task( 'activatePlugin', 'beyondwords-filter-player-script-onload' );

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

				cy.task( 'deactivatePlugin', 'beyondwords-filter-player-script-onload' );
			} );
		} );

	it( 'displays two players when shortcode is placed outside the_content', () => {
		cy.task( 'activatePlugin', 'beyondwords-shortcode-in-footer' );

		cy.createTestPostWithAudio( {
			title: 'Shortcode in footer test',
		} ).then( ( postId ) => {
			cy.visit( `/?p=${ postId }` );

			// Should have 2 player script tags: auto + footer shortcode
			cy.hasPlayerInstances( 2 );

			// Verify contexts via the data attribute
			cy.get( '[data-beyondwords-player-context="auto"]' ).should(
				'have.length',
				1
			);
			cy.get( '[data-beyondwords-player-context="shortcode"]' ).should(
				'have.length',
				1
			);
		} );

		cy.task( 'deactivatePlugin', 'beyondwords-shortcode-in-footer' );
	} );
} );
