context( 'Settings > Credentials',  () => {
  beforeEach( () => {
    cy.task( 'reset' )
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

    // @todo fix missing error notices
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
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )
    cy.showsPluginSettingsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // Invalid creds
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( '401' ) // Project 401 triggers a 403 response in Mockoon
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )
    // @todo fix missing error notices
    // cy.showsInvalidApiCredsNotice()
    cy.showsOnlyCredentialsSettingsTab()

    // Valid API Key & Project ID
    cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )
    cy.get( '.notice.notice-info' ).should( 'not.exist' )
    cy.showsAllSettingsTabs()

    // No notices
    cy.get( '.notice-error' ).should( 'not.exist' )

    cy.contains( '#setting-error-settings_updated', 'Settings saved.' )

    cy.get( 'input[name="beyondwords_api_key"]' ).should( 'have.value', Cypress.env( 'apiKey' ) )
    cy.get( 'input[name="beyondwords_project_id"]' ).should( 'have.value', Cypress.env( 'projectId' ) )

    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_api_key' ).should( 'exist' )
    cy.get( '#beyondwords_project_id' ).should( 'exist' )
    cy.get( '#beyondwords_valid_api_connection' )
    cy.get( '#beyondwords_version' ).should( 'exist' )
  } )
} )
