/**
 * @group block-editor
 * @covers src/editor/components/preview-panel/,src/editor/components/error-notice/
 */

/* global cy, beforeEach, context, it */

context( 'Block Editor: Preview panel', () => {
	beforeEach( () => {
		cy.login();
	} );

	// Note: the block-editor player-preview poll is deliberately not covered
	// here. It only starts once the CDN player SDK global is available, which
	// isn't deterministic in CI, so a block spinner assertion is flaky. The
	// spinner and error/timeout states are covered end-to-end by the Classic
	// Editor specs (classic-editor/content-id.cy.js), whose poll runs
	// independently of the SDK; the block editor shares the same
	// src/editor/lib/poll-content-status.js loop.

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
