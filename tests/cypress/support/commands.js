/* global cy, Cypress, DataTransfer, ClipboardEvent */

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

import _ from 'lodash';

const postTypes = require( '../../fixtures/post-types.json' );

// Set keystroke delay
Cypress.Commands.overwrite(
	'type',
	( originalFn, subject, text, options = {} ) => {
		options.delay = 20;
		return originalFn( subject, text, options );
	}
);

Cypress.Commands.add( 'getTinyMceIframeBody', () => {
	// get the iframe > document > body
	// and retry until the body element is not empty
	return (
		cy
			.get( '#content_ifr' )
			.its( '0.contentDocument.body' )
			.should( 'not.be.empty' )
			/*
			 * Wraps "body" DOM element to allow
			 * chaining more Cypress commands, like ".find(...)"
			 * https://on.cypress.io/wrap
			 */
			.then( cy.wrap )
	);
} );

Cypress.Commands.add( 'login', () => {
	const username = Cypress.env( 'wpUsername' );
	const password = Cypress.env( 'wpPassword' );
	const baseUrl = Cypress.config().baseUrl;

	cy.visit( '/wp-login.php' ).wait( 250 );

	cy.get( '#user_login' ).clear().type( username ).wait( 250 );
	cy.get( '#user_pass' ).clear().type( `${ password }{enter}` );

	cy.url().should( 'eq', `${ baseUrl }/wp-admin/` );
} );

Cypress.Commands.add( 'createPost', ( options = {} ) => {
	const { postType = postTypes[ 0 ], title = '' } = options;

	cy.visitPostEditor( postType.slug );

	if ( title ) {
		cy.get( '.editor-post-title__input' ).type( title );
	}
} );

Cypress.Commands.add( 'visitPostEditor', ( postType ) => {
	const key = `WP_PREFERENCES_USER_1`;

	cy.visit( `/wp-admin/post-new.php?post_type=${ postType }`, {
		onBeforeLoad( win ) {
			let state = {};

			// Attempt to load existing preferences.
			const raw = win.localStorage.getItem( key );
			if ( raw ) {
				try {
					state = JSON.parse( raw );
				} catch ( err ) {}
			}

			// Disable "Show starter patterns".
			state.core = {
				...( state.core || {} ),
				enableChoosePatternModal: false,
			};

			// Close Welcome Guides.
			state[ 'core/edit-post' ] = {
				...( state[ 'core/edit-post' ] || {} ),
				welcomeGuide: false,
				welcomeGuideTemplate: false,
			};

			// Write it back.
			win.localStorage.setItem( key, JSON.stringify( state ) );
		},
	} );

	cy.get( 'body' ).should(
		'not.contain.text',
		'Welcome to the block editor'
	);

	// Close "Choose a pattern" dialog.
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( 'button:contains("Start blank")' ).length ) {
			cy.contains( 'button', 'Start blank' ).click( { force: true } );
		} else if ( $body.find( 'button[aria-label="Close dialog"]' ).length ) {
			cy.get( 'button[aria-label="Close dialog"]' ).click( {
				force: true,
			} );
		}
	} );
} );

Cypress.Commands.add( 'showsOnlyCredentialsSettingsTab', () => {
	cy.get( '.nav-tab' ).contains( 'Credentials' );
	cy.get( '.nav-tab' ).contains( 'Content' ).should( 'not.exist' );
	cy.get( '.nav-tab' ).contains( 'Voices' ).should( 'not.exist' );
	cy.get( '.nav-tab' ).contains( 'Player' ).should( 'not.exist' );
	cy.get( '.nav-tab' ).contains( 'Summarization' ).should( 'not.exist' );
	cy.get( '.nav-tab' ).contains( 'Pronunciations' ).should( 'not.exist' );
} );

Cypress.Commands.add( 'showsAllSettingsTabs', () => {
	cy.get( '.nav-tab' ).contains( 'Credentials' );
	cy.get( '.nav-tab' ).contains( 'Content' );
	cy.get( '.nav-tab' ).contains( 'Voices' );
	cy.get( '.nav-tab' ).contains( 'Player' );
	cy.get( '.nav-tab' ).contains( 'Summarization' );
	cy.get( '.nav-tab' ).contains( 'Pronunciations' );
} );

