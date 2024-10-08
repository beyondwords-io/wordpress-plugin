describe( 'Plugins: Elementor', () => {
  beforeEach( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
    cy.activatePlugin( 'elementor' )
  } )

  afterEach( () => {
    cy.deactivatePlugin( 'elementor' )
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test "Post" post type in Elementor
  postTypes.filter( x => [ 'post' ].includes( x.slug ) ).forEach( postType => {
    it.skip( `can "Generate Audio" for a new ${postType.name} in Elementor`, () => {
      cy.visit( '/wp-admin/post-new.php' )

      cy.closeWelcomeToBlockEditorTips()

      // "Edit with Elementor"
      cy.get( '.elementor-switch-mode-off' ).click()

      // I wait for Elementor loader to disappear
      cy.get('.elementor-loading-title').should('not.be.visible');
      cy.get('#elementor-loading').should('not.be.visible');

      // Close Announcements modals
      cy.closeElementorAnnouncements()

      // Settings > BeyondWords
      cy.get( '.elementor-panel-footer-tool[data-tooltip="Settings"]' ).click()
      cy.get( '.elementor-tab-control-beyondwords-tab' ).click()

      // See "Generate audio" label
      cy.getElementorGenerateAudioLabel().should( 'contain', 'Generate audio' )

      // I don't see "Display Player" label
      cy.getElementorDisplayPlayerLabel().should( 'not.be.visible' )

      // Check "Generate audio" switch
      cy.get( '.elementor-control-control_beyondwords_generate_audio .elementor-switch' ).click()

      // See "Display Player" label
      cy.getElementorDisplayPlayerLabel().should( 'be.visible' )
      cy.getElementorDisplayPlayerLabel().should( 'contain', 'Display player' )

      // Publish
      cy.get( 'button#elementor-panel-saver-button-publish' ).click()

      // See Elementor notification
      cy.get( '.dialog-message' ).should( 'exist' )
      cy.get( '.dialog-message' ).should( 'contain', 'Hurray! Your Post is live.' )

      // (AGAIN) wait for Elementor loader to disappear
      cy.get('.elementor-loading-title').should('not.be.visible');
      cy.get('#elementor-loading').should('not.be.visible');

      // Don't see "Generate audio" label
      cy.getElementorGenerateAudioLabel().should( 'not.be.visible' )

      // See "Display Player" label
      cy.getElementorDisplayPlayerLabel().should( 'contain', 'Display player' )

      // Elementor currently requires a page reload to display player
      cy.reload()
      cy.get( '.elementor-panel-footer-tool[data-tooltip="Settings"]' ).click()
      cy.get( '.elementor-tab-control-beyondwords-tab' ).click()

      // Admin player should exist
      cy.getElementorAdminPlayer().should( 'exist' )

      // View page
      cy.get('#elementor-panel-header-menu-button').click()
      cy.get('.elementor-panel-menu-item-view-page').click()

      cy.getFrontendPlayer().should( 'exist' )
    } )

    it.skip( `shows a player in Elementor for a ${postType.name} with existing audio`, () => {
      cy.createPostWithAudio( `An Elementor ${postType.name} with existing audio` )

      // Admin should have latest player
      cy.getAdminPlayer().should( 'exist' )

      // "Edit with Elementor"
      cy.get( '.elementor-switch-mode-off' ).click()

      cy.get('.elementor-loading-title').should('not.be.visible');
      cy.get('#elementor-loading').should('not.be.visible');

      // Close Announcements modals
      cy.closeElementorAnnouncements()

      // Settings > BeyondWords
      cy.get( '.elementor-panel-footer-tool[data-tooltip="Settings"]' ).click()
      cy.get( '.elementor-tab-control-beyondwords-tab' ).click()

      // Don't see "Generate audio" label
      cy.getElementorGenerateAudioLabel().should( 'not.be.visible' )

      // See "Display Player" label
      cy.getElementorDisplayPlayerLabel().should( 'contain', 'Display player' )

      // Admin player should exist
      cy.getElementorAdminPlayer().should( 'exist' )

      // View page
      cy.get('#elementor-panel-header-menu-button').click()
      cy.get('.elementor-panel-menu-item-view-page').click()

      cy.getFrontendPlayer().should( 'exist' )
    } )
  } )
} )
