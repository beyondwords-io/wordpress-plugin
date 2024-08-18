context( 'Plugins: WPGraphQL', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
    cy.activatePlugin( 'wp-graphql' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  after( () => {
    cy.deactivatePlugin( 'wp-graphql' )
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `${postType.name} shows GraphQL data`, () => {
      cy.createPostWithAudio( `${postType.name} with WPGraphQL data`, postType )

      cy.visit( '/wp-admin/admin.php?page=graphiql-ide' ).wait( 500 )

      // Construct GraphQL query
      cy.get('.query-editor > .CodeMirror').first().then( editor => {
        editor[0].CodeMirror.setValue(`query NewQuery {
          ${postType.graphQLName}(where:{
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
        }`)
      }).wait( 500 )

      // Run the query
      cy.get('.execute-button').click().wait( 500 )

      // Test the query results
      cy.get('.result-window > .CodeMirror').first().then( editor => {
        const text = editor[0].CodeMirror.getValue()

        const json = JSON.parse( text )

        expect( json.data[postType.graphQLName].nodes[0].title ).to.eq( `${postType.name} with WPGraphQL data` )
        expect( json.data[postType.graphQLName].nodes[0].beyondwords.projectId ).to.eq( parseInt( Cypress.env( 'projectId' ) ) )
        expect( json.data[postType.graphQLName].nodes[0].beyondwords.contentId ).to.eq( Cypress.env( 'contentId' ) )
        expect( json.data[postType.graphQLName].nodes[0].beyondwords.podcastId ).to.eq( Cypress.env( 'contentId' ) )
      });
    } )
  } )
} )
