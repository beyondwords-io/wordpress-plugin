/* global cy, before, beforeEach, after, context, expect, it */

context( 'Classic Editor: Player Content', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	before( () => {
		cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.deactivatePlugin( 'classic-editor' );
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `shows the "Player content" field for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.get( 'select#beyondwords_player_content' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = [ ...$els ].map( ( el ) =>
							el.innerText.trim()
						);
						expect( labels ).to.deep.eq( [ 'Article', 'Summary' ] );

						const values = [ ...$els ].map( ( el ) => el.value );
						expect( values ).to.deep.eq( [ '', 'summary' ] );
					} );
			} );

			it( `can set "Article" Player content for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				cy.classicSetPostTitle(
					`I can set "Article" Player content for a ${ postType.name }`
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

				cy.get( 'input[type="submit"]' )
					.contains( 'Publish' )
					.click()
					.wait( 100 );

				// "View post"
				cy.get( '#sample-permalink' ).click().wait( 100 );

				// Check Player appears frontend
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					cy.wait( 500 );
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].loadContentAs
					).to.deep.eq( [ 'article' ] );
				} );

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 100 );
				cy.get( 'select#beyondwords_player_content' )
					.find( 'option:selected' )
					.contains( 'Article' );
			} );

			it( `can set "Summary" Player content for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// Select a Player content
				cy.get( 'select#beyondwords_player_content' ).select(
					'Summary'
				);

				cy.classicSetPostTitle(
					`I can set "Summary" Player content for a ${ postType.name }`
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

				cy.get( 'input[type="submit"]' )
					.contains( 'Publish' )
					.click()
					.wait( 100 );

				// "View post"
				cy.get( '#sample-permalink' ).click().wait( 100 );

				// Check Player appears frontend
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				// window.BeyondWords should contain 1 player instance
				cy.window().then( ( win ) => {
					cy.wait( 500 );
					// eslint-disable-next-line no-unused-expressions
					expect( win.BeyondWords ).to.exist;
					expect( win.BeyondWords.Player.instances() ).to.have.length(
						1
					);
					expect(
						win.BeyondWords.Player.instances()[ 0 ].loadContentAs
					).to.deep.eq( [ 'summary' ] );
				} );

				// Check Player content has also been saved in admin
				cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 100 );
				cy.get( 'select#beyondwords_player_content' )
					.find( 'option:selected' )
					.contains( 'Summary' );
			} );
		} );

	postTypes
		.filter( ( x ) => ! x.supported )
		.forEach( ( postType ) => {
			it( `has no Player content component for a ${ postType.name }`, () => {
				cy.createPost( {
					postType,
				} );

				// Player content should not be visible
				cy.get( 'select#beyondwords_player_content' ).should(
					'not.exist'
				);
			} );
		} );
} );
