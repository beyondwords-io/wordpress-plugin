/* global cy, before, beforeEach, after, context, it */

context( 'Plugins: AMP', () => {
	before( () => {
		// cy.task( 'reset' );
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'amp' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.deactivatePlugin( 'amp' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => [ 'post', 'page' ].includes( x.slug ) )
		.forEach( ( postType ) => {
			it( `${ postType.name } shows an <amp-iframe> player for AMP requests`, () => {
				cy.createPost( {
					postType,
				} );

				// cy.closeWelcomeToBlockEditorTips()

				cy.openBeyondwordsEditorPanel();

				cy.checkGenerateAudio( postType );

				cy.setPostTitle(
					`A ${ postType.slug } has an AMP iframe player`
				);

				cy.publishWithConfirmation();

				cy.getLabel( 'Generate audio' ).should( 'not.exist' );

				cy.hasPlayerInstances( 1 );

				// "View post"
				cy.viewPostViaSnackbar();

				// Non-AMP requests have a JS player.
		  		cy.get( 'amp-iframe' ).should( 'not.exist' );
				cy.getEnqueuedPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.url().then( ( url ) => {
					// View post as AMP by appending &amp=1
					cy.visit( `${ url }&amp=1` );
				} );

				cy.get( 'amp-iframe' ).should( 'exist' );
				cy.getEnqueuedPlayerScriptTag().should( 'not.exist' );
				cy.hasNoBeyondwordsWindowObject();
			} );
		} );
} );
