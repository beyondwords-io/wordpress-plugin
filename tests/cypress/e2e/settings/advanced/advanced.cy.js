context( 'Settings > Advanced',  () => {
  before( () => {
    cy.task( 'reset' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  it( 'Syncs the settings from the Dashboard to WordPress', () => {
    cy.saveAllPluginSettings()

    cy.visit( '/wp-admin/options.php' )

    // Clear existing plugin data.
    cy.get( '#beyondwords_player_call_to_action' ).clear()
    cy.get( '#beyondwords_player_clickable_sections' ).clear()
    cy.get( '#beyondwords_player_skip_button_style' ).clear()
    cy.get( '#beyondwords_player_style' ).clear()
    cy.get( '#beyondwords_player_theme' ).clear()
    cy.get( '#beyondwords_player_widget_position' ).clear()
    cy.get( '#beyondwords_player_widget_style' ).clear()
    cy.get( '#beyondwords_project_body_voice_id' ).clear()
    cy.get( '#beyondwords_project_body_voice_speaking_rate' ).clear()
    cy.get( '#beyondwords_project_language_code' ).clear()
    cy.get( '#beyondwords_project_language_id' ).clear()
    cy.get( '#beyondwords_project_title_enabled' ).clear()
    cy.get( '#beyondwords_project_title_voice_id' ).clear()
    cy.get( '#beyondwords_project_title_voice_speaking_rate' ).clear()

    // @todo themes cannot be cleared using .clear() because they are serialized data
    // cy.get( '#beyondwords_player_theme_dark' ).clear()
    // cy.get( '#beyondwords_player_theme_light' ).clear()
    // cy.get( '#beyondwords_player_theme_video' ).clear()

    cy.get( 'form#all-options' ).submit()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=advanced' )

    cy.get( 'button[name="beyondwords_sync"]' ).click()
    cy.get( '.notice-success' )

    cy.visit( '/wp-admin/options.php' )

    // These should be repopulated using the Mock API response data.
    cy.get( '#beyondwords_player_call_to_action' ).should( 'have.value', 'Listen to this article' )
    cy.get( '#beyondwords_player_clickable_sections' ).should( 'have.value', '1' )
    cy.get( '#beyondwords_player_skip_button_style' ).should( 'have.value', 'auto' )
    cy.get( '#beyondwords_player_style' ).should( 'have.value', 'standard' )
    cy.get( '#beyondwords_player_theme' ).should( 'have.value', 'light' )
    cy.get( '#beyondwords_player_widget_position' ).should( 'have.value', 'auto' )
    cy.get( '#beyondwords_player_widget_style' ).should( 'have.value', 'standard' )
    cy.get( '#beyondwords_project_body_voice_id' ).should( 'have.value', '3' )
    cy.get( '#beyondwords_project_body_voice_speaking_rate' ).should( 'have.value', '110' )
    cy.get( '#beyondwords_project_language_code' ).should( 'have.value', 'en_GB' )
    cy.get( '#beyondwords_project_language_id' ).should( 'have.value', '2' )
    cy.get( '#beyondwords_project_title_enabled' ).should( 'have.value', '1' )
    cy.get( '#beyondwords_project_title_voice_id' ).should( 'have.value', '2' )
    cy.get( '#beyondwords_project_title_voice_speaking_rate' ).should( 'have.value', '90' )

    // @todo themes cannot be tested using this method because they are serialized data
    // cy.get( '#beyondwords_player_theme_dark' ).should( 'have.value', '' )
    // cy.get( '#beyondwords_player_theme_light' ).should( 'have.value', '' )
    // cy.get( '#beyondwords_player_theme_video' ).should( 'have.value', '' )
  } )
} )
