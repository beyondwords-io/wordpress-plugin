context( 'Block Editor: Insert BeyondWords Player', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const postTypes = require( '../../../../tests/fixtures/post-types.json' )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    /**
     * *****************************************************
     * Skipping because we see this error in GitHub Actions:
     * *****************************************************
     *
     * We detected that the Chrome Renderer process just crashed.
     *
     * We have failed the current spec but will continue running the next spec.
     *
     * This can happen for a number of different reasons.
     *
     * If you're running lots of tests on a memory intense application.
     * - Try increasing the CPU/memory on the machine you're running on.
     * - Try enabling experimentalMemoryManagement in your config file.
     * - Try lowering numTestsKeptInMemory in your config file during 'cypress open'.
     *
     * You can learn more here:
     *
     * https://on.cypress.io/renderer-process-crashed
     */
    // @todo test fails because '.block-editor-default-block-appender button' is no longer available
    it.skip( `can add a player block into a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostTitle( `I can add a player block into a ${postType.name}` )

      cy.addParagraphBlock( 'Before.' )

      // Click title to lose focus
      cy.clickTitleBlock();

      // Add player block
      cy.get( '.block-editor-default-block-appender button' ).click().wait( 1000 )
      cy.get( '.block-editor-inserter__quick-inserter input' ).type( 'bey' ).wait( 1000 )
      cy.get( '.block-editor-block-types-list__item-title' ).contains( 'BeyondWords' ).click().wait( 1000 )

      cy.addParagraphBlock( 'After.' )

      // Count 1x player in editor iframe
      cy.get( 'div[data-beyondwords-player="true"][contenteditable="false"]' ).should( 'have.length', 1 )

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // cy.getEnqueuedPlayerScriptTag().should( 'exist' )
      cy.hasPlayerInstance()
    } )

    // @todo test fails because '.block-editor-default-block-appender button' is no longer available
    it.skip( `can add a shortcode into a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      cy.checkGenerateAudio( postType )

      cy.setPostTitle( `I can add a shortcode into a ${postType.name}` )

      cy.addParagraphBlock( 'Before.' )

      // Click title to lose focus
      cy.clickTitleBlock();

      // Add shortcode
      cy.get( '.block-editor-default-block-appender button' ).click().wait( 1000 )
      cy.get( '.block-editor-inserter__quick-inserter input' ).type( 'sho' ).wait( 1000 )
      cy.get( '.block-editor-block-types-list__item-title' ).contains( 'Shortcode' ).click().wait( 1000 )
      cy.get( 'body' ).type( '[beyondwords_player]' )

      cy.addParagraphBlock( 'After.' )

      cy.publishWithConfirmation( true )

      // "View post"
      cy.viewPostViaSnackbar()

      // cy.getEnqueuedPlayerScriptTag().should( 'exist' )
      cy.hasPlayerInstance()
    } )
  } )
} )
