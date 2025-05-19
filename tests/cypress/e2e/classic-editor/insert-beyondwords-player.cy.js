/* global cy, before, beforeEach, after, context, it */

context( 'Classic Editor: Insert BeyondWords Player', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.deactivatePlugin( 'classic-editor' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `can add multiple player blocks into a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'input#beyondwords_generate_audio' ).check();

				// Add 3x players
				cy.get( 'div[aria-label="Insert BeyondWords player"]' )
					.children( 'button' )
					.click();
				cy.get( 'div[aria-label="Insert BeyondWords player"]' )
					.children( 'button' )
					.click();
				cy.get( 'div[aria-label="Insert BeyondWords player"]' )
					.children( 'button' )
					.click();

				// Count 3x players in editor iframe
				cy.getTinyMceIframeBody()
					.find(
						'div[data-beyondwords-player="true"][contenteditable="false"]'
					)
					.should( 'have.length', 3 );

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				// Count 3x players in frontend
				cy.get(
					'div[data-beyondwords-player="true"][contenteditable="false"]'
				).should( 'have.length', 3 );
			} );

			it( `can add shortcodes into a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'input#beyondwords_generate_audio' ).check();

				// Focus TinyMCE
				cy.getTinyMceIframeBody().click();

				// Add 3x shortcodes
				cy.getTinyMceIframeBody().type(
					// eslint-disable-next-line max-len
					'Shortcode 1:{enter}[beyondwords_player]{enter}Shortcode 2:{enter}[beyondwords_player]{enter}Shortcode 3:{enter}[beyondwords_player]{enter}'
				);

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				// Count 3x players in frontend
				cy.get(
					'div[data-beyondwords-player="true"][contenteditable="false"]'
				).should( 'have.length', 3 );
			} );
		} );
} );
