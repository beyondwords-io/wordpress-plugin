/**
 * @group block-editor
 * @covers src/editor/components/settings-panel/,src/editor/components/preview-panel/
 */

/* global cy, beforeEach, context, it */

/*
 * The v7 Player "Embed" dropdown replaced the "Display player" checkbox;
 * Embed "None" hides the player. This spec exercises that behaviour.
 */
context( 'Block Editor: Player visibility (Embed)', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// The Embed dropdown now lives only in the plugin sidebar.
	const embedSelect = () => cy.get( '.beyondwords--embed select' );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `hides and reshows the player via Embed for post type: ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can toggle player visibility for a ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.publishWithConfirmation();

				// Player shows by default — Embed defaults to the first asset.
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.get(
							'td.beyondwords.column-beyondwords > span.beyondwords--disabled'
						).should( 'not.exist' );
						cy.get( 'a.row-title' ).click();
					} );

				cy.openBeyondwordsPluginSidebar();

				embedSelect().select( 'None', { force: true } );

				cy.savePost();

				cy.viewPostViaSnackbar();

				cy.hasPlayerInstances( 0 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.contains(
							'td.beyondwords.column-beyondwords > span.beyondwords--disabled',
							'Disabled'
						);
						cy.get( 'a.row-title' ).click();
					} );

				cy.openBeyondwordsPluginSidebar();

				embedSelect().select( 'Audio (post)', { force: true } );

				cy.savePost();

				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
