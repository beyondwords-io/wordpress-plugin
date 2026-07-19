/* global Cypress, cy, before, beforeEach */

// Loaded automatically before every spec; global Cypress configuration lives here.

import './commands';

// Cypress fail-fast support (v8+ requires explicit import)
import 'cypress-fail-fast';

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

before( () => {
	cy.task( 'setupDatabase' );
} );

beforeEach( () => {
	cy.resetPluginSettings();
	cy.cleanupTestPosts();
	// disable Cypress's default behavior of logging all XMLHttpRequests and fetches
	cy.intercept( { resourceType: /xhr|fetch/ }, { log: false } );
} );
