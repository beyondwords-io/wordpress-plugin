context( 'Settings > Advanced',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it.skip( 'Syncs the settings from Dashboard to WordPress', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=advanced' )

    // @todo complete writing test
  } )
} )
