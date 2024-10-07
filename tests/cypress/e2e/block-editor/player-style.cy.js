context( 'Block Editor: Player Style', () => {
  const postTypes = require( '../../../fixtures/post-types.json' )

  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    // @todo skipping because this fails now we auto-sync the API with WordPress
    it.skip( `uses the plugin setting as the default selected option for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Assert we have the expected Voices
      cy.getBlockEditorSelect( 'Player style' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Standard", "Small", "Large", "Video"] )
      })

      // Check "Standard" is preselected
      cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Standard' )

      // Update the plugin settings to "Small"
      cy.setPlayerStyleInPluginSettings( 'Small' );

      // Check "Small" is preselected
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      cy.closeWelcomeToBlockEditorTips()
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Small' )

      // Update the plugin settings to "Large"
      cy.setPlayerStyleInPluginSettings( 'Large' );

      // Check "Large" is preselected
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      cy.closeWelcomeToBlockEditorTips()
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Large' )

      // @todo "Video"

      // Update the plugin settings to "Video"
      // cy.setPlayerStyleInPluginSettings( 'Video' );

      // @todo Check "Video" is preselected
      // cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      // cy.closeWelcomeToBlockEditorTips()
      // cy.openBeyondwordsEditorPanel()
      // cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Video' )

      // Reset the plugin settings to "Standard"
      cy.setPlayerStyleInPluginSettings( 'Standard' );
    })

    it( `can set "Large" Player style for a ${postType.name}`, () => {

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Select a Player style
      cy.getBlockEditorSelect( 'Player style' ).select( 'Large' )

      cy.setPostTitle( `I can set "Large" Player style for a ${postType.name}` )

      cy.getBlockEditorCheckbox( 'Generate audio' ).check()

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // Check Player has video player in frontend
      cy.getFrontendLargePlayer().should( 'exist' )

      // Check Player style has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Large' )
    } )

    it( `can set "Video" Player style for a ${postType.name}`, () => {

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Select a Player style
      cy.getBlockEditorSelect( 'Player style' ).select( 'Video' )

      cy.setPostTitle( `I can set "Video" Player style for a ${postType.name}` )

      cy.getBlockEditorCheckbox( 'Generate audio' ).check()

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // Check Player has video player in frontend
      cy.getFrontendVideoPlayer().should( 'exist' )

      // Check Player style has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player style' ).find('option:selected').contains( 'Video' )
    } )
  } )
} )
