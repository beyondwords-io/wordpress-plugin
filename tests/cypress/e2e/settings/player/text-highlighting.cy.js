context( 'Settings > Player > Text highlighting',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( `sets "Text highlighting"`, () => {
    cy.saveMinimalPluginSettings()

    // Check
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( '#beyondwords_player_highlight_sections' ).check()
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // Check for value in WordPress options
    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_player_highlight_sections' ).should( 'have.value', '1' );

    // Check for value in Site Health
    cy.visitPluginSiteHealth()
    cy.getSiteHealthValue( 'Text highlighting' ).should( 'have.text', 'Yes' )

    // Uncheck
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( '#beyondwords_player_highlight_sections' ).uncheck()
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // Check for value in WordPress options
    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_player_highlight_sections' ).should( 'have.value', '' );

    // Check for value in Site Health
    cy.visitPluginSiteHealth()
    cy.getSiteHealthValue( 'Text highlighting' ).should( 'have.text', 'No' )
  } )
} )
