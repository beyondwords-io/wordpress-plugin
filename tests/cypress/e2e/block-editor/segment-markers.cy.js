/* global Cypress, cy, before, beforeEach, context, expect, it */

context( 'Block Editor: Segment markers', () => {
	before( () => {
		// cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	const markerRegex =
		/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	const testCases = [
		{
			id: 1,
			// eslint-disable-next-line max-len
			text: 'Latin symbols: á, é, í, ó, ú, ü, ñ, ¡, !, ¿, ?, Ä, ä, Ö, ö, Ü, ü, ẞ, ß, Æ, æ, Ø, ø, Å, å',
		},
		{ id: 2, text: 'Kanji: 任天堂' },
		{ id: 3, text: 'Katana: イリノイ州シカゴにて' },
		{
			id: 4,
			// eslint-disable-next-line max-len
			text: 'Mathematical symbols: αβγδεζηθικλμνξοπρσςτυφχψω ΓΔΘΛΞΠΣΦΨΩ ∫∑∏−±∞≈∝=≡≠≤≥×·⋅÷∂′″∇‰°∴∅ ∈∉∩∪⊂⊃⊆⊇¬∧∨∃∀⇒⇔→↔↑↓ℵ',
		},
	];

	// Test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `A ${ postType.name } without audio should not have segment markers`, () => {
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();

				cy.uncheckGenerateAudio( postType );

				cy.setPostTitle(
					`I can add a ${ postType.name } without segment markers`
				);

				// Add paragraphs
				cy.addParagraphBlock( 'One.' );
				cy.addParagraphBlock( 'Two.' );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getEnqueuedPlayerScriptTag().should( 'not.exist' );
				cy.hasNoBeyondwordsWindowObject();

				cy.contains( 'p', 'One.' ).should(
					'not.have.attr',
					'data-beyondwords-marker'
				);
				cy.contains( 'p', 'Two.' ).should(
					'not.have.attr',
					'data-beyondwords-marker'
				);
			} );

			it( `can add a ${ postType.name } with segment markers`, () => {
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostTitle(
					`I can add a ${ postType.name } with segment markers`
				);

				/**
				 * Ensure the marker is persistent (it DOES NOT change while typing)
				 */
				cy.get( '.wp-block-post-content p:last-of-type' ).click();
				// Type a letter
				cy.get( 'body' ).type( `O` );
				// Check the marker
				cy.contains( 'label', 'Segment marker' )
					.siblings( 'input' )
					.first()
					.invoke( 'val' )
					.then( ( originalMarker ) => {
						// Type another letter
						cy.get( 'body' ).type( `K` ).wait( 200 );
						// Get marker value again and check it hasn't changed
						cy.contains( 'label', 'Segment marker' )
							.siblings( 'input' )
							.first()
							.invoke( 'val' )
							.should( 'equal', originalMarker );
						cy.get( 'body' ).type( `{enter}` ).wait( 200 );
					} );

				/**
				 * Various test cases check we handle UTF-8 correctly
				 */
				testCases.forEach( ( testCase ) => {
					// Add paragraph
					cy.addParagraphBlock( testCase.text );

					// Grab assigned marker from UI input
					cy.contains( 'label', 'Segment marker' )
						.siblings( 'input' )
						.first()
						.invoke( 'val' )
						.should( 'match', markerRegex ) // Check regex
						.as( `marker${ testCase.id }` );
				} );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				testCases.forEach( ( testCase ) => {
					cy.get( `@marker${ testCase.id }` ).then( ( marker ) => {
						cy.contains( 'p', testCase.text )
							.invoke( 'attr', 'data-beyondwords-marker' )
							// @todo fix broken segment markers test
							.should( 'equal', marker ); // Check marker
						// .should( 'not.be.empty' ); // Check marker
					} );
				} );

				cy.deactivatePlugin( 'speechkit' );
				cy.reload();

				// Check content on page again, after deactivating the plugin
				testCases.forEach( ( testCase ) => {
					cy.contains( 'p', testCase.text ) // Text should be an exact UTF-8 match
						.should( 'not.have.attr', 'data-beyondwords-marker' );
				} );

				cy.activatePlugin( 'speechkit' );
			} );

			it( `assigns unique markers for duplicated blocks in a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostTitle(
					`I see unique markers for duplicated blocks in a ${ postType.name }`
				);

				// Add paragraph
				cy.addParagraphBlock( 'Test.' );

				// Grab assigned marker from UI input
				cy.contains( 'label', 'Segment marker' )
					.siblings( 'input' )
					.first()
					.invoke( 'val' )
					.as( 'marker1' );

				// Add first paragraph
				cy.get( '.editor-post-title' ).click();
				cy.contains( 'p.wp-block-paragraph', 'Test.' ).click();

				// Duplicate paragraph
				cy.get( '.block-editor-block-settings-menu' ).click();
				cy.contains(
					'.components-menu-item__item',
					'Duplicate'
				).click();

				cy.get( 'p:contains(Test.)' ).should( 'have.length', 2 );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.get( '.entry-content p:not(:empty)' )
					.should( 'have.length', 2 )
					.mapInvoke( 'getAttribute', 'data-beyondwords-marker' )
					.then( ( markers ) => {
						// Markers must be unique
						const unique = Cypress._.uniq( markers );
						expect(
							unique,
							'all markers are unique'
						).to.have.length( markers.length );

						// All markers must be UUIDs
						expect( markers[ 0 ] ).to.match( markerRegex );
						expect( markers[ 1 ] ).to.match( markerRegex );
					} );
			} );

			it( 'assigns markers when blocks are added programatically', () => {
				cy.createPost();

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostTitle(
					'I see markers when blocks are added programatically'
				);

				// Add paragraph
				cy.createBlockProgramatically( 'core/paragraph', {
					content: 'One.',
				} );

				// Add paragraph
				cy.createBlockProgramatically( 'core/paragraph', {
					content: 'Two.',
				} );

				cy.get( 'p:contains(One.)' ).should( 'have.length', 1 );
				cy.get( 'p:contains(Two.)' ).should( 'have.length', 1 );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.get( '.entry-content p:not(:empty)' )
					.should( 'have.length', 2 )
					.mapInvoke( 'getAttribute', 'data-beyondwords-marker' )
					.then( ( markers ) => {
						// Markers must be unique
						const unique = Cypress._.uniq( markers );
						expect(
							unique,
							'all markers are unique'
						).to.have.length( markers.length );

						// All markers must be UUIDs
						expect( markers[ 0 ] ).to.match( markerRegex );
						expect( markers[ 1 ] ).to.match( markerRegex );
					} );
			} );

			// So far unable to write tests for pasted content, all attempts have failed :(
			it( 'assigns markers when content is pasted', () => {
				cy.createPost();

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostTitle( 'I see markers for pasted content' );

				// Click "+ block" button
				cy.get(
					'.block-editor-default-block-appender__content'
				).click();

				cy.get( '.wp-block.is-selected' ).paste( 'One.\n\nTwo.' );

				cy.get( 'p:contains(One.)' ).should( 'have.length', 1 );
				cy.get( 'p:contains(Two.)' ).should( 'have.length', 1 );

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.get( '.entry-content p:not(:empty)' )
					.should( 'have.length', 2 )
					.mapInvoke( 'getAttribute', 'data-beyondwords-marker' )
					.then( ( markers ) => {
						// Markers must be unique
						const unique = Cypress._.uniq( markers );
						expect(
							unique,
							'all markers are unique'
						).to.have.length( markers.length );

						// All markers must be UUIDs
						expect( markers[ 0 ] ).to.match( markerRegex );
						expect( markers[ 1 ] ).to.match( markerRegex );
					} );
			} );
		} );

	it( `makes existing duplicate segment markers unique`, () => {
		cy.createPost();

		// cy.closeWelcomeToBlockEditorTips()
		cy.openBeyondwordsEditorPanel();

		cy.getBlockEditorCheckbox( 'Generate audio' ).check();

		cy.setPostTitle(
			`I see existing duplicate markers are replaced with unique markers`
		);

		// Add paragraph
		cy.createBlockProgramatically( 'core/paragraph', {
			content: 'One.',
			attributes: {
				beyondwordsMarker: '[DUPLICATE MARKER]',
			},
		} );

		// Add paragraph
		cy.createBlockProgramatically( 'core/paragraph', {
			content: 'Two.',
			attributes: {
				beyondwordsMarker: '[DUPLICATE MARKER]',
			},
		} );

		// Add paragraph
		cy.createBlockProgramatically( 'core/paragraph', {
			content: 'Three.',
			attributes: {
				beyondwordsMarker: '[DUPLICATE MARKER]',
			},
		} );

		cy.publishWithConfirmation();

		// "View post"
		cy.viewPostViaSnackbar();

		cy.getEnqueuedPlayerScriptTag().should( 'exist' );
		cy.hasPlayerInstances( 1 );

		cy.get( '.entry-content p:not(:empty)' )
			.should( 'have.length', 3 )
			.mapInvoke( 'getAttribute', 'data-beyondwords-marker' )
			.then( ( markers ) => {
				// Markers must be unique
				const unique = Cypress._.uniq( markers );
				expect( unique, 'all markers are unique' ).to.have.length(
					markers.length
				);

				// All markers must be UUIDs
				expect( markers[ 0 ] ).to.match( markerRegex );
				expect( markers[ 1 ] ).to.match( markerRegex );
				expect( markers[ 2 ] ).to.match( markerRegex );
			} );
	} );
} );
