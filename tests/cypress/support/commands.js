// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add( 'login', (email, password) => { ... } )
//
//
// -- This is a child command --
// Cypress.Commands.add( 'drag', { prevSubject: 'element'}, (subject, options) => { ... } )
//
//
// -- This is a dual command --
// Cypress.Commands.add( 'dismiss', { prevSubject: 'optional'}, (subject, options) => { ... } )
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite( 'visit', (originalFn, url, options) => { ... } )

const postTypes = require( '../../fixtures/post-types.json' )

// Set keystroke delay
Cypress.Commands.overwrite('type', (originalFn, subject, text, options = {}) => {
  options.delay = 15
  return originalFn(subject, text, options)
})

Cypress.Commands.add( 'getTinyMceIframeBody', () => {
  // get the iframe > document > body
  // and retry until the body element is not empty
  return cy
    .get( '#content_ifr' )
    .its( '0.contentDocument.body' )
    .should( 'not.be.empty' )
    /*
     * Wraps "body" DOM element to allow
     * chaining more Cypress commands, like ".find(...)"
     * https://on.cypress.io/wrap
     */
    .then( cy.wrap )
  } )

Cypress.Commands.add( 'login', () => {
  const username = Cypress.env( 'wpUsername' )
  const password = Cypress.env( 'wpPassword' )
  const baseUrl  = Cypress.config().baseUrl

  cy.visit( '/wp-login.php' ).wait( 500 )
  cy.get( '#user_login' ).clear().type( username )
  cy.get( '#user_pass' ).clear().type( `${password}{enter}` ).wait( 500 )
  cy.url().should( 'eq', `${baseUrl}/wp-admin/` )
} )

Cypress.Commands.add( 'savePluginSettings', () => {
  cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

  cy.get( 'input[name="beyondwords_api_key"]' ).clear().type( Cypress.env( 'apiKey' ) )
  cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( Cypress.env( 'projectId' ) )
  cy.get( '#submit' ).click().wait( 500 )

  cy.get( '.beyondwords-setting--preselect--post-type input[type="checkbox"]' ).uncheck()
  cy.get( 'input[name="beyondwords_preselect[post]"]' ).check()
  cy.get( 'input[name="beyondwords_preselect[page]"]' ).check()
  cy.get( 'input[name="beyondwords_preselect[cpt_active]"]' ).check()
  cy.get( 'input[name="beyondwords_preselect[cpt_inactive]"]' ).should( 'not.be.checked' )
  cy.get( 'input[name="beyondwords_preselect[cpt_unsupported]"]' ).should( 'not.exist' )

  cy.get( '#submit' ).click().wait( 500 )
} )

Cypress.Commands.add( 'setLanguagesInPluginSettings', () => {
  cy.visit( '/wp-admin/options-general.php?page=beyondwords' )

  cy.get('#beyondwords_languages-ts-control').click().wait( 1000 )
  cy.contains('#beyondwords_languages-ts-dropdown .option', 'Language 1' ).click().wait( 1000 )
  cy.contains('#beyondwords_languages-ts-dropdown .option', 'Language 2' ).click().wait( 1000 )

  cy.get( '#submit' ).click().wait( 500 )
} )

Cypress.Commands.add( 'setPlayerStyleInPluginSettings', ( value ) => {
  cy.visit( '/wp-admin/options-general.php?page=beyondwords' ).wait( 500 )
  cy.get( 'select[name="beyondwords_player_style"]' ).select( value )
  cy.get( '#submit' ).click().wait( 500 )
} )

Cypress.Commands.add( 'activatePlugin', ( ...args ) => {
  args.flat().forEach( plugin => {
    cy.task( 'wp:plugin:activate', plugin )
  } )
} )

/**
 * Deactivate one or more plugins.
 */
Cypress.Commands.add( 'deactivatePlugin', ( ...args ) => {
  args.flat().forEach( plugin => {
    cy.task( 'wp:plugin:deactivate', plugin )
  } )
} )

/**
 * Uninstall one or more plugins.
 */
Cypress.Commands.add( 'uninstallPlugin', ( ...args ) => {
  args.flat().forEach( plugin => {
    cy.task( 'wp:plugin:uninstall', plugin )
  } )
} )

/**
 * "Save as pending" for block editor.
 */
Cypress.Commands.add( 'saveAsPending', () => {
  cy.contains( 'button', 'Save as pending' ).click().wait( 1000 )
} )

/**
 * "Save as pending" for classic editor.
 */
Cypress.Commands.add( 'classicSaveAsPending', () => {
  // Show Status select box
  cy.get( '.misc-pub-post-status a.edit-post-status' ).click()

  // Select "Pending Review"
  cy.get( '#post_status' ).select( 'Pending Review' )

  // Click "OK"
  cy.get( 'a.save-post-status' ).click().wait( 500 )

  // Click "Save as Pending" button
  cy.get( 'input[value="Save as Pending"]' ).click()

  // Wait for success message
  cy.get( 'div#message.notice-success' )
} )

