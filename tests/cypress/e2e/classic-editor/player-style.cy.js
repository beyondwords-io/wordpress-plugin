/* global cy, before, beforeEach, after, context, expect, it */

context( 'Classic Editor: Player Style', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

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
			it( `lists the per-post Player Style options for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_player_style' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( labels ).to.deep.eq( [
							'',
							'Standard',
							'Small',
							'Large',
							'Video',
						] );
					} );

				// No plugin-level default in v7 — the metabox starts unset.
				cy.get( 'select#beyondwords_player_style' ).should(
					'have.value',
					''
				);
			} );

			it( `persists "Large" Player style for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_player_style' ).select( 'Large' );
				cy.classicSetPostTitle(
					`Large player style — ${ postType.name }`
				);
				cy.get( 'input#beyondwords_generate_audio' ).check();
				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				cy.get( '#message.notice-success' );
				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Large' );
			} );

			it( `persists "Video" Player style for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_player_style' ).select( 'Video' );
				cy.classicSetPostTitle(
					`Video player style — ${ postType.name }`
				);
				cy.get( 'input#beyondwords_generate_audio' ).check();
				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				cy.get( '#message.notice-success' );
				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Video' );
			} );
		} );
} );
