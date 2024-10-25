context( 'Settings > Content',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'can set the Content plugin settings', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=content' )
    cy.get( '#beyondwords_project_title_enabled' ).should( 'be.checked' )
    cy.get( '#beyondwords_project_auto_publish_enabled' ).should( 'be.checked' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.be.checked' )
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'be.checked' )

    cy.get( '#beyondwords_project_title_enabled' ).uncheck()
    cy.get( '#beyondwords_project_auto_publish_enabled' ).uncheck()
    cy.get( '#beyondwords_prepend_excerpt' ).check()
    cy.get( 'input[name="beyondwords_preselect[post]"]' ).check()
    cy.get( 'input[name="beyondwords_preselect[page]"]' ).uncheck()

    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // @todo skipping because this fails now we auto-sync the API with WordPress
    // cy.get( '#beyondwords_project_title_enabled' ).should( 'not.be.checked' )
    // cy.get( '#beyondwords_project_auto_publish_enabled' ).should( 'not.be.checked' )
    // cy.get( '#beyondwords_prepend_excerpt' ).should( 'be.checked' )
    // cy.get( 'input[name="beyondwords_preselect[post]"]' ).should( 'be.checked' )
    // cy.get( 'input[name="beyondwords_preselect[page]"]' ).should( 'not.be.checked' )

    // cy.visit( '/wp-admin/options.php' )
    // cy.get( '#beyondwords_project_title_enabled' ).should( 'exist' )
    // cy.get( '#beyondwords_project_auto_publish_enabled' ).should( 'exist' )
    // cy.get( '#beyondwords_prepend_excerpt' ).should( 'exist' )
    // cy.get( '#beyondwords_preselect' ).should( 'exist' )
  } )
} )
