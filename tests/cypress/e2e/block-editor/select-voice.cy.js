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
		'Legacy',
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
				// pre-selects its name + accent (mock project: en_US →
				// English + American).
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English' );
				cy.getBlockEditorSelect( 'Accent' )
					.find( 'option:selected' )
					.should( 'have.text', 'American' );

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

				// The Language dropdown lists each language NAME once,
				// placeholder first; the Accent dropdown lists the accents
				// for the chosen name.
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						// 79 language names + the "Select a language…" placeholder.
						expect( values ).to.have.length( 80 );
						expect( values[ 0 ] ).to.eq( 'Select a language…' );
						expect( values ).to.include( 'English' );
						expect( values ).to.include( 'Welsh' );
					} );
				cy.getBlockEditorSelect( 'Accent' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						// English has 14 accents.
						expect( values ).to.have.length( 14 );
						expect( values ).to.include( 'American' );
						expect( values ).to.include( 'British' );
					} );

				// Changing the Accent re-fetches its voices and seeds that
				// language's default body voice (en_GB → Ollie, a Legacy
				// voice).
				cy.getBlockEditorSelect( 'Accent' ).select( 'British', {
					force: true,
				} );
				cy.getBlockEditorSelect( 'Accent' )
					.find( 'option:selected' )
					.should( 'have.text', 'British' );

				// The mock's English voices are all American-primary
				// multilingual voices, so none are native to British English.
				// Switch Native to "All" to list them; the seeded default
				// (Ollie) is shown either way.
				cy.getBlockEditorSelect( 'Native' ).select( 'All', {
					force: true,
				} );
				cy.getBlockEditorSelect( 'Model' )
					.find( 'option:selected' )
					.should( 'have.text', 'Legacy' );
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

				// Language, Accent, Model and Voice persist after reload
				// (derived from the saved language code + voice id). Caleb is
				// not native to en_GB, so the Native filter seeds to "All" on
				// load to keep the saved voice visible.
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English' );
				cy.getBlockEditorSelect( 'Accent' )
					.find( 'option:selected' )
					.should( 'have.text', 'British' );
				cy.getBlockEditorSelect( 'Native' )
					.find( 'option:selected' )
					.should( 'have.text', 'All' );
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
			.should( 'have.text', 'English' );
		cy.getBlockEditorSelect( 'Accent' )
			.find( 'option:selected' )
			.should( 'have.text', 'American' );
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

	it( 'hides the Accent select for single-accent languages', () => {
		cy.createPost( {
			postType: edgePostType,
			title: 'Single-accent language',
		} );
		enableCustomizeForEnUs();

		// Welsh offers a single accent: it is auto-selected (storing its
		// code) and the Accent select hides — nothing to choose.
		cy.getBlockEditorSelect( 'Language' ).select( 'Welsh', {
			force: true,
		} );
		cy.contains( '.components-select-control label', 'Accent' ).should(
			'not.exist'
		);
		expectMeta( ( meta ) =>
			expect( meta?.beyondwords_language_code ).to.eq( 'cy_GB' )
		);
	} );

	it( 'filters the Voice list by Native', () => {
		cy.createPost( {
			postType: edgePostType,
			title: 'Native filter',
		} );
		enableCustomizeForEnUs();

		// Default is Native: the non-native Klaus (German primary, speaks
		// American English) is excluded from the Legacy bucket.
		cy.getBlockEditorSelect( 'Native' )
			.find( 'option:selected' )
			.should( 'have.text', 'Native' );
		cy.getBlockEditorSelect( 'Model' ).select( 'Legacy', { force: true } );
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

		// Switching to All adds the non-native Klaus to the Legacy bucket.
		cy.getBlockEditorSelect( 'Native' ).select( 'All', { force: true } );
		cy.getBlockEditorSelect( 'Voice' )
			.find( 'option' )
			.should( ( $els ) => {
				expect( optionLabels( $els ) ).to.deep.eq( [
					'Select a voice',
					'Ada (Multilingual)',
					'Ava (Multilingual)',
					'Ollie (Multilingual)',
					'Klaus (Multilingual)',
				] );
			} );
	} );

	it( 'shows a spinner in place of Model and Voice while voices resolve', () => {
		cy.createPost( {
			postType: edgePostType,
			title: 'Voice resolving spinner',
		} );
		enableCustomizeForEnUs();
		// Wait for the default language's voices to load (Model appears).
		cy.getBlockEditorSelect( 'Model' ).should( 'exist' );

		// Delay the voices response so the resolving state is observable. The
		// real (mock) data still arrives — only its delivery is delayed — so
		// the store is not stubbed empty.
		cy.intercept(
			'GET',
			'**/beyondwords/v1/languages/*/voices*',
			( req ) => {
				req.on( 'response', ( res ) => {
					res.setDelay( 4000 );
				} );
			}
		);

		// Switching Accent re-fetches the voices. While that resolves, the
		// Model + Voice group is hidden and a spinner takes their place — it is
		// never wedged between two select fields. Both are asserted together so
		// a fast resolve can't slip between two separate commands, and via our
		// own class rather than the component's (which varies by WP version).
		cy.getBlockEditorSelect( 'Accent' ).select( 'British', {
			force: true,
		} );
		cy.get( 'body' ).should( ( $body ) => {
			expect(
				$body.find( '.beyondwords--voice-fields:visible' ),
				'Model + Voice hidden while resolving'
			).to.have.length( 0 );
			expect(
				$body.find( '.beyondwords--voice-spinner' ),
				'spinner shown in their place'
			).to.have.length( 1 );
		} );

		// Recovery — Model + Voice returning once the fetch resolves — is
		// covered by the other tests, which use the un-intercepted mock:
		// cy.intercept does not re-populate the block editor's wp.data voices
		// store, so it can't be asserted here.
	} );

	// The single-bucket branch (a language offering one model, so the Model
	// dropdown is hidden and the Voice list shows directly) is covered
	// end-to-end by the classic-editor spec. The block editor reads voices
	// through the wp.data store, which cy.intercept does not stub reliably,
	// so it is not duplicated here.
} );
