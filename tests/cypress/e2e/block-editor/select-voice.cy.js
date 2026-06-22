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

	// --- Edge cases: the picker logic is post-type agnostic, so these run once. ---
	const edgePostType = postTypes.find( ( x ) => x.priority );

	// Run an assertion against the editor's current post meta.
	const expectMeta = ( assertFn ) =>
		cy
			.window()
			.its( 'wp.data' )
			.then( ( data ) => {
				cy.wrap( null, { timeout: 10000 } ).should( () => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					assertFn( meta );
				} );
			} );

	// Enable Customize and wait for the default language (en_US) to resolve.
	const enableCustomizeForEnUs = () => {
		cy.checkGenerateAudio( edgePostType );
		cy.openBeyondwordsPluginSidebar();
		cy.get( '.beyondwords--customize label' ).click( { force: true } );
		cy.getBlockEditorSelect( 'Language' )
			.find( 'option:selected' )
			.should( 'have.text', 'English (American)' );
	};

	it( 'stores a distinct voice id for the same name under each Model', () => {
		cy.createPost( {
			postType: edgePostType,
			title: 'Same name, different model',
		} );
		enableCustomizeForEnUs();

		// "Bridget" is the auto-selected first voice in each ElevenLabs bucket,
		// but each (name, model) pair is a different voice id.
		[
			[ 'Multilingual v2', '9001' ],
			[ 'v3', '9002' ],
			[ 'Flash v2.5', '9003' ],
		].forEach( ( [ model, voiceId ] ) => {
			cy.getBlockEditorSelect( 'Model' ).select( model, { force: true } );
			cy.getBlockEditorSelect( 'Voice' )
				.find( 'option:selected' )
				.should( 'have.text', 'Bridget' );
			expectMeta( ( meta ) =>
				expect( String( meta?.beyondwords_body_voice_id ) ).to.eq(
					voiceId
				)
			);
		} );
	} );

	it( 'hides the Voice list when the Model is cleared', () => {
		cy.createPost( { postType: edgePostType, title: 'Clear the model' } );
		enableCustomizeForEnUs();

		cy.getBlockEditorSelect( 'Model' ).select( 'v3', { force: true } );
		cy.getBlockEditorSelect( 'Voice' )
			.find( 'option:selected' )
			.should( 'have.text', 'Bridget' );

		// Returning to the placeholder clears the voice and hides the Voice list.
		cy.getBlockEditorSelect( 'Model' ).select( 'Select a model', {
			force: true,
		} );
		cy.contains( '.components-select-control label', 'Voice' ).should(
			'not.exist'
		);
		expectMeta( ( meta ) =>
			expect( meta?.beyondwords_body_voice_id || '' ).to.eq( '' )
		);
	} );

	it( 'clears the language and voice when Customize is turned off', () => {
		cy.createPost( {
			postType: edgePostType,
			title: 'Toggle customize off',
		} );
		enableCustomizeForEnUs();

		cy.getBlockEditorSelect( 'Model' ).select( 'v3', { force: true } );
		expectMeta( ( meta ) => {
			expect( meta?.beyondwords_language_code || '' ).to.not.eq( '' );
			expect( meta?.beyondwords_body_voice_id || '' ).to.not.eq( '' );
		} );

		// Turning Customize off reverts to the project defaults: meta is cleared
		// and the fields are hidden.
		cy.get( '.beyondwords--customize label' ).click( { force: true } );
		cy.contains( '.components-select-control label', 'Language' ).should(
			'not.exist'
		);
		expectMeta( ( meta ) => {
			expect( meta?.beyondwords_language_code || '' ).to.eq( '' );
			expect( meta?.beyondwords_body_voice_id || '' ).to.eq( '' );
		} );
	} );

	it( 'hides the Model dropdown when the language offers a single model', () => {
		// Every voice shares one ElevenLabs model → no Model dropdown; the Voice
		// list shows immediately.
		cy.intercept( 'GET', '**/beyondwords/v1/languages/*/voices*', {
			body: [
				{
					id: 9010,
					name: 'Caleb',
					service: 'ElevenLabs',
					model_id: 'eleven_v3',
					language: { code: 'en_US' },
				},
			],
		} ).as( 'singleModelVoices' );

		cy.createPost( {
			postType: edgePostType,
			title: 'Single model language',
		} );
		enableCustomizeForEnUs();

		cy.contains( '.components-select-control label', 'Model' ).should(
			'not.exist'
		);
		cy.getBlockEditorSelect( 'Voice' )
			.find( 'option' )
			.should( ( $els ) => {
				expect( optionLabels( $els ) ).to.deep.eq( [
					'Select a voice',
					'Caleb',
				] );
			} );
	} );

	it( 'lists Standard voices directly when the language has no ElevenLabs models', () => {
		cy.intercept( 'GET', '**/beyondwords/v1/languages/*/voices*', {
			body: [
				{
					id: 3555,
					name: 'Ada (Multilingual)',
					language: { code: 'en_US' },
				},
				{
					id: 2517,
					name: 'Ava (Multilingual)',
					language: { code: 'en_US' },
				},
				{
					id: 3558,
					name: 'Ollie (Multilingual)',
					language: { code: 'en_US' },
				},
			],
		} ).as( 'standardVoices' );

		cy.createPost( {
			postType: edgePostType,
			title: 'Standard only language',
		} );
		enableCustomizeForEnUs();

		cy.contains( '.components-select-control label', 'Model' ).should(
			'not.exist'
		);
		cy.getBlockEditorSelect( 'Voice' )
			.find( 'option' )
			.should( ( $els ) => {
				expect( optionLabels( $els ) ).to.deep.eq( [
					'Select a voice',
					'Ada (Multilingual)',
					'Ava (Multilingual)',
					'Ollie (Multilingual)',
				] );
			} );
	} );
} );
