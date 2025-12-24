/* global Cypress, cy, beforeEach, context, it */

context( 'Block Editor: Add Post', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// @todo Set to 'draft', 'future', 'publish', 'pending', 'private'
	const postStatuses = [ 'publish' ];

	postTypes
		.filter( ( x ) => x.supported )
		.forEach( ( postType ) => {
			postStatuses.forEach( ( postStatus ) => {
				it.only( `can add a ${ postStatus } ${ postType.name } without audio`, () => {
					cy.createPost( {
						title: `can add a ${ postStatus } ${ postType.name } without audio`,
						status: postStatus,
						postType,
						postStatus,
					} );

					cy.openBeyondwordsEditorPanel();

					cy.uncheckGenerateAudio( postType );

					cy.publishWithConfirmation();

					// "View post"
					cy.viewPostViaSnackbar();

					cy.getPlayerScriptTag().should( 'not.exist' );
					cy.hasPlayerInstances( 0 );

					cy.visit(
						`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
					);

					// See a [-] in the BeyondWords column
					cy.get( 'tbody tr' )
						.eq( 0 )
						.within( () => {
							cy.contains(
								'td.beyondwords.column-beyondwords',
								'—'
							);
						} );
				} );

				it( `can add a ${ postStatus } ${ postType.name } with audio`, () => {
					cy.createPost( {
						title: `I can add a ${ postStatus } ${ postType.name } with audio`,
						status: postStatus,
						postType,
						postStatus,
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

				it( `can update a ${ postStatus } ${ postType.name } without audio`, () => {
					cy.createTestPost( {
						title: `can update a ${ postStatus } ${ postType.name } without audio`,
						status: postStatus,
						postType: postType.slug,
					} ).then( ( postId ) => {
						cy.visitPostEditorById( postId );

						cy.openBeyondwordsEditorPanel();

						cy.uncheckGenerateAudio( postType );

						cy.savePost();

						// "View post"
						cy.viewPostViaSnackbar();

						cy.getPlayerScriptTag().should( 'not.exist' );
						cy.hasPlayerInstances( 0 );

						cy.visit(
							'/wp-admin/edit.php?post_type=' +
								`${ postType.slug }&orderby=date&order=desc`
						);

						// See a [-] in the BeyondWords column
						cy.get( 'tbody tr' )
							.eq( 0 )
							.within( () => {
								cy.contains(
									'td.beyondwords.column-beyondwords',
									'—'
								);
							} );
					} );
				} );

				it( `can update a ${ postStatus } ${ postType.name } with audio`, () => {
					cy.createTestPostWithAudio( {
						title: `can update a ${ postStatus } ${ postType.name } with audio`,
						status: postStatus,
						postType: postType.slug,
					} ).then( ( postId ) => {
						cy.task( 'setPostMeta', {
							postId,
							metaKey: 'beyondwords_error_message',
							metaValue: '404 Not Found',
						} );

						cy.visitPostEditorById( postId );

						cy.openBeyondwordsEditorPanel();

						cy.getBlockEditorCheckbox( 'Generate audio' ).should(
							'be.checked'
						);

						cy.setPostTitle(
							`I can update a ${ postStatus } ${ postType.name } with audio, EDITED`
						);

						cy.savePost();

						// "View post"
						cy.viewPostViaSnackbar();

						cy.getPlayerScriptTag().should( 'exist' );
						cy.hasPlayerInstances( 1 );

						cy.visit(
							'/wp-admin/edit.php?post_type=' +
								`${ postType.slug }&orderby=date&order=desc`
						);

						// See a [tick] in the BeyondWords column
						cy.get( 'tbody tr' )
							.eq( 0 )
							.within( () => {
								cy.get(
									'td.beyondwords.column-beyondwords > ' +
										'span.dashicons.dashicons-yes'
								);
							} );
					} );
				} );
			} );

			// @todo Skip flaky test until mock API is replaced with http intercepts.
			it.skip( `can add a ${ postType.name } with "Pending review" audio`, () => {
				cy.createPost( {
					title: `I can add a ${ postType.name } with "Pending review" audio`,
					postType,
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
