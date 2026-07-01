/**
 * @group block-editor
 * @covers src/editor/components/settings-panel/,src/settings/store/
 */

/* global cy, beforeEach, context, expect, it */

/**
 * Block-editor Settings panel: Content, Format, Voice (+ Model), Player.
 *
 * Targets the SelectControl className hooks (`beyondwords--source` etc.), which
 * are unique and only rendered when their control is shown — so a missing
 * control is asserted with `should('not.exist')`. The document-setting panel's
 * Voice/Player sections aren't mounted while the plugin sidebar is open, so the
 * hooks resolve to a single element. `.select()` is forced because the section
 * PanelBody may be collapsed/scrolled.
 */
context( 'Block Editor: Settings panel', () => {
	const postTypes = require( '../../../fixtures/post-types.json' );

	beforeEach( () => {
		cy.login();
	} );

	const select = ( cls ) => cy.get( `.${ cls } select` );

	const optionLabels = ( $els ) =>
		[ ...$els ].map( ( el ) => el.innerText.trim() );

	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `Content section toggles Script template for a ${ postType.name }`, () => {
				cy.createPost( { postType } );
				cy.openBeyondwordsPluginSidebar();

				// Generate audio toggle sits at the top of the Content panel,
				// above Source.
				cy.get( '.beyondwords--generate-audio' ).should( 'exist' );
				cy.get( '.beyondwords--generate-audio, .beyondwords--source' )
					.first()
					.should( 'have.class', 'beyondwords--generate-audio' );

				select( 'beyondwords--source' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Post',
							'Script',
							'Post + script',
						] );
					} );

				// Script template hidden while Source = Post.
				cy.get( '.beyondwords--script-template' ).should( 'not.exist' );

				// Switching to Script reveals it, Project default first.
				select( 'beyondwords--source' ).select( 'Script', {
					force: true,
				} );
				select( 'beyondwords--script-template' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels ).to.include( 'Default' );
					} );
			} );

			it( `Format section toggles Video template + size for a ${ postType.name }`, () => {
				cy.createPost( { postType } );
				cy.openBeyondwordsPluginSidebar();

				select( 'beyondwords--output' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Audio',
							'Video',
							'Audio + video',
						] );
					} );

				// Video controls hidden while Output = Audio.
				cy.get( '.beyondwords--video-template' ).should( 'not.exist' );
				cy.get( '.beyondwords--video-size' ).should( 'not.exist' );

				select( 'beyondwords--output' ).select( 'Video', {
					force: true,
				} );

				select( 'beyondwords--video-template' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels ).to.include( 'Social' );
					} );

				select( 'beyondwords--video-size' )
					.find( 'option' )
					.should( ( $els ) => {
						const labels = optionLabels( $els );
						expect( labels[ 0 ] ).to.eq( 'Project default' );
						expect( labels.join( ' ' ) ).to.match( /landscape/i );
					} );
			} );

			it( `Voice section filters the Voice list by Model for a ${ postType.name }`, () => {
				cy.createPost( { postType } );
				cy.openBeyondwordsPluginSidebar();

				// "Customize" is opt-in; enable it to reveal the Language/Model fields.
				cy.get(
					'.beyondwords--customize input[type="checkbox"]'
				).check( {
					force: true,
				} );

				// Enabling Customize pre-selects the project default language
				// (mock: en_US → English (American)); wait for it before picking.
				select( 'beyondwords--language' )
					.find( 'option:selected' )
					.should( 'have.text', 'English (American)' );

				// Model is a language-level filter: each ElevenLabs model plus a
				// "Standard" bucket, "Select a model" first. The Voice list is
				// hidden until a model is chosen.
				select( 'beyondwords--model' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a model',
							'Multilingual v2',
							'v3',
							'Flash v2.5',
							'Legacy',
						] );
					} );
				cy.get( '.beyondwords--voice' ).should( 'not.exist' );

				// Picking a model narrows the Voice list to the voices that offer
				// it (v3 → Bridget + Caleb).
				select( 'beyondwords--model' ).select( 'v3', { force: true } );
				select( 'beyondwords--voice' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'Select a voice',
							'Bridget',
							'Caleb',
						] );
					} );
			} );

			it( `Player Embed options derive from Source × Output for a ${ postType.name }`, () => {
				cy.createPost( { postType } );
				cy.openBeyondwordsPluginSidebar();

				// Default Post + Audio → None / Audio (post).
				select( 'beyondwords--embed' )
					.find( 'option' )
					.should( ( $els ) => {
						expect( optionLabels( $els ) ).to.deep.eq( [
							'None',
							'Audio (post)',
						] );
					} );

				// Post + script × Audio + video → all four assets.
				select( 'beyondwords--source' ).select( 'Post + script', {
					force: true,
				} );
				select( 'beyondwords--output' ).select( 'Audio + video', {
					force: true,
				} );

				select( 'beyondwords--embed' )
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
		} );
} );
