context( 'Settings tests',  () => {
  before( () => {
    cy.task( 'reset' )
    // cy.login()
    // cy.savePluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'prompts for API credentials and hides other settings tabs until they are valid', () => {
    cy.visit( '/wp-admin' )

    cy.showsPluginSettingsNotice()
    cy.getPluginSettingsNoticeLink().click()
    cy.showsOnlyCredentialsSettingsTab()

    // Empty API Key & Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).should( 'have.value', '' )
    cy.get( 'input[name="beyondwords_project_id"]' ).should( 'have.value', '' )
    cy.get( 'input[type="submit"]' ).click()
    cy.showsPluginSettingsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // @todo fix missing 2 errors
    // cy.get( '.notice-error' ).find( 'li' ).should('have.length', 2)
    // cy.get( '.notice-error' ).find( 'li' ).eq( 0 ).contains( 'Please enter the BeyondWords API key. This can be found in your project settings.' )
    // cy.get( '.notice-error' ).find( 'li' ).eq( 1 ).contains( 'Please enter your BeyondWords project ID. This can be found in your project settings.' )

    // Empty API Key
    cy.get( 'input[name="beyondwords_api_key"]' ).clear()
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()
    cy.showsPluginSettingsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // Empty Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear()
    cy.get( 'input[type="submit"]' ).click()
    cy.showsPluginSettingsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // Invalid creds
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( '401' ) // Project 401 triggers a 403 response in Mockoon
    cy.get( 'input[type="submit"]' ).click()
    cy.showsInvalidApiCredsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // Valid API Key & Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click()
    cy.get( '.notice.notice-info' ).should( 'not.exist' )
    cy.showsAllSettingsTabs()

    // No notices
    cy.get( '.notice-error' ).should( 'not.exist' )

    cy.contains( '#setting-error-settings_updated', 'Settings saved.' )

    cy.get( 'input[name="beyondwords_api_key"]' ).should( 'have.value', Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).should( 'have.value', Cypress.env( 'projectId' ) )
  } )

  // @todo finish tests for syncing settings on install
  it.only( 'has synced the settings on install', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    // Valid API Key & Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click().wait( 500 )

    // Voices tab
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' )
    cy.get( 'select[name="beyondwords_project_language_id"]' ).find( ':selected' ).contains( 'Language 2' )
    cy.get( 'select[name="beyondwords_project_title_voice_id"]' ).find( ':selected' ).contains( 'Voice 2' )
    cy.get( 'select[name="beyondwords_project_body_voice_id"]' ).find( ':selected' ).contains( 'Voice 3' )
  } )

  it( 'can set the Content plugin settings', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=content' )
    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'not.be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'be.checked' )

    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).check()
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).check()
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).uncheck()

    cy.get( 'input[type="submit"]' ).click()

    cy.get( 'input[name="beyondwords_prepend_excerpt"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.be.checked' )

    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_api_key' ).should( 'exist' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'exist' )
    cy.get( '#beyondwords_preselect' ).should( 'exist' )
    cy.get( '#beyondwords_project_id' ).should( 'exist' )
    cy.get( '#beyondwords_version' ).should( 'exist' )
    // cy.get( '#beyondwords_settings_updated' ).should( 'exist' )
  } )

  it( 'uses "Enabled" Player UI setting', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
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
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
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
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
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
    cy.visit( '/wp-admin/options.php' ).wait( 500 )
    cy.get( '#beyondwords_api_key' )
    cy.get( '#beyondwords_prepend_excerpt' )
    cy.get( '#beyondwords_preselect' )
    cy.get( '#beyondwords_project_id' )
    cy.get( '#beyondwords_version' )

    // The plugin files will not be deleted. Only the uninstall procedure will be run.
    cy.uninstallPlugin( '--skip-delete speechkit' )

    cy.visit( '/wp-admin/options.php' ).wait( 500 )
    cy.get( '#beyondwords_api_key' ).should( 'not.exist' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.exist' )
    cy.get( '#beyondwords_preselect' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_version' ).should( 'not.exist' )
  } )
} )
