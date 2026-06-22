/**
 * @group classic-editor
 * @covers src/editor/components/generate-audio/,src/settings/class-preselect.php
 */

/* global cy, before, beforeEach, after, afterEach, context, it */

/**
 * Classic-editor term-gated preselect.
 *
 * The server renders the correct initial "Generate audio" state; classic-metabox.js
 * keeps it in step as hierarchical taxonomy terms are ticked/unticked.
 */
context( 'Classic Editor: term-gated Generate audio', () => {
	let newsId;

	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
		cy.task( 'createTerm', {
			taxonomy: 'category',
			name: 'CypressClassicNews',
		} ).then( ( id ) => {
			newsId = id;
		} );
	} );

	const setPreselect = ( value ) =>
		cy.task( 'updateOptionJson', {
			name: 'beyondwords_preselect',
			value,
		} );

	beforeEach( () => {
		cy.login();
	} );

	afterEach( () => {
		// Restore the default seed so later specs see post = 'all'.
		setPreselect( {
			post: { mode: 'all' },
			page: { mode: 'all' },
			cpt_active: { mode: 'all' },
		} );
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	it( 'Post not selected → Generate audio is not preselected', () => {
		setPreselect( {} ); // 'post' absent → off.
		cy.createPost( { postType: { slug: 'post' } } );
		cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' );
	} );

	it( 'All selected → Generate audio is preselected', () => {
		setPreselect( { post: { mode: 'all' } } );
		cy.createPost( { postType: { slug: 'post' } } );
		cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' );
	} );

	it( 'Terms selected → toggles as the matching category is ticked/unticked', () => {
		setPreselect( {
			post: { mode: 'terms', terms: { category: [ newsId ] } },
		} );
		cy.createPost( { postType: { slug: 'post' } } );

		// New post has no matching term → not preselected.
		cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' );

		// Tick the matching category → JS checks Generate audio.
		cy.get( `input[name="post_category[]"][value="${ newsId }"]` ).check();
		cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' );

		// Untick it → JS unchecks Generate audio (bidirectional).
		cy.get(
			`input[name="post_category[]"][value="${ newsId }"]`
		).uncheck();
		cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' );
	} );

	it( 'stops auto-toggling once Generate audio is changed by hand', () => {
		setPreselect( {
			post: { mode: 'terms', terms: { category: [ newsId ] } },
		} );
		cy.createPost( { postType: { slug: 'post' } } );

		// Auto-check via the matching term.
		cy.get( `input[name="post_category[]"][value="${ newsId }"]` ).check();
		cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' );

		// Manually override → freezes auto-management.
		cy.get( 'input#beyondwords_generate_audio' ).uncheck();

		// Re-toggling the term no longer changes Generate audio.
		cy.get(
			`input[name="post_category[]"][value="${ newsId }"]`
		).uncheck();
		cy.get( `input[name="post_category[]"][value="${ newsId }"]` ).check();
		cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' );
	} );
} );
