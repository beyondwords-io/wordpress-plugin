/* global cy, before, beforeEach, after, context, expect, it */

context( 'Classic Editor: Player Style', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	before( () => {
		cy.task( 'setupDatabase' );
		// One-time setup for all tests
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	after( () => {
		cy.deactivatePlugin( 'classic-editor' );
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `uses the plugin setting as the default for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'select#beyondwords_player_style' )
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
				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Standard' );

				// Update the plugin settings to "Small"
				cy.setPlayerStyleInPluginSettings( 'Small' );

				// Check "Small" is preselected
				cy.createPost( {
					postType,
				} );

				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Small' );

				// Update the plugin settings to "Large"
				cy.setPlayerStyleInPluginSettings( 'Large' );

				// Check "Large" is preselected
				cy.createPost( {
					postType,
				} );

				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Large' );

				// Update the plugin settings to "Video"
				cy.setPlayerStyleInPluginSettings( 'Video' );

				// Check "Video" is preselected
				cy.createPost( {
					postType,
				} );

				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Video' );

				// Reset the plugin settings to "Standard"
				cy.setPlayerStyleInPluginSettings( 'Standard' );
			} );

			it( `can set "Large" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// Select a Player style
				cy.get( 'select#beyondwords_player_style' ).select( 'Large' );

				cy.classicSetPostTitle(
					`I can set "Large" Player style for a ${ postType.name }`
				);

				if ( postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'be.checked'
					);
				} else {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'not.be.checked'
					);
					cy.get( 'input#beyondwords_generate_audio' ).check();
				}

				cy.get( 'input[type="submit"]' ).contains( 'Publish' ).click();

				// "View post"
				cy.get( '#sample-permalink' ).click();

				// Check Player has large player in frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1, {
					playerStyle: 'large',
				} );

				// Check Player style has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Large' );
			} );

			it( `can set "Video" Player style for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// Select a Player style
				cy.get( 'select#beyondwords_player_style' ).select( 'Video' );

				cy.classicSetPostTitle(
					`I can set "Video" Player style for a ${ postType.name }`
				);

				if ( postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'be.checked'
					);
				} else {
					cy.get( 'input#beyondwords_generate_audio' ).should(
						'not.be.checked'
					);
					cy.get( 'input#beyondwords_generate_audio' ).check();
				}

				cy.get( 'input[type="submit"]' ).contains( 'Publish' ).click();

				// "View post"
				cy.get( '#sample-permalink' ).click();

				// Check Player has video player in frontend
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1, {
					playerStyle: 'video',
				} );

				// Check Player style has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click();
				cy.get( 'select#beyondwords_player_style' )
					.find( 'option:selected' )
					.contains( 'Video' );
			} );
		} );

	postTypes
		.filter( ( x ) => ! x.supported )
		.forEach( ( postType ) => {
			it( `has no Player style component for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// Player style should not be visible
				cy.get( 'select#beyondwords_player_style' ).should(
					'not.exist'
				);
			} );
		} );
} );
