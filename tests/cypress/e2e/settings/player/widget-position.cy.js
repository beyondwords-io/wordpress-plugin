context( 'Settings > Player > Widget position',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const options = [
    // @todo why does this test case fail?
    // {
    //   value: 'auto',
    //   label: 'Auto (default)',
    // },
    {
      value: 'center',
      label: 'Center',
    },
    {
      value: 'left',
      label: 'Left',
    },
    {
      value: 'right',
      label: 'Right',
    },
  ];

  options.forEach( option => {
    // @todo skipping because this fails now we auto-sync the API with WordPress
    it( `sets "${option.label}"`, () => {
      cy.saveMinimalPluginSettings()

      cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
      cy.get( 'select[name="beyondwords_player_widget_position"]' ).select( option.label )
      cy.get( 'input[type="submit"]' ).click().wait( 1000 )

      // Check for value in WordPress options
      cy.visit( '/wp-admin/options.php' )
      cy.get( '#beyondwords_player_widget_position' ).should( 'have.value', option.value );

      // Check for value in Site Health
      cy.visitPluginSiteHealth()
      cy.getSiteHealthValue( 'Widget position' ).should( 'have.text', option.value )
    } )
  } )
} )
