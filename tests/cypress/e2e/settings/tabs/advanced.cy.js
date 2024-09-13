context( 'Settings > Advanced',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'opens the "Advanced" tab', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=advanced' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Advanced' )
  } )
} )
