/**
 * @group block-editor
 * @covers src/editor/components/block-attributes/
 * @covers src/post/class-content.php
 */

/* global cy, after, before, beforeEach, context, expect, it */

context( 'Block Editor: Block Audio', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	// The beyondwords-filter-content-params fixture plugin stores the body we
	// send to the API in this post meta key.
	const SENT_BODY_META = 'BEFORE:beyondwords_content';

	// A core/audio block carries its own recording, so it is always marked —
	// the paragraph alongside it proves nothing else is.
	const CONTENT =
		'<!-- wp:paragraph --><p>Spoken paragraph.</p><!-- /wp:paragraph -->' +
		'<!-- wp:audio --><figure class="wp-block-audio">' +
		'<audio controls src="/wp-content/uploads/cat.mp3"></audio>' +
		'<figcaption class="wp-element-caption">Cat audio caption.</figcaption>' +
		'</figure><!-- /wp:audio -->';

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

	const flatten = ( list, out = [] ) => {
		list.forEach( ( block ) => {
			out.push( block );
			flatten( block.innerBlocks || [], out );
		} );
		return out;
	};

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

	const selectAudioBlock = () => {
		cy.window()
			.its( 'wp.data' )
			.then( ( data ) => {
				const target = flatten(
					data.select( 'core/block-editor' ).getBlocks()
				).find( ( block ) => block.name === 'core/audio' );
				expect( Boolean( target ), 'found the audio block' ).to.eq(
					true
				);
				data.dispatch( 'core/block-editor' ).selectBlock(
					target.clientId
				);
			} );

		cy.get( '.block-editor-block-card__title' ).should(
			'contain',
			'Audio'
		);
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
			it( `marks an audio block as pre-recorded audio for a ${ postType.name }`, () => {
				cy.createTestPost( {
					title: `Cypress Test: block audio for a ${ postType.name }`,
					postType: postType.slug,
					status: 'draft',
					content: CONTENT,
				} ).then( ( postId ) => {
					cy.visitPostEditorById( postId );
					cy.checkGenerateAudio( postType );

					// No editor control to set: inserting the block is enough.
					cy.window()
						.its( 'wp.data' )
						.then( ( data ) => {
							flatten(
								data.select( 'core/block-editor' ).getBlocks()
							).forEach( ( block ) => {
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
						// The marker rides on <audio>, which carries the file.
						expect( body ).to.match(
							/<audio[^>]*data-beyondwords-audio="true"/
						);

						// Not the figure wrapping it, and not the paragraph.
						expect( body ).to.match(
							/<figure(?![^>]*data-beyondwords-audio)[^>]*class="[^"]*wp-block-audio/
						);
						expect( body ).to.match(
							/<p(?![^>]*data-beyondwords-audio)[^>]*>Spoken paragraph\./
						);

						expect( body ).to.contain( 'Cat audio caption.' );
					} );

					/* ----------------------------------- the front end */

					cy.viewPostById( postId );
					cy.get( '[data-beyondwords-audio]' ).should( 'not.exist' );
					cy.get( 'audio' ).should( 'exist' );
				} );
			} );
		} );

	it( 'edits an audio block from the sidebar without touching the saved source', () => {
		cy.createTestPost( {
			title: 'Cypress Test: block audio sidebar edit',
			postType: 'post',
			status: 'draft',
			content: CONTENT,
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.checkGenerateAudio();

			/* ------------------------------- the sidebar panel */

			selectAudioBlock();

			blockPanel().should( 'exist' );
			generation()
				.find( 'input[type="checkbox"]' )
				.should( 'be.checked' );

			// An audio block takes a language and voice like any other block;
			// the API decides what to do with them next to its own recording.
			setToggle( customize, true );
			blockSelect( 'Accent' ).select( 'British', { force: true } );
			blockSelect( 'Native' ).select( 'All', { force: true } );

			cy.publishWithConfirmation();

			/* --------------------------- the saved HTML source */

			cy.task( 'getPostContent', postId ).should( ( source ) => {
				// What the sidebar set is persisted on the block delimiter.
				expect( source ).to.match(
					/<!-- wp:audio \{[^}]*"beyondwordsLanguageCode":"en_GB"/
				);

				// The marker is built for the API body, so it is never saved.
				expect( source ).to.not.contain( 'data-beyondwords-audio' );
				expect( source ).to.not.contain( 'data-beyondwords-language' );
				expect( source ).to.match( /<audio[^>]*src=/ );
			} );

			/* ------------------------ what we send to the API */

			cy.task( 'getPostMetaJson', {
				postId,
				metaKey: SENT_BODY_META,
			} ).should( ( body ) => {
				expect( body ).to.match(
					/<audio[^>]*data-beyondwords-audio="true"/
				);
				// The voice override lands on the block's outermost tag.
				expect( body ).to.match(
					/<figure[^>]*data-beyondwords-language="en_GB"/
				);
			} );

			/* ----------------------------------- the front end */

			cy.viewPostById( postId );
			cy.get( '[data-beyondwords-audio]' ).should( 'not.exist' );
			cy.get( '[data-beyondwords-language]' ).should( 'not.exist' );
			cy.get( 'audio' ).should( 'exist' );
		} );
	} );

	it( 'drops an audio block that has generation disabled', () => {
		cy.createTestPost( {
			title: 'Cypress Test: block audio with generation disabled',
			postType: 'post',
			status: 'draft',
			content: CONTENT,
		} ).then( ( postId ) => {
			cy.visitPostEditorById( postId );
			cy.checkGenerateAudio();

			selectAudioBlock();
			setToggle( generation, false );

			cy.publishWithConfirmation();

			cy.task( 'getPostMetaJson', {
				postId,
				metaKey: SENT_BODY_META,
			} ).should( ( body ) => {
				expect( body ).to.not.contain( 'data-beyondwords-audio' );
				expect( body ).to.not.contain( 'Cat audio caption.' );
				expect( body ).to.contain( 'Spoken paragraph.' );
			} );
		} );
	} );
} );
