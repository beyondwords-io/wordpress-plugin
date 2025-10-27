/* global cy, before, beforeEach, after, context, it */

context( 'Plugins: AMP', () => {
	before( () => {
		cy.task( 'activatePlugin', 'amp' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'amp' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => [ 'post', 'page' ].includes( x.slug ) )
		.forEach( ( postType ) => {
			it( `${ postType.name } shows an <amp-iframe> player for AMP requests`, () => {
				cy.publishPostWithAudio( {
					postType,
					title: `A ${ postType.slug } has an AMP iframe player`,
				} );

				// "View post"
				cy.viewPostViaSnackbar();

				// Non-AMP requests have a JS player.
				cy.get( 'amp-iframe' ).should( 'not.exist' );
				cy.getPlayerScriptTag().should( 'exist' );
				cy.hasPlayerInstances( 1 );

				cy.url().then( ( url ) => {
					// View post as AMP by appending &amp=1
					cy.visit( `${ url }&amp=1` );
				} );

				cy.get( 'amp-iframe' ).should( 'exist' );
				cy.getPlayerScriptTag().should( 'not.exist' );
				cy.hasNoBeyondwordsWindowObject();
			} );
		} );
} );
