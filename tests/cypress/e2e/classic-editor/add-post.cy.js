/* global Cypress, cy, before, beforeEach, after, context, it */

context( 'Classic Editor: Add Post', () => {
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

	postTypes
		.filter( ( x ) => x.supported )
		.forEach( ( postType ) => {
			it( `can add a new ${ postType.name } without audio`, () => {
				cy.createPost( {
					postType,
				} );

				// BeyondWords metabox is shown for supported post types
				cy.get( 'div#beyondwords.postbox' )
					.find( 'div.postbox-header' )
					.contains( 'BeyondWords' )
					.should( 'exist' );

				if ( postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'be.checked'
					);
					cy.get( 'input#beyondwords_generate_audio' ).uncheck();
				} else {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'not.be.checked'
					);
				}

				cy.classicSetPostTitle( `Test ${ postType.name }` );

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// "Generate Audio" is still on page
				cy.get( 'input#beyondwords_generate_audio' );
				cy.get( 'input#beyondwords_display_player' ).should(
					'not.exist'
				);

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 0 );

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

			it( `can update a ${ postType.name } without adding audio`, () => {
				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [—] in the BeyondWords column
				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.contains( 'td.beyondwords.column-beyondwords', '—' );
						cy.get( 'a.row-title' ).click();
					} );

				cy.hasPlayerInstances( 0 );

				cy.get( 'input#beyondwords_generate_audio' ).should(
					'not.be.checked'
				);

				cy.get( '#publish' ).click(); // Click "Update" Button

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 0 );

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

			it( `can add audio to an existing ${ postType.name }`, () => {
				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				// See a [—] in the BeyondWords column
				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.contains( 'td.beyondwords.column-beyondwords', '—' );
						cy.get( 'a.row-title' ).click();
					} );

				cy.hasPlayerInstances( 0 );

				cy.get( 'input#beyondwords_generate_audio' ).check();

				cy.get( '#publish' ).click(); // Click "Update" Button

				// Wait for success message
				cy.get( '#message.notice-success' );

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 1 );
			} );

			it( `can add a new ${ postType.name } with audio`, () => {
				cy.createPost( {
					postType,
				} );

				if ( postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'be.checked'
					);
				} else {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'not.be.checked'
					);
					cy.get( 'input#beyondwords_generate_audio' ).check();
				}

				cy.classicSetPostTitle(
					`I can add a ${ postType.name } with audio`
				);

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// "Generate Audio" is replaced by JS Player
				cy.get( 'input#beyondwords_generate_audio' ).should(
					'not.exist'
				);
				cy.get( 'input#beyondwords_display_player' ).should(
					'be.checked'
				);

				cy.get( '#sample-permalink' ).click();

				cy.hasPlayerInstances( 1 );

				// See a [tick] in the BeyondWords column' )
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

			it( `can add a ${ postType.name } with "Pending review" audio `, () => {
				cy.createPost( {
					postType,
				} );

				if ( postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'be.checked'
					);
				} else {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'not.be.checked'
					);
					cy.get( 'input#beyondwords_generate_audio' ).check();
				}

				cy.classicSetPostTitle(
					`I can add a ${ postType.name } with "Pending review" audio`
				);

				// Show Status select box
				cy.get( '.misc-pub-post-status a.edit-post-status' ).click();

				// Select "Pending Review"
				cy.get( '#post_status', { timeout: 20000 } ).select(
					'Pending Review'
				);

				// Click "OK"
				cy.get( 'a.save-post-status', { timeout: 20000 } ).click();

				// Click "Save as Pending" button
				cy.get( 'input[value="Save as Pending"]' ).click();

				// Wait for success message
				cy.get( 'div#message.notice-success' );

				// "Generate Audio" should be replaced by custom "Pending" message
				cy.get( 'input#beyondwords_generate_audio' ).should(
					'not.exist'
				);
				cy.contains(
					'#beyondwords-pending-review-message',
					'Listen to content saved as “Pending” in the BeyondWords dashboard.'
				);

				// "Pending review" message contains link to project
				cy.get( 'div#beyondwords-pending-review-message a' )
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
						cy.get( 'a.row-title' ).click();
					} );

				cy.hasPlayerInstances( 0 );
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
