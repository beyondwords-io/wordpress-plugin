context( 'Block Editor: Display Player', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.savePluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `hides and reshows the player for post type: ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostTitle( `I can toggle player visibility for a ${postType.name}` )

      cy.publishWithConfirmation( true )

      cy.getLabel( 'Generate audio' ).should( 'not.exist' )

      cy.getAdminPlayer().should( 'exist' )

      // "View post"
      cy.viewPostViaSnackbar()

      cy.getFrontendPlayer().should( 'exist' )

      cy.visit(`/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc`)

      // See a [tick] in the BeyondWords column
      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          cy.get( 'td.beyondwords.column-beyondwords > span.beyondwords--disabled' ).should( 'not.exist' )
          cy.get( 'a.row-title' ).click().wait( 500 )
        } )

      cy.contains( 'a', 'BeyondWords sidebar' ).click().wait( 500 )

      cy.getBlockEditorCheckbox( 'Display player' ).should( 'be.checked' )
      cy.getLabel( 'Display player' ).click()
      cy.getBlockEditorCheckbox( 'Display player' ).should( 'not.be.checked' )

      cy.savePost()

      // "View post"
      cy.viewPostViaSnackbar()

      cy.getFrontendPlayer().should( 'not.exist' )

      cy.visit(`/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc`)

      // See a [tick] and "Disabled" in the BeyondWords column
      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          cy.contains( 'td.beyondwords.column-beyondwords > span.beyondwords--disabled', 'Disabled' )
          cy.get( 'a.row-title' ).click().wait( 1000 )
        } )

      cy.contains( 'a', 'BeyondWords sidebar' ).click().wait( 500 )

      cy.getBlockEditorCheckbox( 'Display player' ).should( 'not.be.checked' )
      cy.getLabel( 'Display player' ).click()
      cy.getBlockEditorCheckbox( 'Display player' ).should( 'be.checked' )

      cy.savePost()

      // "View post"
      cy.viewPostViaSnackbar()

      cy.getFrontendPlayer().should( 'exist' )
    } )
  } )
} )
