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

	beforeEach( () => {
		cy.login();
		cy.task( 'updateOptionJson', {
			name: 'beyondwords_preselect',
			value: { post: { mode: 'terms', terms: { category: [ newsId ] } } },
		} );
	} );

	afterEach( () => {
		// Restore the default seed so later specs see post = 'all'.
		cy.task( 'updateOptionJson', {
			name: 'beyondwords_preselect',
			value: {
				post: { mode: 'all' },
				page: { mode: 'all' },
				cpt_active: { mode: 'all' },
			},
		} );
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	it( 'toggles Generate audio as the matching category is ticked/unticked', () => {
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
} );
