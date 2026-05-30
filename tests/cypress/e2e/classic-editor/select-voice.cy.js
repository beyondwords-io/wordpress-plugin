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

				// Assert we have the expected Languages
				cy.get( 'select#beyondwords_language_code' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = optionLabels( $els );
						expect( values ).to.have.length( 148 );
						expect( values ).to.include( 'English (American)' );
						expect( values ).to.include( 'English (British)' );
						expect( values ).to.include( 'Welsh (Welsh)' );
					} );

				// Select a Language
				cy.get( 'select#beyondwords_language_code' ).select(
					'English (American)'
				);

				// The Voice dropdown lists distinct names, "Project default"
				// first; ElevenLabs "Bridget" appears once despite three models.
				cy.get( 'select#beyondwords_voice' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Project default',
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

				// Publish WITHOUT generating audio: a sync would write the
				// API's returned voice back over our pick (the mock always
				// returns Ava), which would mask the metabox save we're testing.
				cy.get( 'input#beyondwords_generate_audio' ).uncheck();

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

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
