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
			it( `shows the Model dropdown only for multi-model voices (${ postType.name })`, () => {
				cy.createPost( { postType } );

				// "Customize" is opt-in and off by default, so the Language/Voice
				// fields are hidden until it is enabled.
				cy.get( '#beyondwords-metabox-select-voice--fields' ).should(
					'not.be.visible'
				);
				cy.get( '#beyondwords_customize' ).check();

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

				// Select a Language
				cy.get( 'select#beyondwords_language_code' ).select(
					'English (American)'
				);

				// The Voice dropdown lists distinct names, "Select a voice"
				// first; ElevenLabs "Bridget" appears once despite three models.
				cy.get( 'select#beyondwords_voice' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Ada (Multilingual)',
							'Ava (Multilingual)',
							'Ollie (Multilingual)',
							'Bridget',
							'Caleb',
						] );
					} );

				// Multi-model voice → Model dropdown appears with its variants.
				cy.get( 'select#beyondwords_voice' ).select( 'Bridget' );
				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Multilingual v2',
							'v3',
							'Flash v2.5',
						] );
					} );

				// Single-model ElevenLabs voice → Model dropdown hidden.
				cy.get( 'select#beyondwords_voice' ).select( 'Caleb' );
				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'not.be.visible'
				);

				// Non-ElevenLabs voice → Model dropdown also hidden.
				cy.get( 'select#beyondwords_voice' ).select(
					'Ava (Multilingual)'
				);
				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'not.be.visible'
				);
			} );

			it( `persists the selected Voice + Model for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( '#beyondwords_customize' ).check();

				cy.get( 'select#beyondwords_language_code' ).select(
					'English (American)'
				);

				// Pick a multi-model voice + a specific Model variant.
				cy.get( 'select#beyondwords_voice' ).select( 'Bridget' );
				cy.get( 'select#beyondwords_voice_id' ).select( 'Flash v2.5' );

				// The saved field (#beyondwords_voice_id) holds the variant id.
				cy.get( 'select#beyondwords_voice_id' ).should(
					'have.value',
					'9003'
				);

				cy.classicSetPostTitle(
					`I can select a custom Voice + Model for a ${ postType.name }`
				);

				// Publish without generating audio to keep the test deterministic.
				cy.get( 'input#beyondwords_generate_audio' ).uncheck();

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// A post with an explicit language/voice opens with Customize on,
				// so the fields are visible after the page refresh.
				cy.get( '#beyondwords_customize' ).should( 'be.checked' );

				// Language, Voice and Model persist after a page refresh.
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (American)' );
				cy.get( 'select#beyondwords_voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Bridget' );
				cy.get( '#beyondwords-metabox-select-voice--model' ).should(
					'be.visible'
				);
				cy.get( 'select#beyondwords_voice_id' )
					.find( 'option:selected' )
					.should( 'have.text', 'Flash v2.5' );
			} );
		} );
} );
