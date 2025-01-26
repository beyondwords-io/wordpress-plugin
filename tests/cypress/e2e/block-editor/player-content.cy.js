context( 'Block Editor: Player Content', () => {
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
    it( `uses the plugin setting as the default selected option for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Assert we have the expected Voices
      cy.getBlockEditorSelect( 'Player content' ).find( 'option' ).should( $els => {
        const labels = [ ...$els ].map( el => el.innerText.trim() )
        expect(labels).to.deep.eq( ["Article", "Summary"] )

        const values = [ ...$els ].map( el => el.value )
        expect(values).to.deep.eq( ["", "summary"] )
      })

      // Check "Article" is preselected
      cy.getBlockEditorSelect( 'Player content' ).find('option:selected').contains( 'Article' )
    })

    it( `can set "Article" Player content for a ${postType.name}`, () => {

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Select a Player content
      cy.getBlockEditorSelect( 'Player content' ).select( 'Article' )

      cy.setPostTitle( `I can set "Article" Player content for a ${postType.name}` )

      cy.getBlockEditorCheckbox( 'Generate audio' ).check()

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // Check Player appears frontend
      cy.getEnqueuedPlayerScriptTag().should( 'exist' )
      cy.getFrontendPlayer().should( 'exist' )

      // window.BeyondWords should contain 1 player instance
      cy.window().then( win => {
        cy.wait( 2000 )
        expect( win.BeyondWords ).to.not.be.undefined;
        expect( win.BeyondWords.Player.instances() ).to.have.length( 1 );
        expect( win.BeyondWords.Player.instances()[0].loadContentAs ).to.deep.eq( [ 'article' ] );
      } );

      // Check Player content has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player content' ).find('option:selected').contains( 'Article' )
    } )

    it( `can set "Summary" Player content for a ${postType.name}`, () => {

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Select a Player content
      cy.getBlockEditorSelect( 'Player content' ).select( 'Summary' )

      cy.setPostTitle( `I can set "Summary" Player content for a ${postType.name}` )

      cy.getBlockEditorCheckbox( 'Generate audio' ).check()

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // Check Player appears frontend
      cy.getEnqueuedPlayerScriptTag().should( 'exist' )
      cy.getFrontendPlayer().should( 'exist' )

      // window.BeyondWords should contain 1 player instance
      cy.window().then( win => {
        cy.wait( 2000 )
        expect( win.BeyondWords ).to.not.be.undefined;
        expect( win.BeyondWords.Player.instances() ).to.have.length( 1 );
        expect( win.BeyondWords.Player.instances()[0].loadContentAs ).to.deep.eq( [ 'summary' ] );
      } );

      // Check Player content has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Player content' ).find('option:selected').contains( 'Summary' )
    } )
  } )
} )
