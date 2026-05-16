/* global cy, beforeEach, context, expect, it */

context( 'Block Editor: Player Style', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	beforeEach( () => {
		cy.login();
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `lists the per-post Player Style options for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.openBeyondwordsEditorPanel();

				cy.getBlockEditorSelect( 'Player style' )
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

				// No plugin-level default in v7 — the dropdown starts unset.
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.should( 'have.value', '' );
			} );

			it( `persists "Large" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `Large player style — ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' ).select( 'Large' );
				cy.getBlockEditorCheckbox( 'Generate audio' ).check();
				cy.publishWithConfirmation();

				cy.viewPostViaSnackbar();
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1, { playerStyle: 'large' } );

				// Re-open the editor and confirm the meta survived.
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Large' );
			} );

			it( `persists "Video" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `Video player style — ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' ).select( 'Video' );
				cy.getBlockEditorCheckbox( 'Generate audio' ).check();
				cy.publishWithConfirmation();

				cy.viewPostViaSnackbar();
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1, { playerStyle: 'video' } );

				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Video' );
			} );
		} );
} );
