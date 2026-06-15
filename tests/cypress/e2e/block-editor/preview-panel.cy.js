/**
 * @group block-editor
 * @covers src/editor/components/preview-panel/,src/editor/components/error-notice/,src/editor/components/play-audio/
 */

/* global cy, beforeEach, context, it */

context( 'Block Editor: Preview panel', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../fixtures/post-types.json' );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `shows the player placeholder before audio exists for a ${ postType.name }`, () => {
				cy.createPost( { postType } );
				cy.openBeyondwordsPluginSidebar();

				// No generated content and no error: the Preview panel is always
				// present and never empty — it shows the placeholder rather than
				// the live player.
				cy.get( '.beyondwords-sidebar__preview' )
					.find( '.components-placeholder' )
					.should(
						'contain',
						'The BeyondWords audio player will appear here'
					);
			} );

			it( `shows the live player (not the placeholder) once content exists for a ${ postType.name }`, () => {
				cy.createTestPostWithAudio( {
					title: `Cypress Test: preview player — ${ postType.name }`,
					status: 'draft',
					postType: postType.slug,
				} ).then( ( postId ) => {
					cy.visitPostEditorById( postId );
					cy.openBeyondwordsPluginSidebar();

					// With a project + content id the player can load, so the
					// Preview panel shows the player box and not the placeholder.
					cy.get( '.beyondwords-sidebar__preview' )
						.find( '.beyondwords-player-box-wrapper' )
						.should( 'exist' );
					cy.get( '.beyondwords-sidebar__preview' )
						.find( '.components-placeholder' )
						.should( 'not.exist' );
				} );
			} );

			it( `shows a BeyondWords error message in the Preview panel for a ${ postType.name }`, () => {
				cy.createTestPost( {
					title: `Cypress Test: preview panel error — ${ postType.name }`,
					status: 'draft',
					postType: postType.slug,
				} ).then( ( postId ) => {
					cy.task( 'setPostMeta', {
						postId,
						metaKey: 'beyondwords_error_message',
						metaValue: 'Cypress preview error',
					} );

					cy.visitPostEditorById( postId );
					cy.openBeyondwordsPluginSidebar();

					// The Preview panel surfaces the error even without generated
					// content, mirroring the document-settings panel.
					cy.get( '.beyondwords-sidebar__preview' )
						.find(
							'.beyondwords-sidebar__post-status-description--error'
						)
						.should( 'contain', 'Cypress preview error' );
				} );
			} );
		} );
} );
