/* global Cypress, cy, beforeEach, context, it */

context( 'Block Editor: Add Post', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	const postStatuses = [ 'draft', 'future', 'publish', 'pending', 'private' ];

	postTypes
		.filter( ( x ) => x.supported )
		.forEach( ( postType ) => {
			postStatuses.forEach( ( postStatus ) => {
				it( `can add a ${ postStatus } ${ postType.name } without audio`, async () => {
					cy.createPost( {
						title: `can add a ${ postStatus } ${ postType.name } without audio`,
						status: postStatus,
						postType,
					} );

					cy.openBeyondwordsEditorPanel();

					cy.uncheckGenerateAudio( postType );

					cy.publishWithConfirmation();

					// "View post"
					cy.viewPostViaSnackbar();

					cy.getPlayerScriptTag().should( 'not.exist' );
					cy.hasPlayerInstances( 0 );

					// Edit post via admin bar
					cy.get( '#wp-admin-bar-edit a' ).click();

					cy.openBeyondwordsEditorPanel();

					cy.getBlockEditorCheckbox( 'Generate audio' ).should(
						'not.be.checked'
					);
				} );

				it( `can update a ${ postStatus } ${ postType.name } without audio`, async () => {
					const postId = await cy.createTestPost( {
						title: `can update a ${ postStatus } ${ postType.name } without audio`,
						status: postStatus,
						postType: postType.name,
					} );

					cy.visit(
						`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
					);

					// See a [-] in the BeyondWords column
					cy.get( 'tbody tr' )
						.contains(
							`can add a ${ postStatus } ${ postType.name } without audio`
						)
						.parent( 'tr' )
						.within( () => {
							cy.contains(
								'td.beyondwords.column-beyondwords',
								'—'
							);
						} );

					cy.visit(
						`/wp-admin/post.php?post=${ postId }&action=edit`
					);

					cy.openBeyondwordsEditorPanel();

					cy.uncheckGenerateAudio( postType );

					cy.savePost();

					cy.visit(
						`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
					);

					// Still see a [-] in the BeyondWords column
					cy.get( 'tbody tr' )
						.contains(
							`can add a ${ postStatus } ${ postType.name } without audio`
						)
						.parent( 'tr' )
						.within( () => {
							cy.contains(
								'td.beyondwords.column-beyondwords',
								'—'
							);
							cy.get( 'a.row-title' ).click();
						} );

					cy.hasPlayerInstances( 0 );
				} );

				it( `can add a ${ postStatus } ${ postType.name } with audio`, () => {
					cy.createPost( {
						title: `I can add a ${ postStatus } ${ postType.name } with audio`,
						status: postStatus,
						postType,
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
				it( `can update a ${ postStatus } ${ postType.name } with audio`, async () => {
					const postId = await cy.createTestPost( {
						title: `can update a ${ postStatus } ${ postType.name } with audio`,
						status: postStatus,
						postType: postType.name,
					} );

					cy.visit(
						`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
					);

					// See a [-] in the BeyondWords column
					cy.get( 'tbody tr' )
						.contains(
							`can add a ${ postStatus } ${ postType.name } without audio`
						)
						.parent( 'tr' )
						.within( () => {
							cy.contains(
								'td.beyondwords.column-beyondwords',
								'—'
							);
						} );

					cy.visit(
						`/wp-admin/post.php?post=${ postId }&action=edit`
					);

					cy.openBeyondwordsEditorPanel();

					cy.uncheckGenerateAudio( postType );

					cy.savePost();

					cy.visit(
						`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
					);

					// Still see a [-] in the BeyondWords column
					cy.get( 'tbody tr' )
						.contains(
							`can add a ${ postStatus } ${ postType.name } without audio`
						)
						.parent( 'tr' )
						.within( () => {
							cy.contains(
								'td.beyondwords.column-beyondwords',
								'—'
							);
							cy.get( 'a.row-title' ).click();
						} );

					cy.hasPlayerInstances( 0 );
				} );
			} );

			// @todo Skip flaky test until mock API is replaced with http intercepts.
			it.skip( `can add a ${ postType.name } with "Pending review" audio`, () => {
				cy.createPost( {
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
