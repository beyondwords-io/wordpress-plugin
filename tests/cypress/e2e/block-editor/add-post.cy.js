context( 'Block Editor: Add Post', () => {
  beforeEach( () => {
    cy.login()
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  postTypes.filter( x => x.supported ).forEach( postType => {
    it( `can add a ${postType.name} without audio`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.uncheckGenerateAudio( postType )

      cy.setPostTitle( `I can add a ${postType.name} without audio` )

      cy.publishWithConfirmation( false )

      cy.getLabel( 'Generate audio' ).should( 'exist' )

      cy.getAdminPlayer().should( 'not.exist' )

      // "View post"
      cy.viewPostViaSnackbar()

      cy.getFrontendPlayer().should( 'not.exist' )

      cy.visit( `/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc` )

      // See a [-] in the BeyondWords column
      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          cy.contains( 'td.beyondwords.column-beyondwords', '—' )
        } )
    } )

    it( `can add a ${postType.name} with audio`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostTitle( `I can add a ${postType.name} with audio` )

      cy.publishWithConfirmation( true )

      cy.getLabel( 'Generate audio' ).should( 'not.exist' )

      cy.getAdminPlayer().should( 'exist' )

      // "View post"
      cy.viewPostViaSnackbar()

      cy.getFrontendPlayer().should( 'exist' )

      cy.visit( `/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc` )

      // See a [tick] in the BeyondWords column
      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
        } )
    } )

    it( `can add a ${postType.name} with "Pending review" audio`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostStatus( 'pending' )

      cy.setPostTitle( `I can add a ${postType.name} with "Pending Review" audio` )

      cy.get('.editor-post-publish-button__button').click().wait( 1000 )

      cy.getLabel( 'Generate audio' ).should( 'not.exist' )

      // "Generate Audio" is replaced by "Pending" message'
      cy.get( 'input#beyondwords_generate_audio' ).should( 'not.exist' )
      cy.contains( '.beyondwords-sidebar', 'Listen to content saved as “Pending” in the BeyondWords dashboard.' )

      // "Pending review" message contains link to project
      cy.get( '.beyondwords-sidebar a' )
        .eq( 0 )
        .invoke( 'attr', 'href' )
        .should( 'eq', `https://dash.beyondwords.io/dashboard/project/${ Cypress.env( 'projectId' ) }/content` )

      cy.visit( `/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc` )

      // See a [tick] and "Pending" in the BeyondWords column
      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          cy.contains( 'span.post-state', 'Pending' )
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          cy.get( 'a.row-title' ).click().wait( 500 )
        } )
    } )
  } )

  postTypes.filter( x => ! x.supported ).forEach( postType => {
    it(`${postType.name} has no BeyondWords support`, () => {
      // BeyondWords column should not exist
      cy.visit( `/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc` )
      cy.get( 'th#beyondwords.column-beyondwords' ).should( 'not.exist' )

      // BeyondWords metabox should not exist
      cy.visit(`/wp-admin/post-new.php?post_type=${postType.slug}`).wait( 500 )
      cy.get( 'div#beyondwords' ).should( 'not.exist' )
    } )
  } )
} )
