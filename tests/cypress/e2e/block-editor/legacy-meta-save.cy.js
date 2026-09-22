/**
 * @group block-editor
 * @covers src/post/class-sync.php
 * @covers src/editor/components/settings-panel/
 */

/* global cy, beforeEach, context, expect, it */

/**
 * The block editor sends the whole meta object back on save, including legacy
 * non-scalar rows; see doc/rest-meta-visibility.md.
 */
context( 'Block Editor: save with legacy non-scalar meta', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	beforeEach( () => {
		cy.login();
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `publishes a ${ postType.name } after a settings change`, () => {
				const sentMeta = [];

				cy.createTestPost( {
					postType: postType.slug,
					status: 'draft',
					title: 'Cypress Test legacy meta',
				} ).then( ( postId ) => {
					cy.task( 'insertLegacyArrayPostMeta', {
						postId,
						metaKey: 'speechkit_error_message',
					} );

					cy.intercept(
						'POST',
						new RegExp(
							`wp(?:/|%2F)v2(?:/|%2F)\\w+(?:/|%2F)${ postId }(?:[?&]|$)`
						),
						( req ) => {
							if ( req.body?.meta ) {
								sentMeta.push( req.body.meta );
							}
						}
					);

					cy.visitPostEditorById( postId );
					cy.openBeyondwordsPluginSidebar();

					cy.get( '.beyondwords--output select' ).select( 'Video', {
						force: true,
					} );

					cy.publishWithConfirmation();

					// Before the fix this was null, which core rejected with a 500.
					cy.wrap( sentMeta ).should( ( sent ) => {
						expect(
							sent.some(
								( meta ) =>
									'speechkit_error_message' in meta &&
									'' === meta.speechkit_error_message
							)
						).to.equal( true );
					} );

					cy.task( 'getPostMeta', {
						postId,
						metaKey: 'beyondwords_output',
					} ).should( 'eq', 'video' );
				} );
			} );
		} );
} );
