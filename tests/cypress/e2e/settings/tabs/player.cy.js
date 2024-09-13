context( 'Settings > Player',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'opens the "Player" tab', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Player' )
  } )

  it( 'uses "Enabled" Player UI setting', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Enabled' )
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    cy.createPostWithAudio( '"Enabled" Player UI' )

    // Admin should have latest player
    cy.getAdminPlayer().should( 'exist' )

    // Frontend should have a player div
    cy.viewPostViaSnackbar()
    cy.getFrontendPlayer().should( 'exist' )

    // window.BeyondWords should contain 1 player instance
    cy.window().then( win => {
      cy.wait( 2000 )
      expect( win.BeyondWords ).to.not.be.undefined;
      expect( win.BeyondWords.Player.instances() ).to.have.length( 1 );
      expect( win.BeyondWords.Player.instances()[0].showUserInterface ).to.eq( true );
    } );
  } )

  it( 'uses "Headless" Player UI setting', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Headless' )
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    cy.createPostWithAudio( '"Headless" Player UI' )

    // Admin should have latest player
    cy.getAdminPlayer().should( 'exist' )

    // Frontend should have a player div without a UI
    cy.viewPostViaSnackbar()
    cy.get( '.beyondwords-player.bwp' ).should( 'exist' )
    cy.get( '.beyondwords-player .user-interface' ).should( 'not.exist' )

    // window.BeyondWords should contain 1 player instance
    cy.window().then( win => {
      cy.wait( 2000 )
      expect( win.BeyondWords ).to.not.be.undefined;
      expect( win.BeyondWords.Player.instances() ).to.have.length( 1 );
      expect( win.BeyondWords.Player.instances()[0].showUserInterface ).to.eq( false );
    } );
  } )

  it( 'uses "Disabled" Player UI setting', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Disabled' )
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    cy.createPostWithAudio( '"Disabled" Player UI' )

    // Admin should have latest player
    cy.getAdminPlayer().should( 'exist' )

    // Frontend should not have a player div
    cy.viewPostViaSnackbar()
    cy.get( '.beyondwords-player' ).should( 'not.exist' )

    // window.BeyondWords should be undefined
    cy.window().then( win => {
      cy.wait( 2000 )
      expect(win.BeyondWords).to.be.undefined;
    } );
  } )
} )