Cypress.Commands.add( 'showsPluginSettingsNotice', () => {
	cy.get( '.notice.notice-info' )
		.find( 'p' )
		.eq( 0 )
		.contains( 'To use BeyondWords, please update the plugin settings.' );
	cy.get( '.notice.notice-info' )
		.find( 'p' )
		.eq( 1 )
		.contains( 'Don’t have a BeyondWords account yet?' );
	cy.get( '.notice.notice-info' )
		.find( 'p' )
		.eq( 2 )
		.find( 'a.button.button-secondary' )
		.contains( 'Sign up free' );
} );

Cypress.Commands.add( 'showsInvalidApiCredsNotice', () => {
	cy.get( '.notice-error' ).find( 'li' ).should( 'have.length', 1 );
	cy.get( '.notice-error' )
		.find( 'li' )
		.eq( 0 )
		.contains(
			'We were unable to validate your BeyondWords REST API connection.'
		);
} );

Cypress.Commands.add( 'getPluginSettingsNoticeLink', () => {
	return cy
		.get( '.notice.notice-info' )
		.find( 'p' )
		.eq( 0 )
		.find( 'a' )
		.then( ( $el ) => cy.wrap( $el ) );
} );

Cypress.Commands.add( 'saveMinimalPluginSettings', () => {
	cy.visit( '/wp-admin/options-general.php?page=beyondwords' );

	cy.get( 'input[name="beyondwords_api_key"]' )
		.clear()
		.type( Cypress.env( 'apiKey' ) );
	cy.get( 'input[name="beyondwords_project_id"]' )
		.clear()
		.type( Cypress.env( 'projectId' ) );

	cy.get( 'input[type=submit]' ).click();
	cy.get( '.notice-success' );
} );

Cypress.Commands.add( 'saveStandardPluginSettings', () => {
	cy.saveMinimalPluginSettings();

	cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=content' );

	cy.get( '#beyondwords_prepend_excerpt' ).uncheck();
	cy.get( 'input[name="beyondwords_preselect[post]"]' ).check();
	cy.get( 'input[name="beyondwords_preselect[page]"]' ).check();
	cy.get( 'input[name="beyondwords_preselect[cpt_active]"]' ).check();
	cy.get( 'input[name="beyondwords_preselect[cpt_inactive]"]' ).should(
		'not.be.checked'
	);
	cy.get( 'input[name="beyondwords_preselect[cpt_unsupported]"]' ).should(
		'not.exist'
	);

	cy.get( 'input[type=submit]' ).click();
	cy.get( '.notice-success' );
} );

Cypress.Commands.add( 'saveAllPluginSettings', () => {
	cy.saveStandardPluginSettings();

	cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=voices' );
	cy.get( 'input[type=submit]' ).click();
	cy.get( '.notice-success' );

	cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );
	cy.get( 'input[type=submit]' ).click();
	cy.get( '.notice-success' );
} );

Cypress.Commands.add( 'setPlayerStyleInPluginSettings', ( value ) => {
	cy.visit( '/wp-admin/options-general.php?page=beyondwords&tab=player' );

	cy.get( 'select[name="beyondwords_player_style"]' ).select( value );
	cy.get( 'input[type=submit]' ).click();
	cy.get( '.notice-success' );
} );

Cypress.Commands.add( 'visitPluginSiteHealth', () => {
	cy.visit( '/wp-admin/site-health.php?tab=debug' );
	cy.get(
		'button[aria-controls="health-check-accordion-block-beyondwords"]'
	).click();
} );

Cypress.Commands.add( 'getSiteHealthValue', ( label, ...args ) => {
	return cy
		.contains( label )
		.parent( 'tr' )
		.find( 'td' )
		.then( ( $el ) => cy.wrap( $el, args ) );
} );

Cypress.Commands.add( 'activatePlugin', ( ...args ) => {
	args.flat().forEach( ( plugin ) => {
		cy.task( 'wp:plugin:activate', plugin );
	} );
} );

/**
 * Deactivate one or more plugins.
 */
Cypress.Commands.add( 'deactivatePlugin', ( ...args ) => {
	args.flat().forEach( ( plugin ) => {
		cy.task( 'wp:plugin:deactivate', plugin );
	} );
} );

/**
 * Uninstall one or more plugins.
 */
