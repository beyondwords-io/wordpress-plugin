context( 'Settings > Pronunciations',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'has the "Manage pronunciations" button', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=pronunciations' )
    cy.get( '.button.button-primary' ).eq( 0 ).then( button => {
      // Button text
      cy.wrap( button )
        .invoke( 'text' )
        .then( text => text.trim() )
        .should( 'equal', 'Manage pronunciations' );
      // Button link
      cy.wrap( button )
        .should( 'have.attr', 'href', `https://dash.beyondwords.io/dashboard/project/${ Cypress.env( 'projectId' ) }/settings?tab=rules` )
    } )
  } )
} )
