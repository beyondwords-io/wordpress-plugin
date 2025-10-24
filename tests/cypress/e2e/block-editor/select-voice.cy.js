/* global cy, before, beforeEach, context, expect, it */

context( 'Block Editor: Select Voice', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	before( () => {
		cy.task( 'setupDatabase' );
		// One-time setup for all tests
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
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

				cy.checkGenerateAudio( postType );

				// TODO check Language/Voice in Sidebar

				// TODO check Language/Voice in Prepublish panel

				cy.publishWithConfirmation();

				// Check Language/Voice has been saved by refreshing the page
				cy.reload();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (British)' );
				cy.getBlockEditorSelect( 'Voice' )
					.find( 'option:selected' )
					.should( 'have.text', 'Ava (Multilingual)' );
			} );
		} );
} );
