/**
 * @group block-editor
 * @covers src/editor/components/preview-panel/,src/editor/components/error-notice/,src/editor/components/play-audio/
 */

/* global Cypress, cy, beforeEach, context, it, expect */

const PLAYER_SCRIPT_SRC =
	'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';

/**
 * Stub the BeyondWords player SDK instead of loading the real CDN script.
 *
 * The real script's load time isn't deterministic in CI, which is why the
 * poll-before-embed behaviour (src/editor/components/play-audio/hooks.js)
 * wasn't covered here before; stubbing it removes that non-determinism so
 * `window.BeyondWords.Player` calls can be asserted on directly.
 */
function stubPlayerScript() {
	cy.intercept( 'GET', PLAYER_SCRIPT_SRC, {
		headers: { 'content-type': 'application/javascript' },
		body: `
			window.__beyondwordsPlayerCalls = [];
			window.BeyondWords = {
				Player: function ( params ) {
					window.__beyondwordsPlayerCalls.push( params );
					this.destroy = function () {};
				},
			};
		`,
	} ).as( 'playerScript' );
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

			cy.intercept(
				'GET',
				// apiFetch appends `_locale`, hence the trailing wildcard.
				`**/beyondwords/v1/projects/${ projectId }/content/${ contentId }*`,
				{ statusCode: 200, body: { status: 'processing' } }
			).as( 'statusCheck' );

			stubPlayerScript();

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

			cy.intercept(
				'GET',
				// apiFetch appends `_locale`, hence the trailing wildcard.
				`**/beyondwords/v1/projects/${ projectId }/content/${ contentId }*`,
				{ statusCode: 200, body: { status: 'processed' } }
			).as( 'statusCheck' );

			stubPlayerScript();

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
