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

	// Only test priority post types
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
				// pre-selects it (mock project: en_US).
				cy.get( 'select#beyondwords_language_code' ).should(
					'have.value',
					'en_US'
				);

				// Assert we have the expected Languages
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						// 148 languages + the "Select a language…" placeholder.
						expect( values ).to.have.length( 149 );
						expect( values[ 0 ] ).to.eq( 'Select a language…' );
						expect( values ).to.include( 'English (American)' );
						expect( values ).to.include( 'English (British)' );
						expect( values ).to.include( 'Welsh (Welsh)' );
					} );

				// The Model dropdown lists every model the language offers,
				// "Select a model" first, with the Standard bucket last.
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
							'Standard',
						] );
					} );

				// Only the language is pre-filled — no model picked yet, so the
				// Voice dropdown stays hidden until a model narrows it.
				cy.get( 'select#beyondwords_model' ).should( 'have.value', '' );
				cy.get( '#beyondwords-metabox-select-voice--voice-id' ).should(
					'not.be.visible'
				);

				// Standard model → only the non-ElevenLabs voices.
				cy.get( 'select#beyondwords_model' ).select( 'Standard' );
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

				// v3 → only the voices that offer it (Bridget + Caleb).
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

				// Multilingual v2 → only Bridget offers it.
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

				// The project's default language (en_US) is pre-selected.
				cy.get( 'select#beyondwords_language_code' ).should(
					'have.value',
					'en_US'
				);

				// Pick a Model, then a Voice within it.
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

				// Language, Model and Voice persist after a page refresh.
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (American)' );
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
				// changing the Model still narrows the Voice list rather than
				// emptying it (which would drop the saved voice on the next save).
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
} );
