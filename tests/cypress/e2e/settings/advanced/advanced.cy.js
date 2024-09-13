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

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=pronunciations' )
    cy.get( '.button.button-primary' ).eq(0).should( 'have.text', 'Manage pronunciations' )
    cy.get( '.button.button-primary' ).eq(0).should( 'have.attr', 'href', `https://dash.beyondwords.io/dashboard/project/${ Cypress.env( 'projectId' ) }/settings?tab=rules` )
  } )
} )
