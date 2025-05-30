/* global cy, before, beforeEach, context, expect, it */

context( 'Block Editor: Player Content', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
	} );

	beforeEach( () => {
		cy.login();
	} );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `uses the plugin setting as the default for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.openBeyondwordsEditorPanel();

				// Assert we have the expected Voices
				cy.getBlockEditorSelect( 'Player content' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( labels ).to.deep.eq( [ 'Article', 'Summary' ] );

						const values = [ ...$els ].map( ( el ) => el.value );
						expect( values ).to.deep.eq( [ '', 'summary' ] );
					} );

				// Check "Article" is preselected
				cy.getBlockEditorSelect( 'Player content' )
					.find( 'option:selected' )
					.contains( 'Article' );
			} );

			it( `can set "Article" Player content for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can set "Article" Player content for a ${ postType.name }`,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				// Select a Player content
				cy.getBlockEditorSelect( 'Player content' ).select( 'Article' );

				cy.getBlockEditorCheckbox( 'Generate audio' ).check();

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player appears frontend
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].summary
					).to.eq( false );
				} );

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player content' )
					.find( 'option:selected' )
					.contains( 'Article' );
			} );

			it( `can set "Summary" Player content for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can set "Summary" Player content for a ${ postType.name }`,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				// Select a Player content
				cy.getBlockEditorSelect( 'Player content' ).select( 'Summary' );

				cy.getBlockEditorCheckbox( 'Generate audio' ).check();

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player appears frontend
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].summary
					).to.eq( true );
				} );

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player content' )
					.find( 'option:selected' )
					.contains( 'Summary' );
			} );
		} );
} );
