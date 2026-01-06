/* global cy, Cypress, beforeEach, context, expect, it */

context( 'Block Editor: Select Voice', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	beforeEach( () => {
		cy.login();
	} );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `can set a Voice for a ${ postType.name } if languages are selected`, () => {
				cy.createPost( {
					postType,
					title: `I can set a Voice for a ${ postType.name }`,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				// Assert we have the expected Voices
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( values ).to.have.length( 148 );
						expect( values ).to.include( 'English (American)' );
						expect( values ).to.include( 'English (British)' );
						expect( values ).to.include( 'Welsh (Welsh)' );
					} );

				// Select a Language
				cy.getBlockEditorSelect( 'Language' ).select(
					'English (American)'
				);

				// Assert we have the expected Voices
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( values ).to.deep.eq( [
							'Ada (Multilingual)',
							'Ava (Multilingual)',
							'Ollie (Multilingual)',
						] );
					} );

				// Select a Voice
				cy.getBlockEditorSelect( 'Voice' ).select(
					'Ollie (Multilingual)'
				);

				// Select another Language
				cy.getBlockEditorSelect( 'Language' ).select(
					'English (British)'
				);

				// Verify the language selection took effect
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );

				// Assert we have the expected Voices
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( values ).to.deep.eq( [
							'Ada (Multilingual)',
							'Ava (Multilingual)',
							'Ollie (Multilingual)',
						] );
					} );

				// Select a Voice
				cy.getBlockEditorSelect( 'Voice' ).select(
					'Ava (Multilingual)'
				);

				// Verify meta is correctly set in the data store BEFORE publishing
				cy.window()
					.its( 'wp.data' )
					.then( ( data ) => {
						cy.wrap( null, { timeout: 10000 } ).should( () => {
							const meta = data
								.select( 'core/editor' )
								.getEditedPostAttribute( 'meta' );
							// eslint-disable-next-line no-unused-expressions
							expect( meta?.beyondwords_language_code ).to.not.be
								.empty;
						} );
					} );

				cy.checkGenerateAudio( postType );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player appears frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// Check HTML head for voice and language meta tags
				cy.get( 'head' )
					.find( 'meta[name="beyondwords-title-voice-id"]' )
					.should( 'have.attr', 'content', '2517' )
					.should(
						'have.attr',
						'data-beyondwords-title-voice-id',
						'2517'
					);

				cy.get( 'head' )
					.find( 'meta[name="beyondwords-body-voice-id"]' )
					.should( 'have.attr', 'content', '2517' )
					.should(
						'have.attr',
						'data-beyondwords-body-voice-id',
						'2517'
					);

				cy.get( 'head' )
					.find( 'meta[name="beyondwords-summary-voice-id"]' )
					.should( 'have.attr', 'content', '2517' )
					.should(
						'have.attr',
						'data-beyondwords-summary-voice-id',
						'2517'
					);

				cy.get( 'head' )
					.find( 'meta[name="beyondwords-article-language"]' )
					.should( 'have.attr', 'content', 'en_GB' )
					.should(
						'have.attr',
						'data-beyondwords-article-language',
						'en_GB'
					);

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();

				// Verify meta is correctly set in the data store AFTER publishing
				cy.window()
					.its( 'wp.data' )
					.then( ( data ) => {
						cy.wrap( null, { timeout: 10000 } ).should( () => {
							const meta = data
								.select( 'core/editor' )
								.getEditedPostAttribute( 'meta' );
							// eslint-disable-next-line no-unused-expressions
							expect( meta?.beyondwords_language_code ).to.not.be
								.empty;
						} );
					} );

				// Now check Voice (will be loaded since Language is correct)
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );

				// Now check Voice (will be loaded since Language is correct)
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Ava (Multilingual)' );
			} );
		} );
} );