Cypress.Commands.add( 'uninstallPlugin', ( ...args ) => {
	args.flat().forEach( ( plugin ) => {
		cy.task( 'wp:plugin:uninstall', plugin );
	} );
} );

/**
 * "Save as draft" for block editor.
 */
Cypress.Commands.add( 'saveAsDraft', () => {
	cy.contains( 'button', 'Save draft' ).click().wait( 100 );
} );

/**
 * "Save as pending" for block editor.
 */
Cypress.Commands.add( 'saveAsPending', () => {
	cy.contains( 'button', 'Save as pending' ).click().wait( 100 );
} );

Cypress.Commands.add( 'createBlockProgramatically', ( name, params = {} ) => {
	cy.window().then( ( win ) => {
		win.eval( `
      var block = wp.blocks.createBlock( '${ name }', ${ JSON.stringify(
			params
		) } );
      wp.data.dispatch( 'core/block-editor' ).insertBlocks( block );
    ` );
	} );
	cy.wait( 200 );
} );

Cypress.Commands.add( 'checkGenerateAudio', ( postType ) => {
	if ( ! postType ) {
		postType = postTypes[ 0 ];
	}

	cy.openBeyondwordsEditorPanel();

	cy.getBlockEditorCheckbox( 'Generate audio' ).should(
		postType.preselect ? 'be.checked' : 'not.be.checked'
	);

	if ( ! postType.preselect ) {
		cy.getLabel( 'Generate audio' ).click();
	}

	cy.getBlockEditorCheckbox( 'Generate audio' ).should( 'be.checked' );
} );

Cypress.Commands.add( 'uncheckGenerateAudio', ( postType ) => {
	if ( ! postType ) {
		postType = postTypes[ 0 ];
	}

	cy.openBeyondwordsEditorPanel();

	cy.getBlockEditorCheckbox( 'Generate audio' ).should(
		postType.preselect ? 'be.checked' : 'not.be.checked'
	);

	if ( postType.preselect ) {
		cy.getLabel( 'Generate audio' ).click();
	}

	cy.getBlockEditorCheckbox( 'Generate audio' ).should( 'not.be.checked' );
} );

Cypress.Commands.add( 'publishPostWithAudio', ( options = {} ) => {
	const { postType = postTypes[ 0 ] } = options;

	cy.createPost( options );

	cy.checkGenerateAudio( postType );

	cy.publishWithConfirmation();

	cy.hasPlayerInstances( 1 );
} );

Cypress.Commands.add( 'publishPostWithoutAudio', ( options = {} ) => {
	const { postType = postTypes[ 0 ] } = options;

	cy.createPost( options );

	cy.uncheckGenerateAudio( postType );

	cy.publishWithConfirmation();

	cy.getBlockEditorCheckbox( 'Generate audio' ).should( 'exist' );

	cy.hasPlayerInstances( 0 );
} );

/**
 * Set post title for classic editor.
 */
Cypress.Commands.add( 'classicSetPostTitle', ( title ) => {
	cy.get( 'input#title' ).clear().type( title );
} );

/**
 * Add (append) a new paragraph block with the specified text.
 */
Cypress.Commands.add( 'addParagraphBlock', ( text ) => {
	cy.get( '.wp-block-post-content p:last-of-type' ).click();
	cy.get( 'body' ).type( `${ text }{enter}` ).wait( 200 );
} );

/**
 * Add (append) a new paragraph block with the specified text.
 */
Cypress.Commands.add( 'clickTitleBlock', () => {
	cy.get( '.editor-post-title' ).click();
} );

Cypress.Commands.add( 'openBeyondwordsEditorPanel', () => {
	cy.get( '.beyondwords-sidebar' ).scrollIntoView().should( 'be.visible' );
	cy.get( '.beyondwords-sidebar' ).then( ( $el ) => {
		if ( $el.is( '.is-opened' ) ) {
			cy.log( 'The BeyondWords editor panel is already open' );
		} else {
			cy.log( 'Opening the BeyondWords editor panel' );
			cy.wrap( $el ).click();
		}
	} );
} );

Cypress.Commands.add( 'getBlockEditorCheckbox', ( text, ...args ) => {
	return cy
		.get( 'label', ...args )
		.contains( text )
		.closest( '.components-checkbox-control' )
		.find( 'input[type="checkbox"]' )
		.then( ( $el ) => cy.wrap( $el ) );
} );

