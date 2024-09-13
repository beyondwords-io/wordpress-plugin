context( 'Settings > Player > Player colors',  () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveMinimalPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const light_theme = {
    background_color: '#100',
    icon_color: '#200',
    text_color: '#300',
    highlight_color: '#400',
  };

  const dark_theme = {
    background_color: '#500',
    icon_color: '#600',
    text_color: '#700',
    highlight_color: '#800',
  };

  const video_theme = {
    background_color: '#900',
    icon_color: '#a00',
    text_color: '#b00',
  };

  it( `sets Player colors"`, () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' )

    cy.get( 'input[name="beyondwords_player_light_theme[background_color]"]' ).clear().type( light_theme.background_color )
    cy.get( 'input[name="beyondwords_player_light_theme[icon_color]"]' ).clear().type( light_theme.icon_color )
    cy.get( 'input[name="beyondwords_player_light_theme[text_color]"]' ).clear().type( light_theme.text_color )

    cy.get( 'input[name="beyondwords_player_dark_theme[background_color]"]' ).clear().type( dark_theme.background_color )
    cy.get( 'input[name="beyondwords_player_dark_theme[icon_color]"]' ).clear().type( dark_theme.icon_color )
    cy.get( 'input[name="beyondwords_player_dark_theme[text_color]"]' ).clear().type( dark_theme.text_color )

    cy.get( 'input[name="beyondwords_player_video_theme[background_color]"]' ).clear().type( video_theme.background_color )
    cy.get( 'input[name="beyondwords_player_video_theme[icon_color]"]' ).clear().type( video_theme.icon_color )
    cy.get( 'input[name="beyondwords_player_video_theme[text_color]"]' ).clear().type( video_theme.text_color )

    cy.get( 'input[name="beyondwords_player_light_theme[highlight_color]"]' ).clear().type( light_theme.highlight_color )
    cy.get( 'input[name="beyondwords_player_dark_theme[highlight_color]"]' ).clear().type( dark_theme.highlight_color )

    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // Only check for value in Site Health
    cy.visitPluginSiteHealth()

    cy.getSiteHealthValue( 'Light theme' ).then( val => {
      const data = JSON.parse( val.text() )
      expect( data ).to.deep.equal( light_theme )
    })
      
    cy.getSiteHealthValue( 'Dark theme' ).then( val => {
      const data = JSON.parse( val.text() )
      expect( data ).to.deep.equal( dark_theme )
    })
      
    cy.getSiteHealthValue( 'Video theme' ).then( val => {
      const data = JSON.parse( val.text() )
      expect( data ).to.deep.equal( video_theme )
    })
  } )
} )