Cypress.Commands.add( 'closeWelcomeToBlockEditorTips', () => {
  // Waiting 1500ms here isn't ideal, but this is the most reliable method we have found
  cy.wait( 1500 )
  cy.window().then( win => {
    win.eval( 'wp.data.select( "core/edit-post" ).isFeatureActive( "welcomeGuide" ) && wp.data.dispatch( "core/edit-post" ).toggleFeature( "welcomeGuide" );' )
  } )
} )

Cypress.Commands.add( 'createBlockProgramatically', ( name, params = {} ) => {
  cy.window().then( win => {
    win.eval( `var block = wp.blocks.createBlock( '${name}', ${JSON.stringify( params )} );` )
    win.eval( `wp.data.dispatch( 'core/block-editor' ).insertBlocks( block );` )
  } )
  cy.wait( 1000 );
} )

Cypress.Commands.add( 'checkGenerateAudio', ( postType ) => {
  if ( ! postType ) {
    postType = postTypes[0]
  }

  cy.getBlockEditorCheckbox( 'Generate audio' )
    .should( postType.preselect ? 'be.checked' : 'not.be.checked' )

  if ( ! postType.preselect ) {
    cy.getLabel( 'Generate audio' ).click()
  }

  cy.getBlockEditorCheckbox( 'Generate audio' )
    .should( 'be.checked' )
} )

Cypress.Commands.add( 'uncheckGenerateAudio', ( postType ) => {
  if ( ! postType ) {
    postType = postTypes[0]
  }

  cy.getBlockEditorCheckbox( 'Generate audio' )
    .should( postType.preselect ? 'be.checked' : 'not.be.checked' )

  if ( postType.preselect ) {
    cy.getLabel( 'Generate audio' ).click()
  }

  cy.getBlockEditorCheckbox( 'Generate audio' )
    .should( 'not.be.checked' )
} )

Cypress.Commands.add( 'createPostWithAudio', ( title, postType ) => {
  if ( ! postType ) {
    postType = postTypes[0]
  }

  cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` )
  cy.closeWelcomeToBlockEditorTips()
  cy.openBeyondwordsEditorPanel()

  cy.get( 'h1[contenteditable="true"]' )
    .clear()
    .type( title )

  cy.checkGenerateAudio( postType )

  cy.publishWithConfirmation( true )

  cy.getAdminPlayer().should( 'exist' )
} )

Cypress.Commands.add( 'createPostWithoutAudio', ( title, postType ) => {
  if ( ! postType ) {
    postType = postTypes[0]
  }

  cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` )
  cy.closeWelcomeToBlockEditorTips()
  cy.openBeyondwordsEditorPanel()

  cy.get( 'h1[contenteditable="true"]' )
    .clear()
    .type( title )

  cy.uncheckGenerateAudio( postType )

  cy.publishWithConfirmation( true )

  cy.getBlockEditorCheckbox( 'Generate audio' ).should( 'exist' )
  cy.getAdminPlayer().should( 'not.exist' )
} )

/**
 * Set post title for block editor.
 */
Cypress.Commands.add( 'setPostTitle', ( title ) => {
  cy.get( 'h1[contenteditable="true"]' )
    .clear()
    .type( title )
} )

/**
 * Set post title for classic editor.
 */
Cypress.Commands.add( 'classicSetPostTitle', ( title ) => {
  cy.get( 'input#title' )
    .clear()
    .type( title )
} )

/**
 * Add (append) a new paragraph block with the specified text.
 */
Cypress.Commands.add( 'addParagraphBlock', ( text ) => {
  // Click post title to make sure "+ block" shows
  cy.clickTitleBlock();

  // Click "+ block" button
  cy.get('p.block-editor-default-block-appender__content').click().wait( 500 );
  cy.get( 'body' ).type( `${text}` )
} )

/**
 * Add (append) a new paragraph block with the specified text.
 */
Cypress.Commands.add( 'clickTitleBlock', () => {
  cy.get( '.editor-post-title' ).click();
} )

Cypress.Commands.add( 'openBeyondwordsEditorPanel', () => {
  cy.get( '.beyondwords-sidebar' ).scrollIntoView().should('be.visible')
  cy.get( '.beyondwords-sidebar' ).then( ( $el ) => {
    if ( $el.is( '.is-opened' ) ) {
      cy.log( 'The BeyondWords editor panel is already open' )
    } else {
      cy.log( 'Opening the BeyondWords editor panel' )
      cy.wrap( $el ).click().wait( 500 )
    }
  } )
} )

Cypress.Commands.add( 'getBlockEditorCheckbox', ( text, ...args ) => {
  return cy.get( 'label', ...args )
    .contains( text )
    .closest( '.components-checkbox-control' )
    .find( 'input[type="checkbox"]' )
    .then( $el => cy.wrap( $el ) )
} )

Cypress.Commands.add( 'getBlockEditorSelect', ( text, ...args ) => {
  return cy.get( 'label', ...args )
    .contains( text )
    .closest( '.components-select-control' )
    .find( 'select' )
    .then( $el => cy.wrap( $el ) )
} )

