context( 'Plugins: AMP', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
    cy.activatePlugin( 'amp' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  after( () => {
    cy.deactivatePlugin( 'amp' )
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test priority post types
  postTypes.filter( x => [ 'post', 'page' ].includes( x.slug ) ).forEach( postType => {
    it( `${postType.name} shows an <amp-iframe> player for AMP requests`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostTitle( `A ${postType.slug} has an AMP iframe player` )

      cy.publishWithConfirmation( true )

      cy.getLabel( 'Generate audio' ).should( 'not.exist' )

      cy.getAdminPlayer().should( 'exist' )

      // "View post"
      cy.viewPostViaSnackbar()

      cy.get( 'amp-iframe' ).should( 'not.exist' )
      cy.getFrontendPlayer().should( 'exist' )

      cy.url().then(url => {
        // View post as AMP by appending &amp=1
        cy.visit( `${url}&amp=1` ).wait( 500 )
      } )

      cy.get( 'amp-iframe' ).should( 'exist' )
      cy.getFrontendPlayer().should( 'not.exist' )
    } )
  } )
} )
