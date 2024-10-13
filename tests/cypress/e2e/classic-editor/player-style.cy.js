context( 'Classic Editor: Player Style', () => {
  const postTypes = require( '../../../fixtures/post-types.json' )

  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
    cy.activatePlugin( 'classic-editor' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  after( () => {
    cy.deactivatePlugin( 'classic-editor' )
  } )

  postTypes.filter( x => x.priority ).forEach( postType => {
    // @todo skipping because this fails now we auto-sync the API with WordPress
    it( `uses the plugin setting as the default selected option for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.get( 'select#beyondwords_player_style' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Standard", "Small", "Large", "Video"] )
      })

      // Check "Standard" is preselected
      cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Standard' )

      // Update the plugin settings to "Small"
      cy.setPlayerStyleInPluginSettings( 'Small' );

      // Check "Small" is preselected
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Small' )

      // Update the plugin settings to "Large"
      cy.setPlayerStyleInPluginSettings( 'Large' );

      // Check "Large" is preselected
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Large' )

      // @todo "Video"
      // Update the plugin settings to "Video"
      // cy.setPlayerStyleInPluginSettings( 'Video' );

      // Check "Video" is preselected
      // cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )
      // cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Video' )

      // Reset the plugin settings to "Standard"
      cy.setPlayerStyleInPluginSettings( 'Standard' );
    })

    it( `can set "Large" Player style for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Select a Player style
      cy.get( 'select#beyondwords_player_style' ).select( 'Large' )

      cy.classicSetPostTitle( `I can set "Large" Player style for a ${postType.name}` )

      if ( postType.preselect ) {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' )
      } else {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' )
        cy.get( 'input#beyondwords_generate_audio' ).check()
      }

      cy.get( 'input[type="submit"]' ).contains( 'Publish' ).click().wait( 500 )

      // "View post"
      cy.get( '#sample-permalink' ).click().wait( 500 )

      // Check Player has large player in frontend
      cy.getFrontendLargePlayer().should( 'exist' )

      // Check Player style has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Large' )
    } )

    it( `can set "Video" Player style for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Select a Player style
      cy.get( 'select#beyondwords_player_style' ).select( 'Video' )

      cy.classicSetPostTitle( `I can set "Video" Player style for a ${postType.name}` )

      if ( postType.preselect ) {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' )
      } else {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' )
        cy.get( 'input#beyondwords_generate_audio' ).check()
      }

      cy.get( 'input[type="submit"]' ).contains( 'Publish' ).click().wait( 500 )

      // "View post"
      cy.get( '#sample-permalink' ).click().wait( 500 )

      // Check Player has video player in frontend
      cy.getFrontendVideoPlayer().should( 'exist' )

      // Check Player style has also been saved in admin
      cy.get( '#wp-admin-bar-edit' ).find( 'a' ).click().wait( 500 )
      cy.get( 'select#beyondwords_player_style' ).find( 'option:selected' ).contains( 'Video' )
    } )
  } )

  postTypes.filter( x => ! x.supported ).forEach( postType => {
    it( `has no Player style component for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Player style should not be visible
      cy.get( 'select#beyondwords_player_style' ).should( 'not.exist' )
    } )
  } )
} )