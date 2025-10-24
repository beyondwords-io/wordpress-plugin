/**
 * One-time setup script for CI environment
 * Run this ONCE before the Cypress test suite starts
 * This ensures the WordPress database is initialized properly
 */

const util = require( 'util' );
const exec = util.promisify( require( 'child_process' ).exec );

async function setupCI() {
	console.log( '🔧 Setting up WordPress for Cypress tests...' );

	try {
		if ( process.env.CI ) {
			console.log( '  → Activating wp-reset plugin...' );
			await exec( 'wp plugin activate wp-reset' );

			console.log( '  → Resetting WordPress database...' );
			await exec( 'wp reset reset --yes' );

			console.log( '  → Deactivating all plugins...' );
			await exec( 'wp plugin deactivate --all' );

			console.log( '  → Activating required plugins...' );
			await exec(
				'wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported'
			);

			console.log( '✅ WordPress setup complete!' );
		} else {
			console.log( '⚠️  Not in CI environment, skipping setup' );
			console.log(
				'   If running locally, use: yarn wp-env run tests-cli wp reset reset --yes'
			);
		}
	} catch ( error ) {
		console.error( '❌ Setup failed:', error.message );
		process.exit( 1 );
	}
}

setupCI();
