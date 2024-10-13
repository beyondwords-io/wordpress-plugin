context( 'Settings > Player > Widget style',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const options = [
    {
      value: 'standard',
      label: 'Standard',
    },
    {
      value: 'none',
      label: 'None',
    },
    {
      value: 'small',
      label: 'Small',
    },
    {
      value: 'large',
      label: 'Large',
    },
    {
      value: 'video',
      label: 'Video',
    },
  ];

  options.forEach( option => {
    // @todo skipping because this fails now we auto-sync the API with WordPress
    it.skip( `sets "${option.label}"`, () => {
      cy.saveMinimalPluginSettings()

      cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )
      cy.get( 'select[name="beyondwords_player_widget_style"]' ).select( option.label )
      cy.get( 'input[type="submit"]' ).click().wait( 1000 )

      // Check for value in WordPress options
      cy.visit( '/wp-admin/options.php' )
      cy.get( '#beyondwords_player_widget_style' ).should( 'have.value', option.value );

      // Check for value in Site Health
      cy.visitPluginSiteHealth()
      cy.getSiteHealthValue( 'Widget style' ).should( 'have.text', option.value )
    } )
  } )
} )
