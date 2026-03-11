/* global Cypress, cy, beforeEach, context, it, describe */

/**
 * Focused edge-case tests for the GenerateAudio component.
 *
 * These complement the broader post-type × status matrix in add-post.cy.js
 * by covering panel sync, preselect guards, and meta key precedence.
 *
 * Each test uses a single post type to stay efficient — the core logic is
 * in the React component, not WordPress post type handling.
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

			// Sidebar checkbox should be checked via preselect
			cy.getBlockEditorCheckbox( 'Generate audio' ).should(
				'be.checked'
			);

			// Open pre-publish panel
			cy.get( '.editor-post-publish-button__button' ).click();

			// Pre-publish panel checkbox should also be checked
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

			// Uncheck in sidebar
			cy.getLabel( 'Generate audio' ).click();
			cy.getBlockEditorCheckbox( 'Generate audio' ).should(
				'not.be.checked'
			);

			// Open pre-publish panel
			cy.get( '.editor-post-publish-button__button' ).click();

			// Pre-publish panel checkbox should also be unchecked.
			// This validates the hasExplicitValue guard — the new
			// GenerateAudio instance must not re-preselect.
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

				// Sidebar checkbox should be checked via preselect
				cy.getBlockEditorCheckbox( 'Generate audio' ).should(
					'be.checked'
				);

				// Open pre-publish panel
				cy.get( '.editor-post-publish-button__button' ).click();

				// Verify checked in pre-publish, then uncheck
				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'be.checked' );

				cy.get( '.editor-post-publish-panel' )
					.contains( 'label', 'Generate audio' )
					.click();

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'not.be.checked' );

				// Confirm publish
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

				// Close post-publish panel
				cy.get( 'body' ).then( ( $body ) => {
					if (
						$body.find( 'button[aria-label="Close panel"]' ).length
					) {
						cy.get( 'button[aria-label="Close panel"]' ).click();
					}
				} );

				// Verify no audio on frontend
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

			cy.getBlockEditorCheckbox( 'Generate audio' ).should(
				'not.be.checked'
			);
		} );

		it( 'saved generate_audio=0 is not overridden in pre-publish panel', () => {
			cy.createTestPost( {
				title: 'Cypress Test: saved 0 prepublish guard',
				status: 'draft',
				postType: 'post',
			} ).then( ( postId ) => {
				// Explicitly set generate_audio to '0'
				cy.task( 'setPostMeta', {
					postId,
					metaKey: 'beyondwords_generate_audio',
					metaValue: '0',
				} );

				cy.visitPostEditorById( postId );

				cy.openBeyondwordsEditorPanel();

				// Sidebar should respect saved '0' despite preselect
				cy.getBlockEditorCheckbox( 'Generate audio' ).should(
					'not.be.checked'
				);

				// Open pre-publish panel — should also respect saved '0'
				cy.get( '.editor-post-publish-button__button' ).click();

				cy.get(
					'.editor-post-publish-panel .beyondwords--generate-audio input[type="checkbox"]'
				).should( 'not.be.checked' );
			} );
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

				// beyondwords_generate_audio=0 should take precedence
				cy.getBlockEditorCheckbox( 'Generate audio' ).should(
					'not.be.checked'
				);
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

				// Should fall back to speechkit_generate_audio=1
				cy.getBlockEditorCheckbox( 'Generate audio' ).should(
					'be.checked'
				);
			} );
		} );
	} );
} );
