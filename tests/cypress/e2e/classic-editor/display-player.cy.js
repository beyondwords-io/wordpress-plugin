/* global cy, before, beforeEach, after, context, it */

context( 'Classic Editor: Display Player', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.deactivatePlugin( 'classic-editor' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `${ postType.name } can hide and reshow the player`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'input#beyondwords_generate_audio' ).check();

				cy.classicSetPostTitle(
					`I can toggle player visibility for a ${ postType.name }`
				);

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				cy.get( 'input#beyondwords_display_player' ).should(
					'be.checked'
				);
				cy.get( 'input#beyondwords_display_player' ).uncheck();

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

				cy.get( 'input#beyondwords_display_player' ).should(
					'not.be.checked'
				);
				cy.get( 'input#beyondwords_display_player' ).check();

				cy.get( '#publish' ).click(); // Click "Update" Button

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
