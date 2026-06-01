/**
 * @group classic-editor
 * @covers src/editor/components/settings-fields/
 */

/* global cy, before, beforeEach, after, context, it */

/*
 * The "Display player" checkbox was removed in v7 — the Player "Embed" dropdown
 * now controls front-end visibility, where "None" hides the player (equivalent
 * to the old unchecked box). This spec exercises that visibility behaviour.
 */
context( 'Classic Editor: Player visibility (Embed)', () => {
	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `${ postType.name } can hide and reshow the player via Embed`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'input#beyondwords_generate_audio' ).check();

				cy.classicSetPostTitle(
					`I can toggle player visibility for a ${ postType.name }`
				);

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				cy.get( '#sample-permalink' ).click();

				// Player shows by default (Embed defaults to the first asset).
				cy.hasPlayerInstances( 1 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get( 'a.row-title' ).click();
					} );

				// Set Embed = None to hide the player.
				cy.get( 'select#beyondwords_embed' ).select( 'None' );

				cy.get( '#publish' ).click(); // Click "Update" Button

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

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

				// Pick an asset again to reshow the player.
				cy.get( 'select#beyondwords_embed' ).select( 'Audio (post)' );

				cy.get( '#publish' ).click(); // Click "Update" Button

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
