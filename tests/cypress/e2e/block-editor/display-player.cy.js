/**
 * @group block-editor
 * @covers src/editor/components/settings-panel/,src/editor/components/preview-panel/
 */

/* global cy, beforeEach, context, it */

/*
 * The "Display player" checkbox was removed in v7 — the Player "Embed" dropdown
 * now controls front-end visibility, where "None" hides the player (equivalent
 * to the old unchecked box). This spec exercises that visibility behaviour.
 */
context( 'Block Editor: Player visibility (Embed)', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	const embedSelect = () => cy.get( '.beyondwords--embed select' );

	// Only test priority post types
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

				// "View post" — player shows by default (Embed defaults to the
				// first asset).
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [tick] and no "Disabled" in the BeyondWords column
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

				cy.contains( 'a', 'BeyondWords sidebar' ).click();

				// Set Embed = None to hide the player.
				embedSelect().select( 'None', { force: true } );

				cy.savePost();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.hasPlayerInstances( 0 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [tick] and "Disabled" in the BeyondWords column
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

				cy.contains( 'a', 'BeyondWords sidebar' ).click();

				// Pick an asset again to reshow the player.
				embedSelect().select( 'Audio (post)', { force: true } );

				cy.savePost();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
