/* global cy, Cypress, expect */

// Custom Cypress commands for the BeyondWords test suite.
// See https://on.cypress.io/custom-commands

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

/**
 * Get the block editor canvas body.
 *
 * WordPress 6.8+ renders the editor inside an iframe. This command
 * checks for the iframe first and falls back to the top-level document
 * for older versions.
 */
Cypress.Commands.add( 'getEditorCanvasBody', () => {
	return cy.get( 'body' ).then( ( $body ) => {
		const $iframe = $body.find( 'iframe[name="editor-canvas"]' );
		if ( $iframe.length ) {
			return cy
				.wrap( $iframe )
				.its( '0.contentDocument.body' )
				.should( 'not.be.empty' )
				.then( cy.wrap );
		}
		return cy.wrap( $body );
	} );
} );

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
	const baseUrl = Cypress.config().baseUrl;

	cy.env( [ 'wpUsername', 'wpPassword' ] ).then(
		( { wpUsername, wpPassword } ) => {
			cy.visit( '/wp-login.php' ).wait( 250 );

			cy.get( '#user_login' ).clear().type( wpUsername ).wait( 250 );
			cy.get( '#user_pass' ).clear().type( `${ wpPassword }{enter}` );

			cy.url().should( 'eq', `${ baseUrl }/wp-admin/` );
		}
	);
} );

Cypress.Commands.add( 'createPost', ( options = {} ) => {
	const { postType = postTypes[ 0 ], title = '' } = options;

	cy.visitPostEditor( postType.slug );
	cy.setPostTitle( title );
} );

Cypress.Commands.add( 'setPostTitle', ( title ) => {
	if ( title ) {
		cy.getEditorCanvasBody()
			.find( '.editor-post-title__input' )
			.clear()
			.type( title );
	}
} );

Cypress.Commands.add( 'visitPostEditor', ( postType ) => {
	cy.visit( `/wp-admin/post-new.php?post_type=${ postType }` );
	cy.disableWelcomeGuides();
} );

Cypress.Commands.add( 'visitPostEditorById', ( postId ) => {
	cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );
	cy.disableWelcomeGuides();
} );

Cypress.Commands.add( 'disableWelcomeGuides', () => {
	// Wait for editor and dismiss welcome modal if it appears
	cy.window()
		.its( 'wp.data' )
		.then( ( data ) => {
			const prefs = data.dispatch( 'core/preferences' );
			prefs.set( 'core/edit-post', 'welcomeGuide', false );
			prefs.set( 'core/edit-post', 'welcomeGuideTemplate', false );
			prefs.set( 'core', 'enableChoosePatternModal', false );
		} );

	// Wait briefly for any modal to render, then dismiss it
	cy.wait( 500 ); // eslint-disable-line
	cy.get( 'body' ).then( ( $body ) => {
		const closeBtn = $body.find( '.components-modal__header button' );
		if ( closeBtn.length ) {
			cy.wrap( closeBtn ).first().click();
		}
	} );
} );

Cypress.Commands.add( 'showsOnlyAuthenticationSettingsTab', () => {
	cy.get( '.nav-tab' ).contains( 'Authentication' );
	cy.get( '.nav-tab' ).contains( 'Integration' ).should( 'not.exist' );
	cy.get( '.nav-tab' ).contains( 'Preferences' ).should( 'not.exist' );
} );

Cypress.Commands.add( 'showsAllSettingsTabs', () => {
	cy.get( '.nav-tab' ).contains( 'Authentication' );
	cy.get( '.nav-tab' ).contains( 'Integration' );
	cy.get( '.nav-tab' ).contains( 'Preferences' );
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

Cypress.Commands.add( 'dismissPointers', () => {
	// Dismiss WordPress admin pointers/tooltips that may be covering elements
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.wp-pointer' ).length > 0 ) {
			// Try clicking the close button (X in top right)
			cy.get(
				'.wp-pointer .wp-pointer-buttons a.close, .wp-pointer button.wp-pointer-close'
			).each( ( $closeBtn ) => {
				cy.wrap( $closeBtn ).click( { force: true } );
			} );
		}
	} );
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

	cy.hasAdminPlayerInstances( 1 );
} );

Cypress.Commands.add( 'publishPostWithoutAudio', ( options = {} ) => {
	const { postType = postTypes[ 0 ] } = options;

	cy.createPost( options );

	cy.uncheckGenerateAudio( postType );

	cy.publishWithConfirmation();

	cy.getBlockEditorCheckbox( 'Generate audio' ).should( 'exist' );

	cy.hasAdminPlayerInstances( 0 );
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
	cy.getEditorCanvasBody()
		.find( '.wp-block-post-content p:last-of-type' )
		.click();
	cy.getEditorCanvasBody().type( `${ text }{enter}` ).wait( 200 );
} );

/**
 * Add (append) a new paragraph block with the specified text.
 */
Cypress.Commands.add( 'clickTitleBlock', () => {
	cy.getEditorCanvasBody().find( '.editor-post-title' ).click();
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

Cypress.Commands.add( 'openBeyondwordsPluginSidebar', () => {
	cy.openBeyondwordsEditorPanel();
	cy.get( '.beyondwords-sidebar' )
		.contains( 'a', 'BeyondWords sidebar' )
		.click();
	cy.get( '.beyondwords-sidebar__status' ).should( 'be.visible' );
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
	).click();

	// Wait for publish to complete
	cy.get( '.editor-post-publish-panel' ).should( 'exist' );

	// Close "Patterns" modal if it opens (introduced in WordPress 6.6)
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.components-modal__frame' ).length ) {
			cy.get(
				'.components-modal__frame button.components-button[aria-label="Close"]'
			).click();
			cy.get( '.components-modal__frame' ).should( 'not.exist' );
		}
	} );

	// Close Prepublish panel if it's still open
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( 'button[aria-label="Close panel"]' ).length ) {
			cy.get( 'button[aria-label="Close panel"]' ).click();
		}
	} );
} );

