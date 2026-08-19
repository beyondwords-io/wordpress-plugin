/**
 * @group block-editor
 * @covers src/editor/components/block-attributes/
 * @covers src/editor/components/voice-picker/
 * @covers src/post/class-content.php
 */

/* global cy, after, before, beforeEach, context, expect, it */

context( 'Block Editor: Block Voices', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	// The beyondwords-filter-content-params fixture plugin stores the body we
	// send to the API in this post meta key.
	const SENT_BODY_META = 'BEFORE:beyondwords_content';

	// Caleb, an `eleven_v3` voice in the mock API's English voices.
	const CALEB_VOICE_ID = '9010';

	const CONTENT =
		'<!-- wp:paragraph --><p>Bonjour tout le monde.</p><!-- /wp:paragraph -->' +
		'<!-- wp:paragraph --><p>Hello world.</p><!-- /wp:paragraph -->';

	const blockPanel = () => cy.get( '.beyondwords--block-settings' );

	const blockSelect = ( label ) =>
		blockPanel()
			.contains( 'label', label )
			.closest( '.components-select-control' )
			.find( 'select' );

	const selectFirstParagraph = () =>
		cy
			.getEditorCanvasBody()
			.find( 'p' )
			.contains( 'Bonjour tout le monde.' )
			.click();

	before( () => {
		cy.task( 'activatePlugin', 'beyondwords-filter-content-params' );
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'beyondwords-filter-content-params' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `sets a per-block language and voice for a ${ postType.name }`, () => {
				cy.createTestPost( {
					title: `Cypress Test: block voices for a ${ postType.name }`,
					postType: postType.slug,
					status: 'draft',
					content: CONTENT,
				} ).then( ( postId ) => {
					cy.visitPostEditorById( postId );

					cy.checkGenerateAudio( postType );

					selectFirstParagraph();

					// "Customize" is opt-in and off by default, so a block
					// inherits the post language and voice until it is enabled.
					blockPanel()
						.find(
							'.beyondwords--customize-block input[type="checkbox"]'
						)
						.should( 'not.be.checked' );
					blockPanel()
						.contains( 'label', 'Language' )
						.should( 'not.exist' );

					blockPanel()
						.find( '.beyondwords--customize-block label' )
						.click( { force: true } );

					// Unlike the post sidebar, a block seeds no default — it
					// keeps inheriting until a language is picked.
					blockSelect( 'Language' )
						.find( 'option:selected' )
						.should( 'have.text', 'Select a language…' );
					blockPanel()
						.contains( 'label', 'Accent' )
						.should( 'not.exist' );

					blockSelect( 'Language' ).select( 'English', {
						force: true,
					} );
					blockSelect( 'Accent' ).select( 'British', {
						force: true,
					} );

					// The mock's English voices are all American-primary, so
					// none are native to en_GB and Native must be "All".
					blockSelect( 'Native' ).select( 'All', { force: true } );
					blockSelect( 'Model' ).select( 'v3', { force: true } );
					blockSelect( 'Voice' ).select( 'Caleb', { force: true } );

					// The pair is stored on that block, and on no other.
					cy.window()
						.its( 'wp.data' )
						.then( ( data ) => {
							cy.wrap( null, { timeout: 10000 } ).should( () => {
								const blocks = data
									.select( 'core/block-editor' )
									.getBlocks();

								expect(
									blocks[ 0 ].attributes
										.beyondwordsLanguageCode
								).to.eq( 'en_GB' );
								expect(
									blocks[ 0 ].attributes.beyondwordsVoiceId
								).to.eq( CALEB_VOICE_ID );
								expect(
									blocks[ 1 ].attributes
										.beyondwordsLanguageCode
								).to.eq( '' );
								expect(
									blocks[ 1 ].attributes.beyondwordsVoiceId
								).to.eq( '' );
							} );
						} );

					cy.publishWithConfirmation();

					// The body we send carries the override on the chosen block,
					// and leaves the other block's HTML alone.
					cy.task( 'getPostMetaJson', {
						postId,
						metaKey: SENT_BODY_META,
					} ).should( ( body ) => {
						const [ overridden, inherited ] = body
							.split( '\n' )
							.filter( Boolean );

						expect( overridden ).to.contain(
							'data-beyondwords-language="en_GB"'
						);
						expect( overridden ).to.contain(
							`data-beyondwords-voice-id="${ CALEB_VOICE_ID }"`
						);
						expect( overridden ).to.contain(
							'Bonjour tout le monde.'
						);

						// The untouched block keeps the markup it always had.
						expect( inherited ).to.contain( 'Hello world.' );
						expect( inherited ).to.not.contain( 'data-beyondwords' );
					} );

					// The data attributes belong to the API body only.
					cy.viewPostById( postId );
					cy.get( '[data-beyondwords-language]' ).should(
						'not.exist'
					);
					cy.get( '[data-beyondwords-voice-id]' ).should(
						'not.exist'
					);

					// The selections survive a save and a fresh editor.
					cy.visitPostEditorById( postId );
					selectFirstParagraph();

					blockPanel()
						.find(
							'.beyondwords--customize-block input[type="checkbox"]'
						)
						.should( 'be.checked' );
					blockSelect( 'Language' )
						.find( 'option:selected' )
						.should( 'have.text', 'English' );
					blockSelect( 'Accent' )
						.find( 'option:selected' )
						.should( 'have.text', 'British' );
					blockSelect( 'Voice' )
						.find( 'option:selected' )
						.should( 'have.text', 'Caleb' );

					// Turning Customize off returns the block to the post-level
					// language and voice.
					blockPanel()
						.find( '.beyondwords--customize-block label' )
						.click( { force: true } );

					cy.window()
						.its( 'wp.data' )
						.then( ( data ) => {
							cy.wrap( null, { timeout: 10000 } ).should( () => {
								const block = data
									.select( 'core/block-editor' )
									.getBlocks()[ 0 ];

								expect(
									block.attributes.beyondwordsLanguageCode
								).to.eq( '' );
								expect(
									block.attributes.beyondwordsVoiceId
								).to.eq( '' );
							} );
						} );
				} );
			} );
		} );
} );
