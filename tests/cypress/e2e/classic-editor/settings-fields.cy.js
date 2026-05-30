/**
 * @group classic-editor
 * @covers src/editor/components/settings-fields/
 */

/* global cy, before, beforeEach, after, context, expect, it */

/**
 * Classic-editor Content/Format/Player settings fields — the metabox
 * counterparts of the block editor's settings panel. Mirrors
 * tests/cypress/e2e/block-editor/settings-panel.cy.js using native <select>
 * controls and their #id hooks.
 */
context( 'Classic Editor: Settings fields', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	const optionLabels = ( $els ) =>
		[ ...$els ].map( ( el ) => el.innerText.trim() );

	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `Content section toggles Script template for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_source' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Post',
							'Script',
							'Post + script',
						] );
					} );

				// Script template hidden while Source = Post.
				cy.get(
					'#beyondwords-metabox-settings--beyondwords-script-template-id'
				).should( 'not.be.visible' );

				// Switching to Script reveals it, Project default first.
				cy.get( 'select#beyondwords_source' ).select( 'Script' );
				cy.get(
					'#beyondwords-metabox-settings--beyondwords-script-template-id'
				).should( 'be.visible' );
				cy.get( 'select#beyondwords_script_template_id' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels ).to.include( 'Default' );
					} );
			} );

			it( `Format section toggles Video template + size for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_output' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Audio',
							'Video',
							'Audio + video',
						] );
					} );

				// Video controls hidden while Output = Audio.
				cy.get(
					'#beyondwords-metabox-settings--beyondwords-video-template-id'
				).should( 'not.be.visible' );
				cy.get(
					'#beyondwords-metabox-settings--beyondwords-video-size'
				).should( 'not.be.visible' );

				cy.get( 'select#beyondwords_output' ).select( 'Video' );

				cy.get(
					'#beyondwords-metabox-settings--beyondwords-video-template-id'
				).should( 'be.visible' );
				cy.get( 'select#beyondwords_video_template_id' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels ).to.include( 'Social' );
					} );

				cy.get(
					'#beyondwords-metabox-settings--beyondwords-video-size'
				).should( 'be.visible' );
				cy.get( 'select#beyondwords_video_size' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels.join( ' ' ) ).to.match( /landscape/i );
					} );
			} );

			it( `Player Embed options derive from Source × Output for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				// Default Post + Audio → None / Audio (post).
				cy.get( 'select#beyondwords_embed' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'None',
							'Audio (post)',
						] );
					} );

				// Post + script × Audio + video → all four assets.
				cy.get( 'select#beyondwords_source' ).select( 'Post + script' );
				cy.get( 'select#beyondwords_output' ).select( 'Audio + video' );

				cy.get( 'select#beyondwords_embed' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'None',
							'Audio (post)',
							'Audio (script)',
							'Video (post)',
							'Video (script)',
						] );
					} );
			} );

			it( `persists Content/Format/Player selections for a ${ postType.name }`, () => {
				cy.createPost( { postType } );

				cy.get( 'select#beyondwords_source' ).select( 'Post + script' );
				cy.get( 'select#beyondwords_script_template_id' ).select(
					'Default'
				);
				cy.get( 'select#beyondwords_output' ).select( 'Audio + video' );
				cy.get( 'select#beyondwords_embed' ).select( 'Audio (script)' );

				cy.classicSetPostTitle(
					`I can set Content/Format/Player for a ${ postType.name }`
				);

				if ( ! postType.preselect ) {
					cy.get( 'input#beyondwords_generate_audio' ).check();
				}

				cy.contains( 'input[type="submit"]', 'Publish' ).click();

				// Selections persist after a page refresh.
				cy.get( 'select#beyondwords_source' )
					.find( 'option:selected' )
					.should( 'have.text', 'Post + script' );
				cy.get( 'select#beyondwords_output' )
					.find( 'option:selected' )
					.should( 'have.text', 'Audio + video' );
				cy.get( 'select#beyondwords_embed' )
					.find( 'option:selected' )
					.should( 'have.text', 'Audio (script)' );
			} );
		} );
} );
