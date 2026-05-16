/* global cy, beforeEach, context, it */

context( 'Block Editor: Insert BeyondWords Player', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `inserts a BeyondWords Player block into a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `BeyondWords Player block — ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();
				cy.checkGenerateAudio( postType );

				// Insert via @wordpress/data — avoids the brittle inserter UI.
				cy.window().then( ( win ) => {
					const block = win.wp.blocks.createBlock(
						'beyondwords/player'
					);
					win.wp.data
						.dispatch( 'core/block-editor' )
						.insertBlocks( block );
				} );

				cy.getEditorCanvasBody()
					.find( 'div[data-beyondwords-player="true"]' )
					.should( 'have.length', 1 );

				cy.publishWithConfirmation();
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );
			} );

			it( `inserts a [beyondwords_player] shortcode into a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `BeyondWords shortcode — ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();
				cy.checkGenerateAudio( postType );

				cy.window().then( ( win ) => {
					const block = win.wp.blocks.createBlock( 'core/shortcode', {
						text: '[beyondwords_player]',
					} );
					win.wp.data
						.dispatch( 'core/block-editor' )
						.insertBlocks( block );
				} );

				cy.publishWithConfirmation();
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
