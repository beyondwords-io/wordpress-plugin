context( 'Settings',  () => {
  beforeEach( () => {
    cy.task( 'reset' )
    cy.login()
  } )

  it( 'shows the tab headings', () => {
    cy.saveMinimalPluginSettings()

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=credentials' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Credentials' )

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=content' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Content' )

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Voices' )

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=pronunciations' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Pronunciations' )

    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=advanced' )
    cy.get( '#beyondwords-plugin-settings > h2' ).eq( 0 ).should( 'have.text', 'Advanced' )
  } )

  it( 'has synced the voice settings on install', () => {
    cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

    // Enter only a valid API Key & Project ID
    cy.get( 'input#beyondwords_api_key' ).clear().type( Cypress.env( 'apiKey' ) )
    cy.get( 'input#beyondwords_project_id' ).clear().type( Cypress.env( 'projectId' ) )
    cy.get( 'input[type="submit"]' ).click().wait( 1000 )

    // The language and voices from the mock API response should be synced
    cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' )
    cy.get( 'select#beyondwords_project_language_id' ).find( ':selected' ).contains( 'Language 2' )
    cy.get( 'select#beyondwords_project_title_voice_id' ).find( ':selected' ).contains( 'Voice 2' )
    cy.get( 'select#beyondwords_project_body_voice_id' ).find( ':selected' ).contains( 'Voice 3' )
  } )

  it( 'removes the plugin settings when uninstalled', () => {
    cy.saveAllPluginSettings()

    cy.visit( '/wp-admin/options.php' )

    cy.get( '#beyondwords_api_key' )
    cy.get( '#beyondwords_body_voice_speaking_rate' )
    cy.get( '#beyondwords_include_title' )
    cy.get( '#beyondwords_player_call_to_action' )
    cy.get( '#beyondwords_player_clickable_sections' )
    cy.get( '#beyondwords_player_dark_theme' )
    // cy.get( '#beyondwords_player_highlight_sections' ) // @todo get this to appear
    cy.get( '#beyondwords_player_light_theme' )
    cy.get( '#beyondwords_player_skip_button_style' )
    cy.get( '#beyondwords_player_style' )
    cy.get( '#beyondwords_player_theme' )
    cy.get( '#beyondwords_player_video_theme' )
    cy.get( '#beyondwords_player_widget_position' )
    cy.get( '#beyondwords_player_widget_style' )
    // cy.get( '#beyondwords_prepend_excerpt' ) // @todo get this to appear
    // cy.get( '#beyondwords_preselect' ) // @todo get this to appear
    cy.get( '#beyondwords_project_body_voice_id' )
    cy.get( '#beyondwords_project_id' )
    cy.get( '#beyondwords_project_language_code' )
    cy.get( '#beyondwords_project_language_id' )
    cy.get( '#beyondwords_project_title_voice_id' )
    cy.get( '#beyondwords_title_voice_speaking_rate' )
    cy.get( '#beyondwords_valid_api_connection' )
    cy.get( '#beyondwords_version' )

    // The plugin files will not be deleted. Only the uninstall procedure will be run.
    cy.uninstallPlugin( '--skip-delete speechkit' )

    cy.visit( '/wp-admin/options.php' )

    cy.get( '#beyondwords_api_key' ).should( 'not.exist' )
    cy.get( '#beyondwords_body_voice_speaking_rate' ).should( 'not.exist' )
    cy.get( '#beyondwords_include_title' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_call_to_action' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_clickable_sections' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_dark_theme' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_highlight_sections' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_light_theme' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_skip_button_style' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_style' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_theme' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_video_theme' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_widget_position' ).should( 'not.exist' )
    cy.get( '#beyondwords_player_widget_style' ).should( 'not.exist' )
    cy.get( '#beyondwords_prepend_excerpt' ).should( 'not.exist' )
    cy.get( '#beyondwords_preselect' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_body_voice_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_language_code' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_language_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_project_title_voice_id' ).should( 'not.exist' )
    cy.get( '#beyondwords_title_voice_speaking_rate' ).should( 'not.exist' )
    cy.get( '#beyondwords_valid_api_connection' ).should( 'not.exist' )
    cy.get( '#beyondwords_version' ).should( 'not.exist' )
  } )
} )
