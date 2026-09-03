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

	// The store updates before the inspector re-renders, so without waiting for
	// the block card the next assertion reads the previous block's panel.
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

					customize()
						.find( 'input[type="checkbox"]' )
						.should( 'not.be.checked' );
					blockPanel()
						.contains( 'label', 'Language' )
						.should( 'not.exist' );

					setToggle( customize, true );

					// Seeded from what the block already inherits — the mock
					// project is en_US with body voice "Ava (Multilingual)" —
					// so changing voice alone costs no language clicks.
					blockSelect( 'Language' )
						.find( 'option:selected' )
						.should( 'have.text', 'English' );
					blockSelect( 'Accent' )
						.find( 'option:selected' )
						.should( 'have.text', 'American' );
					blockSelect( 'Voice' )
						.find( 'option:selected' )
						.should( 'have.text', 'Ava (Multilingual)' );

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

						const heading = find( byText( 'Deep heading.' ) );
						expect(
							heading.attributes.beyondwordsLanguageCode
						).to.match( /^en_/ );
						// eslint-disable-next-line no-unused-expressions
						expect( heading.attributes.beyondwordsVoiceId ).to.not
							.be.empty;

						// The container stores its own language; the blocks
						// inside it inherit or override it.
						const group = find( byName( 'core/group' ) );
						expect(
							group.attributes.beyondwordsLanguageCode
						).to.match( /^fr_/ );
						// eslint-disable-next-line no-unused-expressions
						expect( group.attributes.beyondwordsVoiceId ).to.not.be
							.empty;

						expect(
							find( byText( 'Deep paragraph.' ) ).attributes
								.beyondwordsAudio
						).to.eq( false );

						const item = find( byText( 'Deep list item.' ) );
						expect(
							item.attributes.beyondwordsLanguageCode || ''
						).to.eq( '' );
						expect(
							item.attributes.beyondwordsVoiceId || ''
						).to.eq( '' );

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
						expect( body ).to.match(
							new RegExp(
								`<p[^>]*data-beyondwords-voice-id="${ CALEB_VOICE_ID }"[^>]*>Top paragraph`
							)
						);

						// Only the container and the heading carry attributes;
						// the API resolves the cascade between them.
						expect( body ).to.match(
							/<div[^>]*data-beyondwords-language="fr_[A-Z]{2}"/
						);
						expect( body ).to.match(
							/<h2[^>]*data-beyondwords-(language|voice-id)=/
						);

						expect( body ).to.not.contain( 'Deep paragraph.' );
						expect( body ).to.contain( 'Deep list item.' );
						expect( body ).to.contain( 'Deep image caption.' );

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

	it( "seeds a block from the post's own voice, not the project default", () => {
		cy.createTestPost( {
			title: 'Cypress Test: block voices seed from the post',
			postType: 'post',
			status: 'draft',
			content: CONTENT,
		} ).then( ( postId ) => {
			// en_GB with "Ollie (Multilingual)", where the project is en_US.
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_language_code',
				metaValue: 'en_GB',
			} );
			cy.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_body_voice_id',
				metaValue: '3558',
			} );

			cy.visitPostEditorById( postId );
			selectBlock( byText( 'Top paragraph.' ), 'Paragraph' );
			setToggle( customize, true );

			blockSelect( 'Language' )
				.find( 'option:selected' )
				.should( 'have.text', 'English' );
			blockSelect( 'Accent' )
				.find( 'option:selected' )
				.should( 'have.text', 'British' );
			blockSelect( 'Voice' )
				.find( 'option:selected' )
				.should( 'have.text', 'Ollie (Multilingual)' );

			blocks().then( ( all ) => {
				const top = all.find( byText( 'Top paragraph.' ) );
				expect( top.attributes.beyondwordsLanguageCode ).to.eq(
					'en_GB'
				);
				expect( top.attributes.beyondwordsVoiceId ).to.eq( '3558' );
			} );
		} );
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `re-seeds a block when Customize is switched off and on for a ${ postType.name }`, () => {
				cy.createTestPost( {
					title: `Cypress Test: block voices re-seed for a ${ postType.name }`,
					postType: postType.slug,
					status: 'draft',
					content: CONTENT,
				} ).then( ( postId ) => {
					cy.visitPostEditorById( postId );

					const expectSeeded = () => {
						blockSelect( 'Language' )
							.find( 'option:selected' )
							.should( 'have.text', 'English' );
						blockSelect( 'Accent' )
							.find( 'option:selected' )
							.should( 'have.text', 'American' );
						blockSelect( 'Voice' )
							.find( 'option:selected' )
							.should( 'have.text', 'Ava (Multilingual)' );
					};

					selectBlock( byText( 'Top paragraph.' ), 'Paragraph' );

					setToggle( customize, true );
					expectSeeded();

					setToggle( customize, false );
					blockPanel()
						.contains( 'label', 'Language' )
						.should( 'not.exist' );

					// The picker stays mounted while Customize is off, so the
					// second time round has to seed from scratch again.
					setToggle( customize, true );
					expectSeeded();

					blocks().then( ( all ) => {
						const top = all.find( byText( 'Top paragraph.' ) );
						expect( top.attributes.beyondwordsLanguageCode ).to.eq(
							'en_US'
						);
						// eslint-disable-next-line no-unused-expressions
						expect( top.attributes.beyondwordsVoiceId ).to.not.be
							.empty;
					} );
				} );
			} );
		} );
} );
