/**
 * @group block-editor
 * @covers src/editor/components/generate-audio/,src/post/class-sync.php,src/settings/class-preselect.php
 */

/* global Cypress, cy, before, beforeEach, afterEach, context, it, describe, expect */

/**
 * Edge-case tests for GenerateAudio, complementing the post-type × status
 * matrix in add-post.cy.js: panel sync, preselect guards, meta precedence.
 */
context( 'Block Editor: Generate Audio', () => {
	beforeEach( () => {
		cy.login();
	} );

	describe( 'Pre-publish panel sync', () => {
		it( 'pre-publish panel reflects sidebar checked state', () => {
			cy.createPost( {
				title: 'Cypress Test: prepublish sync checked',
				postType: { slug: 'post' },
			} );

			cy.openBeyondwordsEditorPanel();

			cy.getGenerateAudioCheckbox().should( 'be.checked' );

			cy.get( '.editor-post-publish-button__button' ).click();

			cy.get(
				'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
			).should( 'be.checked' );
		} );

		it( 'pre-publish panel reflects sidebar unchecked state', () => {
			cy.createPost( {
				title: 'Cypress Test: prepublish sync unchecked',
				postType: { slug: 'post' },
			} );

			cy.openBeyondwordsEditorPanel();

			// Preselect applies via a useEffect — wait for the box to settle,
			// otherwise the uncheck races the effect and is undone.
			cy.getGenerateAudioCheckbox().should( 'be.checked' );

			// Toggle the input directly — clicking the CheckboxControl label
			// double-fires the change event and lands back on checked.
			cy.getGenerateAudioCheckbox().uncheck( {
				force: true,
			} );
			cy.getGenerateAudioCheckbox().should( 'not.be.checked' );

			cy.get( '.editor-post-publish-button__button' ).click();

			// Validates the hasExplicitValue guard — the new GenerateAudio
			// instance must not re-preselect.
			cy.get(
				'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
			).should( 'not.be.checked' );
		} );

		it( 'unchecking in pre-publish panel prevents audio generation', () => {
			cy.createTestPost( {
				title: 'Cypress Test: prepublish uncheck',
				status: 'draft',
				postType: 'post',
			} ).then( ( postId ) => {
				cy.visitPostEditorById( postId );

				cy.openBeyondwordsEditorPanel();

				cy.getGenerateAudioCheckbox().should( 'be.checked' );

				cy.get( '.editor-post-publish-button__button' ).click();

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'be.checked' );

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).uncheck( { force: true } );

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'not.be.checked' );

				cy.get(
					'.editor-post-publish-panel__header-publish-button > .components-button'
				).click();

				cy.get( '.editor-post-publish-panel' ).should( 'exist' );

				// Close "Patterns" modal if it opens (WordPress 6.6+)
				cy.get( 'body' ).then( ( $body ) => {
					if ( $body.find( '.components-modal__frame' ).length ) {
						cy.get(
							'.components-modal__frame button.components-button[aria-label="Close"]'
						).click();
						cy.get( '.components-modal__frame' ).should(
							'not.exist'
						);
					}
				} );

				cy.get( 'body' ).then( ( $body ) => {
					if (
						$body.find( 'button[aria-label="Close panel"]' ).length
					) {
						cy.get( 'button[aria-label="Close panel"]' ).click();
					}
				} );

				cy.viewPostById( postId );
				cy.getPlayerScriptTag().should( 'not.exist' );
				cy.hasPlayerInstances( 0 );
			} );
		} );
	} );

	describe( 'Preselect guards', () => {
		it( 'does not preselect for post type without preselect enabled', () => {
			cy.createPost( {
				title: 'Cypress Test: no preselect cpt_inactive',
				postType: { slug: 'cpt_inactive' },
			} );

			cy.openBeyondwordsEditorPanel();

			cy.getGenerateAudioCheckbox().should( 'not.be.checked' );
		} );

		it( 'saved generate_audio=0 is not overridden in pre-publish panel', () => {
			cy.createTestPost( {
				title: 'Cypress Test: saved 0 prepublish guard',
				status: 'draft',
				postType: 'post',
			} ).then( ( postId ) => {
				cy.task( 'setPostMeta', {
					postId,
					metaKey: 'beyondwords_generate_audio',
					metaValue: '0',
				} );

				cy.visitPostEditorById( postId );

				cy.openBeyondwordsEditorPanel();

				cy.getGenerateAudioCheckbox().should( 'not.be.checked' );

				cy.get( '.editor-post-publish-button__button' ).click();

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'not.be.checked' );
			} );
		} );
	} );

	describe( 'Auto-preselect does not dirty the post', () => {
		it( 'reflects preselect without writing meta or dirtying the post', () => {
			// No title → nothing else can dirty the post.
			cy.createPost( { postType: { slug: 'post' } } );

			cy.openBeyondwordsEditorPanel();

			// Default preselect for 'post' is mode 'all' → box shows checked
			// (derived from the setting, not written to meta).
			cy.getGenerateAudioCheckbox().should( 'be.checked' );

			cy.window().then( ( win ) => {
				const editor = win.wp.data.select( 'core/editor' );

				// The toggle is derived — we never write '1' to meta for
				// preselect (the value stays at its registered default).
				const meta = editor.getEditedPostAttribute( 'meta' ) || {};
				expect( meta.beyondwords_generate_audio ).to.not.equal( '1' );

				expect( editor.isEditedPostDirty() ).to.eq( false );
			} );
		} );
	} );

	describe( 'Respects all preselect modes for a post type', () => {
		let newsId;

		before( () => {
			cy.task( 'createTerm', {
				taxonomy: 'category',
				name: 'CypressBwNews',
			} ).then( ( id ) => {
				newsId = id;
			} );
		} );

		const setPreselect = ( value ) =>
			cy.task( 'updateOptionJson', {
				name: 'beyondwords_preselect',
				value,
			} );

		afterEach( () => {
			// Restore the default seed so later specs see post = 'all'.
			setPreselect( {
				post: { mode: 'all' },
				page: { mode: 'all' },
				cpt_active: { mode: 'all' },
			} );
		} );

		it( 'Post not selected → Generate audio is not preselected', () => {
			setPreselect( {} ); // 'post' absent → off.
			cy.createPost( { postType: { slug: 'post' } } );
			cy.openBeyondwordsEditorPanel();
			cy.getGenerateAudioCheckbox().should( 'not.be.checked' );
		} );

		it( 'All selected → Generate audio is preselected', () => {
			setPreselect( { post: { mode: 'all' } } );
			cy.createPost( { postType: { slug: 'post' } } );
			cy.openBeyondwordsEditorPanel();
			cy.getGenerateAudioCheckbox().should( 'be.checked' );
		} );

		it( 'Terms selected → preselected only when the post has a matching term', () => {
			setPreselect( {
				post: { mode: 'terms', terms: { category: [ newsId ] } },
			} );
			cy.createPost( { postType: { slug: 'post' } } );
			cy.openBeyondwordsEditorPanel();

			cy.getGenerateAudioCheckbox().should( 'not.be.checked' );

			cy.window().then( ( win ) => {
				win.wp.data
					.dispatch( 'core/editor' )
					.editPost( { categories: [ newsId ] } );
			} );
			cy.getGenerateAudioCheckbox().should( 'be.checked' );

			// Remove it → not preselected (bidirectional).
			cy.window().then( ( win ) => {
				win.wp.data
					.dispatch( 'core/editor' )
					.editPost( { categories: [] } );
			} );
			cy.getGenerateAudioCheckbox().should( 'not.be.checked' );
		} );
	} );

	describe( 'Meta key precedence', () => {
		it( 'beyondwords_generate_audio=0 takes precedence over speechkit_generate_audio=1', () => {
			cy.createTestPost( {
				title: 'Cypress Test: meta precedence bw0 sk1',
				status: 'publish',
				postType: 'post',
			} ).then( ( postId ) => {
				cy.task( 'setPostMeta', {
					postId,
					metaKey: 'beyondwords_generate_audio',
					metaValue: '0',
				} );
				cy.task( 'setPostMeta', {
					postId,
					metaKey: 'speechkit_generate_audio',
					metaValue: '1',
				} );

				cy.visitPostEditorById( postId );

				cy.openBeyondwordsEditorPanel();

				cy.getGenerateAudioCheckbox().should( 'not.be.checked' );
			} );
		} );

		it( 'falls back to speechkit_generate_audio when beyondwords meta is unset', () => {
			cy.createTestPost( {
				title: 'Cypress Test: meta fallback speechkit',
				status: 'publish',
				postType: 'post',
			} ).then( ( postId ) => {
				cy.task( 'setPostMeta', {
					postId,
					metaKey: 'speechkit_generate_audio',
					metaValue: '1',
				} );

				cy.visitPostEditorById( postId );

				cy.openBeyondwordsEditorPanel();

				cy.getGenerateAudioCheckbox().should( 'be.checked' );
			} );
		} );
	} );
} );
