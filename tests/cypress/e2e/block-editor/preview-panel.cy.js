/**
 * @group block-editor
 * @covers src/editor/components/preview-panel/,src/editor/components/error-notice/
 */

/* global cy, beforeEach, context, it */

context( 'Block Editor: Preview panel', () => {
	beforeEach( () => {
		cy.login();
	} );

	// The block poll only starts once the CDN player SDK global loads, which
	// isn't deterministic in CI, so a spinner assertion here would be flaky.

	it( 'shows a BeyondWords error message in the Preview panel', () => {
		cy.createTestPost( {
			title: 'Cypress Test: preview panel error',
			status: 'draft',
			postType: 'post',
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
				.find( '.beyondwords-sidebar__post-status-description--error' )
				.should( 'contain', 'Cypress preview error' );
		} );
	} );
} );