Cypress.Commands.add( 'getBlockEditorSelect', ( text, ...args ) => {
	return cy
		.get( 'label', ...args )
		.contains( text )
		.closest( '.components-select-control' )
		.find( 'select' )
		.then( ( $el ) => cy.wrap( $el ) );
} );

Cypress.Commands.add( 'assertDisplayPlayerIs', ( displayPlayer ) => {
	cy.contains( 'label', 'Display player' )
		.closest( 'div' )
		.find( 'svg' ) // Block editor uses a funky SVG instead of a checkbox
		.should( displayPlayer ? 'exist' : 'not.exist' );
} );

Cypress.Commands.add( 'setPostStatus', ( status ) => {
	cy.get( '.editor-post-status button' ).click();
	cy.get(
		`.editor-change-status__options input[value="${ status }"]`
	).click();
} );

Cypress.Commands.add( 'publishWithConfirmation', () => {
	// "Publish" in top bar
	cy.get( '.editor-post-publish-button__button' ).click();

	// Confirm "Publish" in the Prepublish panel
	cy.get(
		'.editor-post-publish-panel__header-publish-button > .components-button'
	)
		.click()
		.wait( 250 );

	// Close "Patterns" modal if it opens (introduced in WordPress 6.6)
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.components-modal__frame' ).length ) {
			cy.get(
				'.components-modal__frame button.components-button[aria-label="Close"]'
			).click();
		}
	} );

	// Close Prepublish panel
	cy.get( 'button[aria-label="Close panel"]' ).click();
} );

// "Save" existing post
Cypress.Commands.add( 'savePost', () => {
	cy.get( '.editor-post-publish-button' ).click();
} );

Cypress.Commands.add( 'viewPostViaSnackbar', () => {
	cy.get( '.components-snackbar' ).find( 'a' ).click();
} );

// Get label element from text
Cypress.Commands.add( 'getLabel', ( text, ...args ) => {
	return cy.get( 'label', ...args ).contains( text );
} );

// Check for a number of player instances.
Cypress.Commands.add( 'hasPlayerInstances', ( num = 1 ) => {
	cy.window( { timeout: 10000 } ).should( ( win ) => {
		if ( ! win.BeyondWords ) {
			throw new Error(
				'BeyondWords is not available on the window object.'
			);
		}

		if (
			! win.BeyondWords.Player ||
			typeof win.BeyondWords.Player.instances !== 'function'
		) {
			throw new Error(
				'BeyondWords.Player.instances is not a function.'
			);
		}

		const instances = win.BeyondWords.Player.instances();

		if ( instances.length !== num ) {
			throw new Error(
				`Expected ${ num } player instance(s), but found ${ instances.length }.`
			);
		}
	} );
} );

// Check for no Beyondwords Player object.
Cypress.Commands.add( 'hasNoBeyondwordsWindowObject', () => {
	cy.window( { timeout: 4000 } ).should( ( win ) => {
		if ( 'BeyondWords' in win ) {
			throw new Error(
				'Expected window.BeyondWords to be undefined, but it exists.'
			);
		}
	} );
} );

// Get frontend audio player element (standard)
Cypress.Commands.add( 'getPlayerScriptTag', ( ...args ) => {
	return cy.get(
		// eslint-disable-next-line max-len
		'body > script[async][defer][src="https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js"]',
		...args
	);
} );

/**
 * Simulates a paste event.
 * Modified from https://gist.github.com/nickytonline/bcdef8ef00211b0faf7c7c0e7777aaf6
 *
 * @param subject                   A jQuery context representing a DOM element.
 * @param pasteOptions              Set of options for a simulated paste event.
 * @param pasteOptions.pastePayload Simulated data that is on the clipboard.
 * @param pasteOptions.pasteFormat  The format of the simulated paste payload.
 *                                  Default value is 'text'.
 *
 * @return The subject parameter.
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
	( $element, data ) => {
		const clipboardData = new DataTransfer();
		clipboardData.setData( 'text', data );
		const pasteEvent = new ClipboardEvent( 'paste', {
			bubbles: true,
			cancelable: true,
			data,
			clipboardData,
		} );

		cy.get( $element ).then( () => {
			$element[ 0 ].dispatchEvent( pasteEvent );
		} );
	}
);
