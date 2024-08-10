context( 'Classic Editor: Insert BeyondWords Player', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.savePluginSettings()
    cy.activatePlugin( 'classic-editor' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  after( () => {
    cy.deactivatePlugin( 'classic-editor' )
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `can add multiple player blocks into a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.get( 'input#beyondwords_generate_audio' ).check()

      // Add 3x players
      cy.get( 'div[aria-label="Insert BeyondWords player"]' ).children( 'button' ).click()
      cy.get( 'div[aria-label="Insert BeyondWords player"]' ).children( 'button' ).click()
      cy.get( 'div[aria-label="Insert BeyondWords player"]' ).children( 'button' ).click()

      // Count 3x players in editor iframe
      cy.getTinyMceIframeBody().find( 'div[data-beyondwords-player="true"][contenteditable="false"]' ).should( 'have.length', 3)

      cy.contains( 'input[type="submit"]', 'Publish' ).click().wait( 1000 )

      // Wait for success message
      cy.get( '#message.notice-success' )

      cy.get( '#sample-permalink' ).click().wait( 500 )

      // Count 3x players in frontend
      cy.get( 'div[data-beyondwords-player="true"][contenteditable="false"]' ).should( 'have.length', 3)
    } )

    it( `can add shortcodes into a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.get( 'input#beyondwords_generate_audio' ).check()

      // Focus TinyMCE
      cy.getTinyMceIframeBody().click().wait( 1000 )

      // Add 3x shortcodes
      cy.getTinyMceIframeBody().type( 'Shortcode 1:{enter}[beyondwords_player]{enter}Shortcode 2:{enter}[beyondwords_player]{enter}Shortcode 3:{enter}[beyondwords_player]{enter}' )

      cy.contains( 'input[type="submit"]', 'Publish' ).click().wait( 1000 )

      // Wait for success message
      cy.get( '#message.notice-success' )

      cy.get( '#sample-permalink' ).click().wait( 500 )

      // Count 3x players in frontend
      cy.get( 'div[data-beyondwords-player="true"][contenteditable="false"]' ).should( 'have.length', 3)
    } )
  } )
} )
