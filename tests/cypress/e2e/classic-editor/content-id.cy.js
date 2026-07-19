/**
 * @group classic-editor
 * @covers src/editor/components/content-id/,src/editor/classic/class-metabox.php
 */

/* global Cypress, cy, before, beforeEach, after, context, it */

context( 'Classic Editor: Content ID', () => {
	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	it( 'displays the Content ID field in the metabox', () => {
		cy.createTestPost( {
			title: 'Cypress Test: classic content id field visible',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );

			cy.get( '#beyondwords-metabox-content-id' ).should( 'exist' );
			cy.get( '#beyondwords_content_id' ).should( 'exist' );
			cy.get( '#beyondwords__content-id--fetch' ).should( 'exist' );
		} );
	} );

	it( 'shows existing content ID value for posts with audio', () => {
		cy.createTestPostWithAudio( {
			title: 'Cypress Test: classic content id shows value',
			postStatus: 'publish',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );

			cy.get( '#beyondwords_content_id' ).should(
				'have.value',
				Cypress.expose('contentId')
			);
		} );
	} );

	it( 'successfully fetches content and updates post meta', () => {
		const testContentId = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

		cy.createTestPost( {
			title: 'Cypress Test: classic content id fetch success',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );

			cy.intercept( 'POST', '**/wp/v2/posts/**' ).as( 'savePostMeta' );

			cy.get( '#beyondwords_content_id' )
				.clear()
				.type( testContentId );

			cy.get( '#beyondwords__content-id--fetch' ).click();

			cy.wait( '@savePostMeta', { timeout: 30000 } );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_content_id',
			} ).should( 'equal', testContentId );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_language_code',
			} ).should( 'equal', 'en_US' );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_preview_token',
			} ).should( 'equal', 'd9ce36ea-ddc4-4611-b60c-4f90ed0fc082' );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_body_voice_id',
			} ).should( 'equal', '2517' );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_generate_audio',
			} ).should( 'equal', '0' );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_error_message',
			} ).should( 'equal', '' );
		} );
	} );

	it( 'handles fetch error and sets error message', () => {
		cy.createTestPost( {
			title: 'Cypress Test: classic content id fetch error',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );

			cy.intercept(
				'GET',
				'**/beyondwords/v1/projects/*/content/not-found-content-id',
				{
					statusCode: 404,
					body: { code: 404, message: 'Not Found' },
				}
			).as( 'fetchContent' );

			cy.intercept( 'POST', '**/wp/v2/posts/**' ).as(
				'saveErrorMeta'
			);

			cy.get( '#beyondwords_content_id' )
				.clear()
				.type( 'not-found-content-id' );

			cy.get( '#beyondwords__content-id--fetch' ).click();

			cy.wait( '@fetchContent' );
			cy.wait( '@saveErrorMeta', { timeout: 30000 } );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_error_message',
			} ).should( 'not.be.empty' );

			cy.task( 'getPostMeta', {
				postId,
				metaKey: 'beyondwords_content_id',
			} ).should( 'equal', 'not-found-content-id' );
		} );
	} );
} );
