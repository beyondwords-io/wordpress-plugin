/* global cy, before, beforeEach, context, it */

context( 'Block Editor: Display Player', () => {
	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it.skip( `hides and reshows the player for post type: ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can toggle player visibility for a ${ postType.name }`,
				} );

				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [tick] in the BeyondWords column
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

				cy.getBlockEditorCheckbox( 'Display player' ).should(
					'be.checked'
				);
				cy.getLabel( 'Display player' ).click();
				cy.getBlockEditorCheckbox( 'Display player' ).should(
					'not.be.checked'
				);

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

				cy.getBlockEditorCheckbox( 'Display player' ).should(
					'not.be.checked'
				);
				cy.getLabel( 'Display player' ).click();
				cy.getBlockEditorCheckbox( 'Display player' ).should(
					'be.checked'
				);

				cy.savePost();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );
			} );
		} );
} );
