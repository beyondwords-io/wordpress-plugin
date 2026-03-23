/* global Cypress, cy, beforeEach, context, it, expect */

context( 'Block Editor: Content ID', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'displays the Content ID field in the sidebar panel', () => {
		cy.createTestPost( {
			title: 'Cypress Test: content id field visible',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.getLabel( 'Content ID' ).should( 'exist' );
		} );
	} );

	it( 'displays the Content ID field for a new post', () => {
		cy.createPost( {
			title: 'Cypress Test: content id new post',
			postType: { slug: 'post' },
		} );

		cy.openBeyondwordsPluginSidebar();

		cy.getLabel( 'Content ID' ).should( 'exist' );
	} );

	it( 'shows existing content ID value for posts with audio', () => {
		cy.createTestPostWithAudio( {
			title: 'Cypress Test: content id shows value',
			postStatus: 'publish',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.get( '.beyondwords-sidebar__status input[type="text"]' )
				.filter(
					( _i, el ) => el.value === Cypress.env( 'contentId' )
				)
				.should( 'exist' );
		} );
	} );

	it( 'successfully fetches content and updates post meta', () => {
		const testContentId = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

		cy.createTestPost( {
			title: 'Cypress Test: content id fetch success',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			// Explicitly set generate_audio so the preselect useEffect
			// does not race with the Fetch editPost call.
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_generate_audio',
				metaValue: '1',
			} );

			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			// Type a content ID into the field
			cy.getLabel( 'Content ID' )
				.parent()
				.find( 'input' )
				.clear( { force: true } )
				.type( testContentId, { force: true } );

			// Click Fetch
			cy.get( '.beyondwords-sidebar__status' )
				.contains( 'button', 'Fetch' )
				.click( { force: true } );

			// Wait for the fetch to complete and verify meta fields.
			// Note: beyondwords_generate_audio is verified after reload
			// because the GenerateAudio preselect useEffect can race
			// with the Fetch editPost() call in-session.
			cy.window()
				.its( 'wp.data' )
				.should( ( data ) => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					expect( meta.beyondwords_content_id ).to.equal(
						testContentId
					);
					expect( meta.beyondwords_language_code ).to.equal(
						'en_US'
					);
					expect( meta.beyondwords_preview_token ).to.equal(
						'd9ce36ea-ddc4-4611-b60c-4f90ed0fc082'
					);
					expect( meta.beyondwords_title_voice_id ).to.equal(
						'2517'
					);
					expect( meta.beyondwords_body_voice_id ).to.equal(
						'2517'
					);
					expect( meta.beyondwords_error_message ).to.equal( '' );
				} );

			// Verify persists after reload — including generate_audio,
			// which is only reliably testable after a fresh page load.
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.window()
				.its( 'wp.data' )
				.then( ( data ) => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					expect( meta.beyondwords_content_id ).to.equal(
						testContentId
					);
					expect( meta.beyondwords_generate_audio ).to.equal( '0' );
					expect( meta.beyondwords_preview_token ).to.equal(
						'd9ce36ea-ddc4-4611-b60c-4f90ed0fc082'
					);
				} );
		} );
	} );

	it( 'handles fetch error and sets error message', () => {
		cy.createTestPost( {
			title: 'Cypress Test: content id fetch error',
			status: 'draft',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			// Intercept the fetch request at browser level and return 404
			cy.intercept(
				'GET',
				'**/beyondwords/v1/projects/*/content/not-found-content-id',
				{
					statusCode: 404,
					body: { code: 404, message: 'Not Found' },
				}
			).as( 'fetchContent' );

			// Type the "not found" content ID
			cy.getLabel( 'Content ID' )
				.parent()
				.find( 'input' )
				.clear( { force: true } )
				.type( 'not-found-content-id', { force: true } );

			// Click Fetch
			cy.get( '.beyondwords-sidebar__status' )
				.contains( 'button', 'Fetch' )
				.click( { force: true } );

			cy.wait( '@fetchContent' );

			// Verify error message is set in editor state
			cy.window()
				.its( 'wp.data' )
				.should( ( data ) => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					expect( meta.beyondwords_error_message ).to.not.be.empty;
					expect( meta.beyondwords_content_id ).to.equal(
						'not-found-content-id'
					);
				} );

			// Verify error persists after reload
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			cy.window()
				.its( 'wp.data' )
				.then( ( data ) => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					expect( meta.beyondwords_error_message ).to.not.be.empty;
				} );
		} );
	} );

	it( 'fetched content is reflected on the frontend', () => {
		const testContentId = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

		cy.createTestPost( {
			title: 'Cypress Test: content id frontend',
			status: 'publish',
			postType: 'post',
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.openBeyondwordsPluginSidebar();

			// Type a content ID and fetch
			cy.getLabel( 'Content ID' )
				.parent()
				.find( 'input' )
				.clear( { force: true } )
				.type( testContentId, { force: true } );

			cy.get( '.beyondwords-sidebar__status' )
				.contains( 'button', 'Fetch' )
				.click( { force: true } );

			// Wait for the fetch to complete by polling editor meta state
			cy.window()
				.its( 'wp.data' )
				.should( ( data ) => {
					const meta = data
						.select( 'core/editor' )
						.getEditedPostAttribute( 'meta' );
					expect( meta.beyondwords_content_id ).to.equal(
						testContentId
					);
				} );

			// View the post on the frontend
			cy.viewPostById( postId );

			// The player script tag should include the fetched content ID
			cy.getPlayerScriptTag()
				.should( 'have.attr', 'src' )
				.and( 'include', testContentId );
		} );
	} );
} );
