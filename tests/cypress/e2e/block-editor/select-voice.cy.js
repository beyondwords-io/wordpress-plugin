/**
 * @group block-editor
 * @covers src/editor/components/settings-panel/,src/settings/store/
 */

/* global cy, beforeEach, context, expect, it */

context( 'Block Editor: Select Voice', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	beforeEach( () => {
		cy.login();
	} );

	// Voice names for English: "Select a voice" first, then distinct names
	// (ElevenLabs "Bridget" appears once despite having three models).
	const voiceNames = [
		'Select a voice',
		'Ada (Multilingual)',
		'Ava (Multilingual)',
		'Ollie (Multilingual)',
		'Bridget',
		'Caleb',
	];

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `can set a Voice for a ${ postType.name } if languages are selected`, () => {
				cy.createPost( {
					postType,
					title: `I can set a Voice for a ${ postType.name }`,
				} );

				// "Generate audio" lives in the document panel; toggle it before
				// switching to the plugin sidebar, where the Voice settings live.
				cy.checkGenerateAudio( postType );

				// Voice/Language are exposed only in the plugin sidebar now.
				cy.openBeyondwordsPluginSidebar();

				// "Customize" is opt-in and off by default, so the Language/Voice
				// fields are hidden until it is enabled.
				cy.get(
					'.beyondwords--customize input[type="checkbox"]'
				).should( 'not.be.checked' );
				cy.contains(
					'.components-select-control label',
					'Language'
				).should( 'not.exist' );

				cy.get( '.beyondwords--customize label' ).click( {
					force: true,
				} );

				// Assert we have the expected Languages
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						// 148 languages + the "Select a language…" placeholder.
						expect( values ).to.have.length( 149 );
						expect( values[ 0 ] ).to.eq( 'Select a language…' );
						expect( values ).to.include( 'English (American)' );
						expect( values ).to.include( 'English (British)' );
						expect( values ).to.include( 'Welsh (Welsh)' );
					} );

				// Select a Language
				cy.getBlockEditorSelect( 'Language' ).select(
					'English (American)',
					{ force: true }
				);

				// Assert we have the expected Voices
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( values ).to.deep.eq( voiceNames );
					} );

				// Select a Voice
				cy.getBlockEditorSelect( 'Voice' ).select(
					'Ollie (Multilingual)',
					{ force: true }
				);

				// Select another Language
				cy.getBlockEditorSelect( 'Language' ).select(
					'English (British)',
					{ force: true }
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
						expect( values ).to.deep.eq( voiceNames );
					} );

				// Select a Voice
				cy.getBlockEditorSelect( 'Voice' ).select(
					'Ava (Multilingual)',
					{ force: true }
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
							// eslint-disable-next-line no-unused-expressions
							expect( meta?.beyondwords_body_voice_id ).to.not.be
								.empty;
						} );
					} );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player appears frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// The beyondwords-* <head> meta tags are only emitted for the
				// client-side (Magic Embed) integration now, so they're not
				// asserted here (covered by the PHPUnit Head tests).

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsPluginSidebar();

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

				// A post with an explicit language/voice opens with Customize on,
				// so the fields are already visible after reload.
				cy.get(
					'.beyondwords--customize input[type="checkbox"]'
				).should( 'be.checked' );

				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );

				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Ava (Multilingual)' );
			} );
		} );
} );
