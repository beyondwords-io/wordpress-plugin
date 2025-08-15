/* global cy, before, beforeEach, context, expect, it */

context( 'Block Editor: Player Style', () => {
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
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option' )
					.should( ( $els ) => {
						const values = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( values ).to.deep.eq( [
							'',
							'Standard',
							'Small',
							'Large',
							'Video',
						] );
					} );

				// Check "Standard" is preselected
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Standard' );

				// Update the plugin settings to "Small"
				cy.setPlayerStyleInPluginSettings( 'Small' );

				// Check "Small" is preselected
				cy.createPost( {
					postType,
				} );

				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Small' );

				// Update the plugin settings to "Large"
				cy.setPlayerStyleInPluginSettings( 'Large' );

				// Check "Large" is preselected
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Large' );

				// Update the plugin settings to "Video"
				cy.setPlayerStyleInPluginSettings( 'Video' );

				// Check "Video" is preselected
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Video' );

				// Reset the plugin settings to "Standard"
				cy.setPlayerStyleInPluginSettings( 'Standard' );
			} );

			it( `can set "Large" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can set "Large" Player style for a ${ postType.name }`,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				// Select a Player style
				cy.getBlockEditorSelect( 'Player style' ).select( 'Large' );

				cy.getBlockEditorCheckbox( 'Generate audio' ).check();

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player has video player in frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].playerStyle
					).to.eq( 'large' );
				} );

				// Check Player style has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Large' );
			} );

			it( `can set "Video" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
					title: `I can set "Video" Player style for a ${ postType.name }`,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				// Select a Player style
				cy.getBlockEditorSelect( 'Player style' ).select( 'Video' );

				cy.getBlockEditorCheckbox( 'Generate audio' ).check();

				cy.publishWithConfirmation();

				// "View post"
				cy.viewPostViaSnackbar();

				// Check Player has video player in frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].playerStyle
					).to.eq( 'video' );
				} );

				// Check Player style has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.openBeyondwordsEditorPanel();
				cy.getBlockEditorSelect( 'Player style' )
					.find( 'option:selected' )
					.contains( 'Video' );
			} );
		} );
} );
