/* global cy, beforeEach, context, it */

context( 'Block Editor: Block Inserter', () => {
	beforeEach( () => {
		cy.login();
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	postTypes
		.filter( ( x ) => x.supported )
		.forEach( ( postType ) => {
			it( `block inserter button appears correctly for ${ postType.name }`, () => {
				cy.visitPostEditor( postType.slug );

				// Add a title to make the editor active
				cy.get( '.editor-post-title__input' ).type(
					'Test block inserter'
				);

				// Click after the title to focus the editor body
				cy.get( '.editor-post-title__input' ).type( '{enter}' );

				// Wait for the editor to be ready
				cy.wait( 500 );

				// The block inserter button ([+]) should be visible
				// This is the button that appears when you're in an empty block
				cy.get( 'button[aria-label="Add block"]' ).should(
					'be.visible'
				);

				// Click the inserter button to open the block picker
				cy.get( 'button[aria-label="Add block"]' ).first().click();

				// The block picker popover should appear
				cy.get( '.block-editor-inserter__menu' ).should(
					'be.visible'
				);

				// Should show "Browse all" option
				cy.contains( 'Browse all' ).should( 'be.visible' );

				// Close the inserter
				cy.get( 'body' ).type( '{esc}' );
			} );

			it( `can insert multiple blocks sequentially for ${ postType.name }`, () => {
				cy.visitPostEditor( postType.slug );

				// Add a title
				cy.get( '.editor-post-title__input' ).type(
					'Test multiple blocks'
				);
				cy.get( '.editor-post-title__input' ).type( '{enter}' );

				// Wait for the editor to be ready
				cy.wait( 500 );

				// Type some text in the first block
				cy.get( '.block-editor-block-list__layout' )
					.first()
					.type( 'First paragraph' );

				// Press enter to create a new block
				cy.get( '.block-editor-block-list__layout' )
					.first()
					.type( '{enter}' );

				// The inserter button should still appear for the new block
				cy.get( 'button[aria-label="Add block"]' ).should(
					'be.visible'
				);

				// Type in the second block
				cy.get( '.block-editor-block-list__layout' )
					.first()
					.type( 'Second paragraph{enter}' );

				// Type in the third block
				cy.get( '.block-editor-block-list__layout' )
					.first()
					.type( 'Third paragraph' );

				// Verify we have 3 paragraph blocks
				cy.get( '.wp-block-paragraph' ).should( 'have.length', 3 );

				// Verify the content
				cy.contains( '.wp-block-paragraph', 'First paragraph' );
				cy.contains( '.wp-block-paragraph', 'Second paragraph' );
				cy.contains( '.wp-block-paragraph', 'Third paragraph' );
			} );

			it( `duplicated blocks get unique markers for ${ postType.name }`, () => {
				cy.visitPostEditor( postType.slug );

				// Add a title
				cy.get( '.editor-post-title__input' ).type(
					'Test duplicate markers'
				);
				cy.get( '.editor-post-title__input' ).type( '{enter}' );

				// Wait for the editor to be ready
				cy.wait( 500 );

				// Type some text in the first block
				cy.get( '.block-editor-block-list__layout' )
					.first()
					.type( 'Original paragraph' );

				// Wait for the block to be created
				cy.wait( 500 );

				// Select the block by clicking on it
				cy.contains( '.wp-block-paragraph', 'Original paragraph' ).click();

				// Open the block options menu (three dots)
				cy.get( '.block-editor-block-toolbar' )
					.find( 'button[aria-label="Options"]' )
					.click();

				// Click "Duplicate" in the dropdown menu
				cy.contains( 'button', 'Duplicate' ).click();

				// Wait for duplication to complete
				cy.wait( 500 );

				// Verify we have 2 paragraph blocks with the same content
				cy.get( '.wp-block-paragraph' ).should( 'have.length', 2 );

				// Get the beyondwordsMarker attributes for both blocks
				cy.window().then( ( win ) => {
					const blocks = win.wp.data
						.select( 'core/block-editor' )
						.getBlocks();

					// Find the two paragraph blocks
					const paragraphBlocks = blocks.filter(
						( block ) => block.name === 'core/paragraph'
					);

					expect( paragraphBlocks ).to.have.length( 2 );

					// Extract markers
					const marker1 =
						paragraphBlocks[ 0 ].attributes.beyondwordsMarker;
					const marker2 =
						paragraphBlocks[ 1 ].attributes.beyondwordsMarker;

					// Both should have markers
					expect( marker1 ).to.be.a( 'string' );
					expect( marker2 ).to.be.a( 'string' );
					expect( marker1 ).to.have.length.greaterThan( 0 );
					expect( marker2 ).to.have.length.greaterThan( 0 );

					// Markers should be different (not duplicates)
					expect( marker1 ).to.not.equal( marker2 );
				} );
			} );
		} );
} );
