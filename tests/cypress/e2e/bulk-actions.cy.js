/**
 * @group posts-list
 * @covers src/posts-list/class-bulk-edit.php,src/posts-list/class-notices.php,src/posts-list/class-column.php,src/api/class-client.php
 */

/* global cy, beforeEach, context, it */

context( 'Bulk Actions', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../tests/fixtures/post-types.json' );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `does bulk actions for for ${ postType.name }s`, () => {
				cy.publishPostWithoutAudio( {
					postType,
					title: `Bulk actions for ${ postType.name }s (1)`,
				} );
				cy.publishPostWithoutAudio( {
					postType,
					title: `Bulk actions for ${ postType.name }s (2)`,
				} );
				cy.publishPostWithoutAudio( {
					postType,
					title: `Bulk actions for ${ postType.name }s (3)`,
				} );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&orderby=date&order=desc`
				);

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' );
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-error' ).contains(
					'None of the selected posts had valid BeyondWords audio data.'
				);

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select(
					'Generate audio'
				);
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was requested for 3 posts.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						// todo save URL and visit it to check player exists
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						// todo save URL and visit it to check player exists
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						// todo save URL and visit it to check player exists
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' );
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was deleted for 3 posts.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select(
					'Generate audio'
				);
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was requested for 1 post.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select(
					'Generate audio'
				);
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was requested for 3 posts.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' );
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was deleted for 1 post.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );

				cy.get( 'tbody tr' )
					.eq( 0 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 1 )
					.within( () => {
						cy.get( 'td.beyondwords.column-beyondwords' ).contains(
							'—'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( 'tbody tr' )
					.eq( 2 )
					.within( () => {
						cy.get(
							'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes'
						);
						cy.get(
							'input[type="checkbox"][name="post[]"]'
						).check();
					} );

				cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' );
				cy.get( '#doaction' ).click();

				cy.get( 'div.notice.notice-info' ).contains(
					'Audio was deleted for 2 posts.'
				);
				cy.get( 'div.notice.notice-error' ).should( 'not.be.visible' );
			} );

			it( `reports skipped, not failed, for draft ${ postType.name }s`, () => {
				// A draft is a post status BeyondWords does not process, so the
				// bulk action has nothing to generate for it.
				const title = `Bulk skip for ${ postType.name }s`;

				cy.createTestPost( {
					title,
					postType: postType.slug,
					status: 'draft',
				} );

				cy.visit(
					`/wp-admin/edit.php?post_type=${ postType.slug }&post_status=draft`
				);

				cy.contains( 'tbody tr', title ).within( () => {
					cy.get( 'input[type="checkbox"][name="post[]"]' ).check();
				} );

				cy.get( '#bulk-action-selector-top' ).select(
					'Generate audio'
				);
				cy.get( '#doaction' ).click();

				cy.get( '#beyondwords-bulk-edit-notice-skipped' ).contains(
					'1 post was skipped because no audio change was needed.'
				);
				cy.get( '#beyondwords-bulk-edit-notice-failed' ).should(
					'not.exist'
				);
			} );
		} );
} );
