/**
 * @group block-editor
 * @covers src/editor/components/preview-panel/,src/editor/components/error-notice/,src/editor/components/play-audio/
 */

/* global Cypress, cy, beforeEach, context, it, expect */

/**
 * Stub the BeyondWords player SDK before the editor boots.
 *
 * With the namespace already present the plugin never appends the CDN script,
 * so `window.BeyondWords.Player` calls can be asserted on directly without
 * depending on a cross-origin load — which cy.intercept cannot stub reliably
 * once an earlier spec has left the script in the browser cache.
 */
function stubPlayerSdk() {
	cy.on( 'window:before:load', ( win ) => {
		win.__beyondwordsPlayerCalls = [];
		win.BeyondWords = {
			Player: function ( params ) {
				win.__beyondwordsPlayerCalls.push( params );
				this.destroy = function () {};
			},
		};
	} );
}

/**
 * Match the content-status poll by its content ID.
 *
 * `apiFetch` percent-encodes the REST path into `rest_route` under the test
 * site's plain permalinks, so a slash-separated glob never matches — the ID
 * is the one part of the URL that survives either form intact.
 *
 * @param {string} contentId BeyondWords content ID.
 *
 * @return {RegExp} Matcher for the content-status request.
 */
function contentStatusRoute( contentId ) {
	return new RegExp( contentId );
}

context( 'Block Editor: Preview panel', () => {
	beforeEach( () => {
		cy.login();
	} );

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

	it( 'waits for a still-processing voice-customised preview instead of embedding it early', () => {
		const projectId = Cypress.expose( 'projectId' );
		const contentId = 'cypress-voice-preview-still-processing';

		cy.createTestPost( {
			title: 'Cypress Test: preview panel voice customised, still processing',
			status: 'publish',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_project_id',
				metaValue: projectId,
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_content_id',
				metaValue: contentId,
			} );
			// Voice customisation: a non-default voice/model can take longer to
			// process than the project default, widening the window where the
			// content is still processing when the editor is opened.
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_language_code',
				metaValue: 'en_US',
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_body_voice_id',
				metaValue: '9001',
			} );

			cy.intercept( 'GET', contentStatusRoute( contentId ), {
				statusCode: 200,
				body: { status: 'processing' },
			} ).as( 'statusCheck' );

			stubPlayerSdk();

			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.wait( '@statusCheck' );

			cy.get( '.beyondwords-sidebar__preview' )
				.find( '.beyondwords-player-loading' )
				.should( 'contain', 'Generating' );

			// The still-processing content must never reach the player SDK —
			// embedding it would 404 (and the CDN would cache that 404).
			cy.window()
				.its( '__beyondwordsPlayerCalls' )
				.should( 'have.length', 0 );
		} );
	} );

	it( 'embeds a voice-customised preview once it has finished processing', () => {
		const projectId = Cypress.expose( 'projectId' );
		const contentId = 'cypress-voice-preview-processed';

		cy.createTestPost( {
			title: 'Cypress Test: preview panel voice customised, processed',
			status: 'publish',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_project_id',
				metaValue: projectId,
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_content_id',
				metaValue: contentId,
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_language_code',
				metaValue: 'en_US',
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_body_voice_id',
				metaValue: '9001',
			} );

			cy.intercept( 'GET', contentStatusRoute( contentId ), {
				statusCode: 200,
				body: { status: 'processed' },
			} ).as( 'statusCheck' );

			stubPlayerSdk();

			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.wait( '@statusCheck' );

			// PlayAudio also mounts in the core "Post" tab's document-setting
			// panel (src/editor/block/document-setting/index.js), so more than
			// one instance can embed for the same post — assert every embed
			// used the right content, not an exact count.
			cy.window()
				.its( '__beyondwordsPlayerCalls' )
				.should( 'have.length.at.least', 1 )
				.then( ( calls ) => {
					calls.forEach( ( call ) => {
						expect( call ).to.include( { contentId, projectId } );
					} );
				} );

			cy.get( '.beyondwords-sidebar__preview' )
				.find( '.beyondwords-player-loading' )
				.should( 'not.exist' );
		} );
	} );
} );
