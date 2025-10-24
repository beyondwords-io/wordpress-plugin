/* global Cypress, cy, before, beforeEach, after, context, expect, it */

context( 'Plugins: WPGraphQL', () => {
	before( () => {
		cy.task( 'setupDatabase' );
		// One-time setup for all tests
		cy.login();
		cy.saveStandardPluginSettings();
		cy.activatePlugin( 'wp-graphql' );
	} );

	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	after( () => {
		cy.deactivatePlugin( 'wp-graphql' );
	} );

	const postTypes = require( '../../../../tests/fixtures/post-types.json' );

	// Only test priority post types
	postTypes
		.filter( ( x ) => x.priority )
		.forEach( ( postType ) => {
			it( `${ postType.name } shows GraphQL data`, () => {
				cy.publishPostWithAudio( {
					postType,
					title: `${ postType.name } with WPGraphQL data`,
				} );

				cy.visit( '/wp-admin/admin.php?page=graphiql-ide' );

				// Construct GraphQL query
				cy.get( '.query-editor > .CodeMirror' )
					.first()
					.then( ( editor ) => {
						editor[ 0 ].CodeMirror.setValue( `query NewQuery {
          ${ postType.graphQLName }(where:{
            orderby:{
              field:DATE,
              order:DESC
            }
          }){
            nodes {
              title
              beyondwords {
                projectId
                contentId
                podcastId
              }
            }
          }
        }` );
					} );

				// Run the query
				cy.get( '.execute-button' ).click().wait( 1000 );

				// Test the query results
				cy.get( '.result-window > .CodeMirror' )
					.first()
					.then( ( editor ) => {
						const text = editor[ 0 ].CodeMirror.getValue();

						const json = JSON.parse( text );

						expect(
							json.data[ postType.graphQLName ].nodes[ 0 ].title
						).to.eq( `${ postType.name } with WPGraphQL data` );
						expect(
							json.data[ postType.graphQLName ].nodes[ 0 ]
								.beyondwords.projectId
						).to.eq( parseInt( Cypress.env( 'projectId' ) ) );
						expect(
							json.data[ postType.graphQLName ].nodes[ 0 ]
								.beyondwords.contentId
						).to.eq( Cypress.env( 'contentId' ) );
						expect(
							json.data[ postType.graphQLName ].nodes[ 0 ]
								.beyondwords.podcastId
						).to.eq( Cypress.env( 'contentId' ) );
					} );
			} );
		} );
} );