Cypress.Commands.add( 'assertDisplayPlayerIs', ( displayPlayer ) => {
  cy.contains( 'label', 'Display player' )
    .closest( 'div' )
    .find( 'svg' ) // Block editor uses a funky SVG instead of a checkbox
    .should( displayPlayer ? 'exist' : 'not.exist' )
} )

Cypress.Commands.add( 'publishWithConfirmation', ( generateAudio ) => {
  // "Publish" in Prepublish panel
  cy.contains( '.edit-post-header button', 'Publish' ).click().wait( 1000 )

  // Confirm "Publish" in the Prepublish panel
  cy.get( '.editor-post-publish-panel__header-publish-button > .components-button' ).click()

  // Close Prepublish panel
  cy.get( 'button[aria-label="Close panel"]' ).click()
} )

// "Publish" in Prepublish panel
Cypress.Commands.add( 'updatePost', () => {
  cy.contains( '.edit-post-header button', 'Update' ).click().wait( 1000 )
} )

Cypress.Commands.add( 'viewPostViaSnackbar', () => {
  cy.get( '.components-snackbar' ).find( 'a' ).click()
  // Use a longer wait() time here to allow the page and any players to load
  cy.wait( 1500 )
} )

// Get label element from text
Cypress.Commands.add( 'getLabel', ( text,  ...args ) => {
  return cy.get( 'label',  ...args ).contains( text )
} )

// Get admin audio player element
Cypress.Commands.add( 'getAdminPlayer', ( ...args ) => {
  cy.openBeyondwordsEditorPanel();

  return cy.get( '.beyondwords-player .user-interface',  ...args )
} )

// Get frontend audio player element (standard)
Cypress.Commands.add( 'getFrontendPlayer', ( ...args ) => {
  return cy.get( '.beyondwords-player .user-interface',  ...args )
} )

// Get frontend small player element
Cypress.Commands.add( 'getFrontendSmallPlayer', ( ...args ) => {
  return cy.get( '.beyondwords-player .user-interface.small',  ...args )
} )

// Get frontend large player element
Cypress.Commands.add( 'getFrontendLargePlayer', ( ...args ) => {
  return cy.get( '.beyondwords-player .user-interface.large',  ...args )
} )

// Get frontend video player element
Cypress.Commands.add( 'getFrontendVideoPlayer', ( ...args ) => {
  return cy.get( '.beyondwords-player .user-interface.video',  ...args )
} )

// Get frontend audio player element (legacy player)
Cypress.Commands.add( 'getLegacyFrontendPlayer', ( ...args ) => {
  return cy.get( '.sk-app-container',  ...args )
} )

// Get Elementor admin audio player element
Cypress.Commands.add( 'getElementorAdminPlayer', ( ...args ) => {
  return cy.get( '.sk-app-container',  ...args )
} )

// Get Elementor "Generate Audio" label
Cypress.Commands.add( 'getElementorGenerateAudioLabel', ( ...args ) => {
  return cy.get('.elementor-control-control_beyondwords_generate_audio > .elementor-control-content > .elementor-control-field > .elementor-control-title',  ...args )
} )

// Get Elementor "Display Player" label
Cypress.Commands.add( 'getElementorDisplayPlayerLabel', ( ...args ) => {
  return cy.get('.elementor-control-control_beyondwords_display_player > .elementor-control-content > .elementor-control-field > .elementor-control-title',  ...args )
} )

Cypress.Commands.add( 'closeElementorAnnouncements', () => {
  cy.get( 'body' ).then( $body => {
    // Give the announcements modal some time to appear
    cy.wait( 2000 )

    if ( $body.find( '.announcements-container .close-button' ).length ) {
      // Then give the announcements modal some time to disappear
      cy.get( '.announcements-container .close-button' ).click( { force: true } ).wait( 2000 )
    }
  } )
} )

/**
 * Simulates a paste event.
 * Modified from https://gist.github.com/nickytonline/bcdef8ef00211b0faf7c7c0e7777aaf6
 *
 * @param subject A jQuery context representing a DOM element.
 * @param pasteOptions Set of options for a simulated paste event.
 * @param pasteOptions.pastePayload Simulated data that is on the clipboard.
 * @param pasteOptions.pasteFormat The format of the simulated paste payload. Default value is 'text'.
 *
 * @returns The subject parameter.
 *
 * @example
 * cy.get('body').paste({
 *   pasteType: 'application/json',
 *   pastePayload: {hello: 'yolo'},
 * });
*/
Cypress.Commands.add(
  'paste',
  { prevSubject: true, element: true },
  ($element, data) => {
    const clipboardData = new DataTransfer()
    clipboardData.setData('text', data)
    const pasteEvent = new ClipboardEvent('paste', {
      bubbles: true,
      cancelable: true,
      data,
      clipboardData,
    })

    cy.get($element).then(() => {
      $element[0].dispatchEvent(pasteEvent)
    })
  },
)