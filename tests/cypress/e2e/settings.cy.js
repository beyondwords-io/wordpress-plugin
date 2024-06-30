context( 'Settings tests',  () => {
  beforeEach( () => {
    cy.task( 'reset' )
    cy.login()
  } )

  after( () => {
    cy.task( 'reset' )
    cy.login()
    cy.savePluginSettings()
  } )

  it( 'only shows basic settings until valid auth creds are supplied', () => {
    cy.visit( '/wp-admin' )

    cy.get( '.notice.notice-info' ).find( 'p' ).eq( 0 ).contains( 'To use BeyondWords, please update the plugin settings.' )
    cy.get( '.notice.notice-info' ).find( 'p' ).eq( 1 ).contains( 'Don’t have a BeyondWords account yet?' )
    cy.get( '.notice.notice-info' ).find( 'p' ).eq( 2 ).find( 'a.button.button-secondary' ).contains( 'Sign up free' )

    cy.get( '.notice.notice-info' ).find( 'p' ).eq( 0 ).find( 'a' ).click()

    // Empty API Key & Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).should( 'have.value', '' )
    cy.get( 'input[name="beyondwords_project_id"]' ).should( 'have.value', '' )
    cy.get( 'input[type="submit"]' ).click()

    // 2 errors
    cy.get( '.notice-error' ).find( 'li' ).should('have.length', 2)
    cy.get( '.notice-error' ).find( 'li' ).eq( 0 ).contains( 'Please enter the BeyondWords API key. This can be found in your project settings.' )
    cy.get( '.notice-error' ).find( 'li' ).eq( 1 ).contains( 'Please enter your BeyondWords project ID. This can be found in your project settings.' )

    // No Advanced settings
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.exist' )

    // Empty API Key
    cy.get( 'input[name="beyondwords_api_key"]' ).clear()
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    // 1 error
    cy.get( '.notice-error' ).find( 'li' ).should('have.length', 1)
    cy.get( '.notice-error' ).find( 'li' ).eq( 0 ).contains( 'Please enter the BeyondWords API key. This can be found in your project settings.' )

    // No Advanced settings
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.exist' )

    // Empty Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear()
    cy.get( 'input[type="submit"]' ).click()

    // 1 error
    cy.get( '.notice-error' ).find( 'li' ).should('have.length', 1)
    cy.get( '.notice-error' ).find( 'li' ).eq( 0 ).contains( 'Please enter your BeyondWords project ID. This can be found in your project settings.' )

    // No Advanced settings
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.exist' )

    // Invalid creds
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( '401' ) // Project 401 triggers a 403 response in Mockoon
    cy.get( 'input[type="submit"]' ).click()

    // 1 error
    cy.get( '.notice-error' ).find( 'li' ).should('have.length', 1)
    cy.get( '.notice-error' ).find( 'li' ).eq( 0 ).contains( 'Please check and re-enter your BeyondWords API key and project ID. They appear to be invalid.' )

    // No Advanced settings
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'not.exist' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.exist' )

    // Valid creds
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    // No notices
    cy.get( '.notice-error' ).should( 'not.exist' )
    cy.get( '#beyondwords-player-location-notice' ).should( 'be.visible' )

    // Advanced settings are visible
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'be.checked' )
  } )

  it( 'can set the advanced plugin settings', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'be.checked' )

    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).check()
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).check()
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).uncheck()

    cy.get( 'input[type="submit"]' ).click()

    cy.contains( '#setting-error-settings_updated', 'Settings saved.' )

    cy.get( 'input[name="beyondwords_api_key"]' ).should( 'have.value', Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).should( 'have.value', Cypress.env( 'projectId' ) )

    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.be.checked' )

    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_api_key' ).should( 'exist' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'exist' )
    cy.get( '#beyondwords_preselect' ).should( 'exist' )
    cy.get( '#beyondwords_project_id' ).should( 'exist' )
    cy.get( '#beyondwords_version' ).should( 'exist' )
    cy.get( '#beyondwords_settings_updated' ).should( 'exist' )
  } )

  it( 'uses "Enabled" Player UI setting', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Enabled' )
    cy.get( 'input[type="submit"]' ).click()

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
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Headless' )
    cy.get( 'input[type="submit"]' ).click()

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
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()

    cy.get( 'select[name="beyondwords_player_ui"]' ).select( 'Disabled' )
    cy.get( 'input[type="submit"]' ).click()

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

  it( 'removes the plugin settings when uninstalled', () => {
    cy.savePluginSettings()

    // The plugin files will not be deleted. Only the uninstall procedure will be run.
    cy.uninstallPlugin( '--skip-delete speechkit' )

    cy.visit( '/wp-admin/options.php' ).wait( 500 )
    cy.get( '#beyondwords_api_key' ).should( 'not.exist' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.exist' )
    cy.get( '#beyondwords_preselect' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_version' ).should( 'not.exist' )
    cy.get( '#beyondwords_settings_updated' ).should( 'not.exist' )
  } )
} )
