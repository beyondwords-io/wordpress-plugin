context( 'Site Health', () => {
  beforeEach( () => {
    cy.login()
  } )

  const settingsUpdatedRegex = /^admin@[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}[\+][\d]{4}$/i
  const semverRegex = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/

  it( 'has BeyondWords site health info', () => {
    cy.visit( '/wp-admin/site-health.php?tab=debug' ).wait( 500 )

    cy.get( 'button[aria-controls="health-check-accordion-block-beyondwords"]' ).click()

    cy.contains( 'Plugin version' )
      .parent( 'tr' )
      .within( () => {
        // all searches are automatically rooted to the found tr element
        cy.get( 'td' ).eq( 1 ).invoke( 'text' ).should( 'match', semverRegex )
      } )

    cy.contains( 'REST API URL' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', Cypress.env( 'apiUrl' ) )
      } )

    cy.contains( 'Communication with REST API' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).invoke( 'text' ).should( 'contain', 'BeyondWords API is reachable' )
      } )

    cy.contains( 'API Key' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).invoke( 'text' ).then( text => {
          const visibleChars = Cypress.env( 'apiKey' ).slice( -4 )
          expect( text ).to.be.a( 'string' ).and.match( new RegExp(`[X]{34}${visibleChars}`) );
        })

      } )

    cy.contains( 'Player version' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', '1' )
      } )

    cy.contains( 'Player UI' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'enabled' )
      } )

    cy.contains( 'Project ID' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', Cypress.env( 'projectId' ) )
      } )

    cy.contains( 'Process excerpts' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'No' )
      } )

    cy.contains( 'Preselect ‘Generate audio’' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', '{"post":"1","page":"1","cpt_active":"1"}' )
      } )

    cy.contains( 'Compatible post types' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'post, page, cpt_active, cpt_inactive' )
      } )

    cy.contains( 'Incompatible post types' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'cpt_unsupported' )
      } )

    cy.contains( 'Settings updated' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).invoke( 'text' ).should( 'match', settingsUpdatedRegex )
      } )

    cy.contains( 'Registered filters' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'None' )
      } )

    cy.contains( 'Registered deprecated filters' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'None' )
      } )

    cy.contains( 'BEYONDWORDS_AUTOREGENERATE' )
      .parent( 'tr' )
      .within( () => {
        cy.get( 'td' ).eq( 1 ).should( 'have.text', 'Undefined' )
      } )
  } )
} )
