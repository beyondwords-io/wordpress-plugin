/**
 * @group classic-editor
 * @covers src/editor/components/select-voice/
 */

/* global cy, before, beforeEach, after, context, expect, it */

context( 'Classic Editor: Select Voice', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	const optionLabels = ( $els ) =>
		[ ...$els ].map( ( el ) => el.innerText.trim() );

	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `narrows the Voice list by the selected Model (${ postType.name })`, () => {
				cy.createPost( { postType } );

				// "Customize" is opt-in and off by default, so the fields are
				// hidden until it is enabled.
				cy.get( '#beyondwords-metabox-select-voice--fields' ).should(
					'not.be.visible'
				);
				cy.get( '#beyondwords_customize' ).check();

				// Enabling Customize fetches the project's default language and
				// pre-selects its name + accent (mock project: en_US).
				cy.get( 'select#beyondwords_language_code' ).should(
					'have.value',
					'en_US'
				);
				cy.get( 'select#beyondwords_language_name' ).should(
					'have.value',
					'English'
				);

				cy.get( 'select#beyondwords_language_name' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						expect( values ).to.have.length( 80 );
						expect( values[ 0 ] ).to.eq( 'Select a language…' );
						expect( values ).to.include( 'English' );
						expect( values ).to.include( 'Welsh' );
					} );

				// The Accent select carries the language code, so it is the
				// field that gets submitted.
				cy.get( '#beyondwords-metabox-select-voice--accent' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						expect( values ).to.have.length( 14 );
						expect( values ).to.include( 'American' );
						expect( values ).to.include( 'British' );
					} );

				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_model' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a model',
							'Multilingual v2',
							'v3',
							'Flash v2.5',
							'Legacy',
						] );
					} );

				// Only the language is pre-filled — no model picked yet, so the
				// Voice dropdown stays hidden until a model narrows it.
				cy.get( 'select#beyondwords_model' ).should( 'have.value', '' );
				cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
					'not.be.visible'
				);

				// Standard model → only the non-ElevenLabs voices.
				cy.get( 'select#beyondwords_model' ).select( 'Legacy' );
				cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Ada (Multilingual)',
							'Ava (Multilingual)',
							'Ollie (Multilingual)',
						] );
					} );

				cy.get( 'select#beyondwords_model' ).select( 'v3' );
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Bridget',
							'Caleb',
						] );
					} );

				cy.get( 'select#beyondwords_model' ).select(
					'Multilingual v2'
				);
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Bridget',
						] );
					} );
			} );

			it( `persists the selected Model + Voice for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( '#beyondwords_customize' ).check();

				cy.get( 'select#beyondwords_language_code' ).should(
					'have.value',
					'en_US'
				);

				cy.get( 'select#beyondwords_model' ).select( 'Flash v2.5' );
				cy.get( 'select#beyondwords_voice_id' ).select( 'Bridget' );

				// The saved field (#beyondwords_voice_id) holds the voice id that
				// carries the (name, model) pair.
				cy.get( 'select#beyondwords_voice_id' ).should(
					'have.value',
					'9003'
				);

				cy.classicSetPostTitle(
					`I can select a custom Model + Voice for a ${ postType.name }`
				);

				// Publish without generating audio to keep the test deterministic.
				cy.get( 'input#beyondwords_generate_audio' ).uncheck();

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// A post with an explicit language/voice opens with Customize on,
				// so the fields are visible after the page refresh.
				cy.get( '#beyondwords_customize' ).should( 'be.checked' );

				cy.get( 'select#beyondwords_language_name' ).should(
					'have.value',
					'English'
				);
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option:selected' )
					.should( 'have.text', 'American' );
				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_model' )
					.find( 'option:selected' )
					.should( 'have.text', 'Flash v2.5' );
				cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option:selected' )
					.should( 'have.text', 'Bridget' );

				// Regression: after reload the in-memory voices are hydrated, so
				// changing the Model narrows the Voice list instead of emptying it.
				cy.get( 'select#beyondwords_model' ).select( 'v3' );
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Bridget',
							'Caleb',
						] );
					} );
			} );
		} );

	// --- Edge cases: run once for a single post type. ---
	const edgePostType = postTypes.find( ( x ) => x.priority );

	it( 'stores a distinct voice id for the same name under each Model', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		// Selecting a Model auto-selects "Bridget" (the bucket's first voice);
		// each (name, model) pair maps to a different voice id.
		[
			[ 'Multilingual v2', '9001' ],
			[ 'v3', '9002' ],
			[ 'Flash v2.5', '9003' ],
		].forEach( ( [ model, voiceId ] ) => {
			cy.get( 'select#beyondwords_model' ).select( model );
			cy.get( 'select#beyondwords_voice_id' )
				.find( 'option:selected' )
				.should( 'have.text', 'Bridget' );
			cy.get( 'select#beyondwords_voice_id' ).should(
				'have.value',
				voiceId
			);
		} );
	} );

	it( 'hides the Voice list when the Model is cleared', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		cy.get( 'select#beyondwords_model' ).select( 'v3' );
		cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
			'be.visible'
		);

		cy.get( 'select#beyondwords_model' ).select( 'Select a model' );
		cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
			'not.be.visible'
		);
	} );

	it( 'picking a language name auto-selects its first accent', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		// Welsh has a single accent, so the Accent select hides itself while
		// still holding the code.
		cy.get( 'select#beyondwords_language_name' ).select( 'Welsh' );
		cy.get( '#beyondwords-metabox-select-voice--accent' ).should(
			'not.be.visible'
		);
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'cy_GB'
		);

		cy.get( 'select#beyondwords_language_name' ).select( 'English' );
		cy.get( '#beyondwords-metabox-select-voice--accent' ).should(
			'be.visible'
		);
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_NZ'
		);
		cy.get( 'select#beyondwords_language_code' )
			.find( 'option' )
			.should( 'have.length', 14 );
	} );

	it( 'switching accent re-fetches voices and seeds the default voice', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		// en_GB's default body voice is Ollie, a Legacy voice.
		cy.get( 'select#beyondwords_language_code' ).select( 'British' );
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_GB'
		);

		// The mock's English voices are all American-primary, so none are
		// native to en_GB and only "All" lists them.
		cy.get( 'select#beyondwords_native' ).select( 'All' );
		cy.get( 'select#beyondwords_model' )
			.find( 'option:selected' )
			.should( 'have.text', 'Legacy' );
		cy.get( 'select#beyondwords_voice_id' )
			.find( 'option:selected' )
			.should( 'have.text', 'Ollie (Multilingual)' );
	} );

	it( 'filters the Voice list by Native', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		// Klaus is German-primary, so Native excludes him from en_US.
		cy.get( 'select#beyondwords_native' ).should( 'have.value', 'native' );
		cy.get( 'select#beyondwords_model' ).select( 'Legacy' );
		cy.get( 'select#beyondwords_voice_id' )
			.find( 'option' )
			.should( ( $els ) => {
				expect( optionLabels( $els ) ).to.deep.eq( [
					'Select a voice',
					'Ada (Multilingual)',
					'Ava (Multilingual)',
					'Ollie (Multilingual)',
				] );
			} );

		cy.get( 'select#beyondwords_native' ).select( 'All' );
		cy.get( 'select#beyondwords_voice_id' )
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

	it( 'reverts to project defaults when Customize is turned off', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);
		cy.get( 'select#beyondwords_model' ).select( 'Flash v2.5' );
		cy.get( 'select#beyondwords_voice_id' ).should( 'have.value', '9003' );

		// Turning Customize off clears the selects so they submit empty.
		cy.get( '#beyondwords_customize' ).uncheck();
		cy.get( '#beyondwords-metabox-select-voice--fields' ).should(
			'not.be.visible'
		);
		cy.get( 'select#beyondwords_language_name' ).should( 'have.value', '' );
		cy.get( 'select#beyondwords_language_code' ).should( 'have.value', '' );
		cy.get( 'select#beyondwords_voice_id' ).should( 'have.value', '' );

		cy.classicSetPostTitle(
			`Customize off reverts a ${ edgePostType.name } to defaults`
		);
		cy.get( 'input#beyondwords_generate_audio' ).uncheck();
		cy.contains( 'input[type="submit"]', 'Publish' ).click();

		// With both meta values removed, the post reopens un-customized.
		cy.get( '#beyondwords_customize' ).should( 'not.be.checked' );
		cy.get( '#beyondwords-metabox-select-voice--fields' ).should(
			'not.be.visible'
		);
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

		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);

		cy.get( '#beyondwords-metabox-select-voice--model' ).should(
			'not.be.visible'
		);
		cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
			'be.visible'
		);
		cy.get( 'select#beyondwords_voice_id' )
			.find( 'option' )
			.should( ( $els ) => {
				expect( optionLabels( $els ) ).to.deep.eq( [
					'Select a voice',
					'Caleb',
				] );
			} );
	} );

	it( 'shows the loader in place of Model and Voice while voices resolve', () => {
		cy.createPost( { postType: edgePostType } );
		cy.get( '#beyondwords_customize' ).check();
		cy.get( 'select#beyondwords_language_code' ).should(
			'have.value',
			'en_US'
		);
		cy.get( '#beyondwords-metabox-select-voice--model' ).should(
			'be.visible'
		);

		// Delay the voices response so the resolving state is observable.
		cy.intercept(
			'GET',
			'**/beyondwords/v1/languages/*/voices*',
			( req ) => {
				req.on( 'response', ( res ) => {
					res.setDelay( 1500 );
				} );
			}
		);

		cy.get( 'select#beyondwords_language_code' ).select( 'British' );
		cy.get( '.beyondwords-settings__loader' ).should( 'be.visible' );
		cy.get( '#beyondwords-metabox-select-voice--model' ).should(
			'not.be.visible'
		);
		cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
			'not.be.visible'
		);

		cy.get( '.beyondwords-settings__loader', { timeout: 10000 } ).should(
			'not.be.visible'
		);
	} );
} );
