/**
 * @group block-editor
 * @covers src/editor/components/settings-panel/,src/settings/store/
 */

/* global cy, beforeEach, context, expect, it */

context( 'Block Editor: Select Voice', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	const optionLabels = ( $els ) =>
		[ ...$els ].map( ( el ) => el.innerText.trim() );

	beforeEach( () => {
		cy.login();
	} );

	// The Model dropdown for English: "Select a model" first, then the
	// ElevenLabs models a voice offers (default leading), Standard bucket last.
	const modelLabels = [
		'Select a model',
		'Multilingual v2',
		'v3',
		'Flash v2.5',
		'Standard',
	];

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `narrows the Voice list by the selected Model for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can set a Voice for a ${ postType.name }`,
				} );

				// "Generate audio" lives in the document panel; toggle it before
				// switching to the plugin sidebar, where the Voice settings live.
				cy.checkGenerateAudio( postType );

				// Voice/Language are exposed only in the plugin sidebar now.
				cy.openBeyondwordsPluginSidebar();

				// "Customize" is opt-in and off by default, so the Language/Model/
				// Voice fields are hidden until it is enabled.
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

				// Enabling Customize fetches the project's default language and
				// pre-selects it (mock project: en_US → English (American)).
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (American)' );

				// Only the language is pre-filled — no model picked yet, so the
				// Model dropdown opens on its placeholder…
				cy.getBlockEditorSelect( 'Model' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq(
							modelLabels
						);
					} );
				cy.getBlockEditorSelect( 'Model' )
					.find( 'option:selected' )
					.should( 'have.text', 'Select a model' );

				// …and the Voice dropdown stays hidden until a model narrows it.
				cy.contains(
					'.components-select-control label',
					'Voice'
				).should( 'not.exist' );

				// The Language dropdown still lists every language, placeholder first.
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						// 148 languages + the "Select a language…" placeholder.
						expect( values ).to.have.length( 149 );
						expect( values[ 0 ] ).to.eq( 'Select a language…' );
						expect( values ).to.include( 'English (British)' );
					} );

				// Changing the Language re-fetches its voices and seeds that
				// language's default body voice (en_GB → Ollie, a Standard voice),
				// so the Model resolves to Standard and the Voice to Ollie.
				cy.getBlockEditorSelect( 'Language' ).select(
					'English (British)',
					{ force: true }
				);
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );
				cy.getBlockEditorSelect( 'Model' )
					.find( 'option:selected' )
					.should( 'have.text', 'Standard' );
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Ollie (Multilingual)' );

				// Pick the v3 model → the Voice list narrows to the voices that
				// offer it (Bridget + Caleb), the first auto-selected.
				cy.getBlockEditorSelect( 'Model' ).select( 'v3', {
					force: true,
				} );
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Bridget',
							'Caleb',
						] );
					} );
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Bridget' );

				// Pick a specific Voice within the model.
				cy.getBlockEditorSelect( 'Voice' ).select( 'Caleb', {
					force: true,
				} );

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

				// Language, Model and Voice persist after reload (derived from the
				// saved voice id).
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );
				cy.getBlockEditorSelect( 'Model' )
					.find( 'option:selected' )
					.should( 'have.text', 'v3' );
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Caleb' );
			} );
		} );
} );
