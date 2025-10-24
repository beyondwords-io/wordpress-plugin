/* global Cypress, cy, before, beforeEach, context, it */

context( 'Block Editor: Add Post', () => {
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

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	postTypes
		.filter( ( x ) => x.supported )
		.forEach( ( postType ) => {
			it( `can add a ${ postType.name } without audio`, () => {
				cy.publishPostWithoutAudio( {
					postType,
					title: `I can add a ${ postType.name } without audio`,
				} );

				// "View post"
				cy.viewPostViaSnackbar();

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [-] in the BeyondWords column
				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.contains( 'td.beyondwords.column-beyondwords', '—' );
					} );
			} );

			it( `can add a ${ postType.name } with audio`, () => {
				cy.createPost( {
					postType,
					title: `I can add a ${ postType.name } with audio`,
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
					} );
			} );

			it.skip( `can add a ${ postType.name } with "Pending review" audio`, () => {
				cy.createPost( {
					postType,
					title: `I can add a ${ postType.name } with "Pending Review" audio`,
				} );

				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostStatus( 'pending' );

				cy.get( '.editor-post-publish-button__button' ).click();

				cy.hasAdminPlayerInstances( 0 );

				// "Generate Audio" is replaced by "Pending" message'
				cy.get( 'input#beyondwords_generate_audio' ).should(
					'not.exist'
				);
				cy.contains(
					'.beyondwords-sidebar',
					'Listen to content saved as “Pending” in the BeyondWords dashboard.'
				);

				// "Pending review" message contains link to project
				cy.get( '.beyondwords-sidebar a' )
					.eq( 0 )
					.invoke( 'attr', 'href' )
					.should(
						'eq',
						`https://dash.beyondwords.io/dashboard/project/${ Cypress.env(
							'projectId'
						) }/content`
					);

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [tick] and "Pending" in the BeyondWords column
				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.contains( 'span.post-state', 'Pending' );
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
					} );
			} );
		} );

	postTypes
		.filter( ( x ) => ! x.supported )
		.forEach( ( postType ) => {
			it( `${ postType.name } has no BeyondWords support`, () => {
				// BeyondWords column should not exist
				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);
				cy.get( 'th#beyondwords.column-beyondwords' ).should(
					'not.exist'
				);

				// BeyondWords metabox should not exist
				cy.createPost( {
					postType,
				} );

				cy.get( 'div#beyondwords' ).should( 'not.exist' );
			} );
		} );
} );
