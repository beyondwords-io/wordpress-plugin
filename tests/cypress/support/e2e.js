/* global Cypress, cy, beforeEach */

// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands';

// Alternatively you can use CommonJS syntax:
// require( './commands' )

// MochAwesome Reporting
import addContext from 'mochawesome/addContext';

// Extra Cypress query commands for v12+
import 'cypress-map';

Cypress.on( 'window:before:load', ( win ) => {
	cy.spy( win.console, 'log' ).as( 'consoleLog' );
	cy.spy( win.console, 'error' ).as( 'consoleError' );
	cy.spy( win.console, 'warn' ).as( 'consoleWarn' );
} );

require( 'cypress-terminal-report/src/installLogsCollector' )();

Cypress.on( 'test:after:run', ( test ) => {
	let videoName = Cypress.spec.name;
	videoName = videoName.replace( '/.js.*', '.js' );
	const videoUrl = 'videos/' + videoName + '.mp4';

	addContext( { test }, videoUrl );
} );

Cypress.on( 'uncaught:exception', () => {
	// returning false here prevents Cypress from failing the test
	return false;
} );

/**
 * Reset WordPress
 * (This is now done in each test)
 */
// before( () => {
//   cy.task( 'reset' )
//   cy.login()
//   cy.saveStandardPluginSettings()
// } )

beforeEach( () => {
	// disable Cypress's default behavior of logging all XMLHttpRequests and fetches
	cy.intercept( { resourceType: /xhr|fetch/ }, { log: false } );
} );