// "Save" existing post
Cypress.Commands.add( 'savePost', () => {
	cy.get( '.editor-post-publish-button' ).click();
} );

Cypress.Commands.add( 'viewPostById', ( postId ) => {
	cy.visit( `/?p=${ postId }` );
} );

Cypress.Commands.add( 'viewPostViaSnackbar', () => {
	cy.get( '.components-snackbar' )
		.find( 'a' )
		.invoke( 'removeAttr', 'target' )
		.click();
} );

// Get label element from text
Cypress.Commands.add( 'getLabel', ( text, ...args ) => {
	return cy.get( 'label', ...args ).contains( text );
} );

// Check for a number of admin player instances.
Cypress.Commands.add( 'hasAdminPlayerInstances', ( num = 1 ) => {
	if ( num < 0 ) {
		throw new Error( 'Number of player instances cannot be negative.' );
	}

	if ( num === 0 ) {
		cy.get( '.beyondwords-player-box-wrapper' ).should( 'not.exist' );
		return;
	}

	cy.get( '.beyondwords-player-box-wrapper' ).should( 'exist' );
} );

// Check for a number of player instances.
Cypress.Commands.add( 'hasPlayerInstances', ( num = 1, params = {} ) => {
	// Ensure the player script tag count matches the expected number of instances.
	if ( num < 0 ) {
		throw new Error( 'Number of player instances cannot be negative.' );
	}

	if ( num === 0 ) {
		cy.getPlayerScriptTag().should( 'not.exist' );
		return;
	}

	cy.getPlayerScriptTag().should( 'have.length', num );

	if ( _.isEmpty( params ) ) {
		// eslint-disable-next-line no-useless-return
		return;
	}

	// Check params exist in the params of the player script tag's onload init object.
	cy.getPlayerScriptTag().each( ( $el ) => {
		const onload = $el.attr( 'onload' );
		const match = onload.match( /\{target:this, \.\.\.(.+)\}\)/ );
		console.log( 'onload', onload );
		console.log( 'match', match );

		if ( ! match ) {
			throw new Error(
				'Could not find params object in onload attribute.'
			);
		}

		const paramsStr = match[ 1 ];

		let paramsObj = JSON.parse( paramsStr );

		// Parse double-encoded JSON strings again.
		if ( typeof paramsObj === 'string' ) {
			paramsObj = JSON.parse( paramsObj );
		}

		Object.entries( params ).forEach( ( [ key, value ] ) => {
			if ( value === undefined ) {
				expect( paramsObj ).to.not.have.property( key, value );
			} else {
				expect( paramsObj ).to.have.property( key ).that.eql( value );
			}
		} );
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

// Get frontend audio player script tag.
Cypress.Commands.add( 'getPlayerScriptTag', ( ...args ) => {
	return cy.get(
		// eslint-disable-next-line max-len
		'body script[async][defer][src="https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js"]',
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

/**
 * Clean up all test posts with "Cypress Test" in the title.
 * This is much faster than a full DB reset (100-500ms vs 5-10s).
 */
Cypress.Commands.add( 'cleanupTestPosts', () => {
	cy.task( 'deleteAllPosts', 'Cypress Test' );
} );

/**
 * Reset BeyondWords plugin settings to defaults.
 * This ensures tests start with a clean slate for plugin configuration.
 * Preserves infrastructure options set by setupDatabase: API credentials
 * (to avoid 403 errors) and preselect (whose PHP default excludes cpt_active).
 */
Cypress.Commands.add( 'resetPluginSettings', () => {
	cy.task( 'deleteOptionsByPattern', {
		pattern: 'beyondwords_',
		exclude: [
			'beyondwords_api_key',
			'beyondwords_project_id',
			'beyondwords_preselect',
			// Keep the validation flag so Integration/Preferences tabs stay
			// visible between tests — Tabs::get_visible_tabs() hides them
			// when this option is missing.
			'beyondwords_valid_api_connection',
		],
	} );
} );

/**
 * Create a test post with a unique identifier.
 * Posts created with this command can be cleaned up with cy.cleanupTestPosts().
 *
 * @param {Object} options - Post creation options
 * @return {Promise<number>} The created post ID
 */
Cypress.Commands.add( 'createTestPost', ( options = {} ) => {
	if ( 'future' === options.postStatus ) {
		// Set future date 10 years from now
		const futureDate = new Date();
		const year = futureDate.getFullYear() + 10;
		options.postDate = `${ year }-01-01T00:00:00Z`;
	}
	return cy.task( 'createPost', options );
} );

/**
 * Create a test post with BeyondWords audio generation enabled.
 *
 * @param {Object} options - Post creation options
 * @return {Promise<number>} The created post ID
 */
Cypress.Commands.add( 'createTestPostWithAudio', ( options = {} ) => {
	return cy.createTestPost( options ).then( ( postId ) => {
		// Set the meta to generate audio for this post
		return cy
			.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_generate_audio',
				metaValue: '1',
			} )
			.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_project_id',
				metaValue: Cypress.expose('projectId'),
			} )
			.task( 'setPostMeta', {
				postId,
				metaKey: 'beyondwords_content_id',
				metaValue: Cypress.expose('contentId'),
			} )
			.then( () => postId );
	} );
} );
