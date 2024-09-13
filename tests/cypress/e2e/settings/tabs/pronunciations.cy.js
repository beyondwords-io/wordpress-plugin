context( 'Settings > Pronunciations',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'opens the "Pronunciations" tab', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=pronunciations' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Pronunciations' )
  } )
} )
