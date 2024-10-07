context( 'Settings > Player > Playback from segments',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  // @todo skipping because this fails now we auto-sync the API with WordPress
  it.skip( `sets "Playback from segments"`, () => {
    cy.saveMinimalPluginSettings()

    // Check
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( '#beyondwords_player_clickable_sections' ).check()
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // Check for value in WordPress options
    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_player_clickable_sections' ).should( 'have.value', '1' );

    // Check for value in Site Health
    cy.visitPluginSiteHealth()
    cy.getSiteHealthValue( 'Playback from segments' ).should( 'have.text', 'Yes' )

    // Uncheck
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
    cy.get( '#beyondwords_player_clickable_sections' ).uncheck()
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // Check for value in WordPress options
    cy.visit( '/wp-admin/options.php' )
    cy.get( '#beyondwords_player_clickable_sections' ).should( 'have.value', '' );

    // Check for value in Site Health
    cy.visitPluginSiteHealth()
    cy.getSiteHealthValue( 'Playback from segments' ).should( 'have.text', 'No' )
  } )
} )
