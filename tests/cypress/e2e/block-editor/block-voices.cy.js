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

	// group > columns > column > [heading, paragraph, list > list-item, image]
	// puts an override, and an exclusion, deep in the tree. core/image has no
	// inner blocks — its caption is a rich-text attribute — so the list is what
	// nests below the column.
	const CONTENT =
		'<!-- wp:paragraph --><p>Top paragraph.</p><!-- /wp:paragraph -->' +
		'<!-- wp:group --><div class="wp-block-group">' +
		'<!-- wp:columns --><div class="wp-block-columns">' +
		'<!-- wp:column --><div class="wp-block-column">' +
		'<!-- wp:heading --><h2 class="wp-block-heading">Deep heading.</h2><!-- /wp:heading -->' +
		'<!-- wp:paragraph --><p>Deep paragraph.</p><!-- /wp:paragraph -->' +
		'<!-- wp:list --><ul class="wp-block-list">' +
		'<!-- wp:list-item --><li>Deep list item.</li><!-- /wp:list-item -->' +
		'</ul><!-- /wp:list -->' +
		'<!-- wp:image --><figure class="wp-block-image">' +
		'<img src="/wp-includes/images/w-logo-blue.png" alt="Deep alt."/>' +
		'<figcaption class="wp-element-caption">Deep image caption.</figcaption>' +
		'</figure><!-- /wp:image -->' +
		'</div><!-- /wp:column -->' +
		'</div><!-- /wp:columns -->' +
		'</div><!-- /wp:group -->';

	const blockPanel = () => cy.get( '.beyondwords--block-settings' );

	const blockSelect = ( label ) =>
		blockPanel()
			.contains( 'label', label )
			.closest( '.components-select-control' )
			.find( 'select' );

	const customize = () =>
		blockPanel().find( '.beyondwords--customize-block' );

	const generation = () =>
		blockPanel()
			.contains( 'label', /^Generation (enabled|disabled)$/ )
			.closest( '.beyondwords-toggle' );

	// The inspector re-renders as the selection settles, so clicking a node we
	// queried a moment ago can land on a detached element and do nothing. Wait
	// for the control, then assert the click actually took.
	const setToggle = ( toggle, checked ) => {
		toggle()
			.find( 'input[type="checkbox"]' )
			.should( 'have.prop', 'checked', ! checked );
		toggle().find( 'label' ).click( { force: true } );
		toggle()
			.find( 'input[type="checkbox"]' )
			.should( 'have.prop', 'checked', checked );
	};

	const flatten = ( list, out = [] ) => {
		list.forEach( ( block ) => {
			out.push( block );
			flatten( block.innerBlocks || [], out );
		} );
		return out;
	};

	const text = ( block ) =>
		`${ block.attributes?.content ?? '' }${
			block.attributes?.caption ?? ''
		}`;

	const byText = ( needle ) => ( block ) => text( block ).includes( needle );
	const byName = ( name ) => ( block ) => block.name === name;

	const blocks = () =>
		cy
			.window()
			.its( 'wp.data' )
			.then( ( data ) =>
				flatten( data.select( 'core/block-editor' ).getBlocks() )
			);

	// Selection is not what is under test, and clicking a block four levels
	// deep in the canvas is the flakiest way to reach it.
	//
	// `title` is the block's name in the inspector's block card: the store
	// updates before the inspector re-renders, so without waiting for the card
	// the next assertion reads the previously selected block's panel.
	const selectBlock = ( match, title ) => {
		cy.window()
			.its( 'wp.data' )
			.then( ( data ) => {
				const target = flatten(
					data.select( 'core/block-editor' ).getBlocks()
				).find( match );
				expect(
					Boolean( target ),
					'found a block matching the selector'
				).to.eq( true );
				data.dispatch( 'core/block-editor' ).selectBlock(
					target.clientId
				);
			} );

		// `contain`, not exact text — a heading's card reads "Heading 2".
		cy.get( '.block-editor-block-card__title' ).should( 'contain', title );
	};

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
			it( `overrides the language and voice per block for a ${ postType.name }`, () => {
				cy.createTestPost( {
					title: `Cypress Test: block voices for a ${ postType.name }`,
					postType: postType.slug,
					status: 'draft',
					content: CONTENT,
				} ).then( ( postId ) => {
					cy.visitPostEditorById( postId );
					cy.checkGenerateAudio( postType );

					/* --------------------------- a top-level paragraph */

					selectBlock( byText( 'Top paragraph.' ), 'Paragraph' );

					// "Customize" is opt-in and off by default, so a block
					// inherits the post language and voice until it is enabled.
					customize()
						.find( 'input[type="checkbox"]' )
						.should( 'not.be.checked' );
					blockPanel()
						.contains( 'label', 'Language' )
						.should( 'not.exist' );

					setToggle( customize, true );

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

					/* ------------------ a heading four levels down */

					selectBlock( byText( 'Deep heading.' ), 'Heading' );
					blockPanel().should( 'exist' );
					setToggle( customize, true );
					blockSelect( 'Language' ).select( 'English', {
						force: true,
					} );

					/* ----------------------------- the container */

					selectBlock( byName( 'core/group' ), 'Group' );
					blockPanel().should( 'exist' );
					setToggle( customize, true );
					blockSelect( 'Language' ).select( 'French', {
						force: true,
					} );

					/* -------------- generation off, three levels down */

					selectBlock( byText( 'Deep paragraph.' ), 'Paragraph' );
					setToggle( generation, false );

					// With generation off there is nothing to customise.
					customize().should( 'not.exist' );
					blockPanel()
						.contains( 'label', 'Language' )
						.should( 'not.exist' );

					/* ------------------------------ what got stored */

					blocks().then( ( all ) => {
						const find = ( match ) => all.find( match );

						const top = find( byText( 'Top paragraph.' ) );
						expect( top.attributes.beyondwordsLanguageCode ).to.eq(
							'en_GB'
						);
						expect( top.attributes.beyondwordsVoiceId ).to.eq(
							CALEB_VOICE_ID
						);

						// A block at depth stores its own pair...
						const heading = find( byText( 'Deep heading.' ) );
						expect(
							heading.attributes.beyondwordsLanguageCode
						).to.match( /^en_/ );
						// eslint-disable-next-line no-unused-expressions
						expect( heading.attributes.beyondwordsVoiceId ).to.not
							.be.empty;

						// ...and the container stores a language of its own,
						// which the blocks inside it inherit or override.
						const group = find( byName( 'core/group' ) );
						expect(
							group.attributes.beyondwordsLanguageCode
						).to.match( /^fr_/ );
						// eslint-disable-next-line no-unused-expressions
						expect( group.attributes.beyondwordsVoiceId ).to.not.be
							.empty;

						// Generation off is stored on the block at depth.
						expect(
							find( byText( 'Deep paragraph.' ) ).attributes
								.beyondwordsAudio
						).to.eq( false );

						// Untouched blocks stay bare.
						const item = find( byText( 'Deep list item.' ) );
						expect(
							item.attributes.beyondwordsLanguageCode || ''
						).to.eq( '' );
						expect(
							item.attributes.beyondwordsVoiceId || ''
						).to.eq( '' );

						// Nothing was invalidated by the added attributes.
						all.forEach( ( block ) => {
							expect(
								block.isValid,
								`${ block.name } is valid`
							).to.not.eq( false );
						} );
					} );

					cy.publishWithConfirmation();

					/* ------------------------ what we send to the API */

					cy.task( 'getPostMetaJson', {
						postId,
						metaKey: SENT_BODY_META,
					} ).should( ( body ) => {
						// The top-level override.
						expect( body ).to.match(
							new RegExp(
								`<p[^>]*data-beyondwords-voice-id="${ CALEB_VOICE_ID }"[^>]*>Top paragraph`
							)
						);

						// The container carries its own, and the heading inside
						// it overrides that — the API resolves the cascade.
						expect( body ).to.match(
							/<div[^>]*data-beyondwords-language="fr_[A-Z]{2}"/
						);
						expect( body ).to.match(
							/<h2[^>]*data-beyondwords-(language|voice-id)=/
						);

						// A block excluded three levels down is dropped, while
						// its siblings survive.
						expect( body ).to.not.contain( 'Deep paragraph.' );
						expect( body ).to.contain( 'Deep list item.' );
						expect( body ).to.contain( 'Deep image caption.' );

						// An untouched block emits no attributes of its own.
						expect( body ).to.match(
							/<li(?![^>]*data-beyondwords)[^>]*>Deep list item\./
						);
					} );

					/* ----------------------------------- the front end */

					cy.viewPostById( postId );
					cy.get( '[data-beyondwords-language]' ).should(
						'not.exist'
					);
					cy.get( '[data-beyondwords-voice-id]' ).should(
						'not.exist'
					);
					// Excluded from the audio, but still published as usual.
					cy.contains( 'Deep paragraph.' ).should( 'exist' );

					/* ------------------------------ back in the editor */

					cy.visitPostEditorById( postId );
					selectBlock( byText( 'Top paragraph.' ), 'Paragraph' );

					customize()
						.find( 'input[type="checkbox"]' )
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
					setToggle( customize, false );

					blocks().then( ( all ) => {
						const top = all.find( byText( 'Top paragraph.' ) );
						expect( top.attributes.beyondwordsLanguageCode ).to.eq(
							''
						);
						expect( top.attributes.beyondwordsVoiceId ).to.eq( '' );
					} );
				} );
			} );
		} );
} );
