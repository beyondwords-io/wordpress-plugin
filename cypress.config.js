const { defineConfig } = require( 'cypress' );
const util = require( 'util' );
const exec = util.promisify( require( 'child_process' ).exec );

// Track if we've done the one-time setup for this test run
let hasSetupDatabase = false;

/**
 * Helper function to execute WP-CLI commands
 * Automatically handles CI vs local environment differences
 *
 * @param {string|string[]} commands             - Single command or array of commands
 * @param {Object}          options              - Optional configuration
 * @param {boolean}         options.returnResult - Return exec result
 * @return {Promise<void|Object>} Exec result if returnResult is true
 */
async function execWp( commands, options = {} ) {
	const commandArray = Array.isArray( commands ) ? commands : [ commands ];
	const isCI = process.env.CI;
	const { returnResult = false } = options;
	let lastResult;

	for ( const command of commandArray ) {
		const fullCommand = isCI
			? `wp ${ command }`
			: `yarn wp-env run tests-cli wp ${ command }`;
		lastResult = await exec( fullCommand );
	}

	return returnResult ? lastResult : undefined;
}

module.exports = defineConfig( {
	projectId: 'd5g7ep',
	defaultCommandTimeout: 15000,
	downloadsFolder: 'tests/cypress/downloads',
	env: {
		wpUsername: 'admin',
		wpPassword: 'password',
	},
	experimentalMemoryManagement: true,
	fixturesFolder: 'tests/fixtures',
	includeShadowDom: true,
	screenshotsFolder: 'tests/cypress/screenshots',
	screenshotOnRunFailure: true,
	reporter: 'cypress-multi-reporters',
	reporterOptions: {
		configFile: 'tests/cypress/reporter.config.json',
	},
	e2e: {
		baseUrl: 'http://localhost:8889',
		setupNodeEvents( on, config ) {
			return setupNodeEvents( on, config );
		},
		specPattern: [ 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}' ],
		supportFile: 'tests/cypress/support/e2e.js',
	},
} );

function setupNodeEvents( on, config ) {
	require( 'cypress-terminal-report/src/installLogsPrinter' )( on );
	require( 'cypress-fail-fast/plugin' )( on, config );

	// Get credentials from config for use in tasks
	const apiKey = config.env.apiKey || '';
	const projectId = config.env.projectId || '';

	// implement node event listeners here
	on( 'task', {
		async setupDatabase() {
			// Run database setup only once per test suite
			// This sets up a clean database WITH credentials configured
			if ( hasSetupDatabase ) {
				// eslint-disable-next-line no-console
				console.log(
					'  ✓ Database already set up for this test run, skipping...'
				);
				return null;
			}

			// eslint-disable-next-line no-console
			console.log( '  - Running database setup...' );

			// Reset database and activate plugins
			await execWp( [
				'plugin activate wp-reset',
				'reset reset --yes',
				'plugin deactivate --all',
				// eslint-disable-next-line max-len
				'plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported beyondwords-mock-rest-api-responses',
				// Configure plugin credentials for most tests
				`option update beyondwords_api_key '${ apiKey }'`,
				`option update beyondwords_project_id '${ projectId }'`,
				`option add beyondwords_valid_api_connection '2025-01-01T00:00:00+00:00'`,
				// Set defaults for options NOT synced from API
				'option add beyondwords_player_ui enabled',
				// eslint-disable-next-line max-len
				'option add beyondwords_preselect \'{"post":"1","page":"1","cpt_active":"1"}\' --format=json',
			] );

			hasSetupDatabase = true;
			// eslint-disable-next-line no-console
			console.log( '  ✓ Database setup complete' );
			return null;
		},

		async setupFreshDatabase() {
			// Setup a fresh database WITHOUT credentials
			// Use this for tests that need to test fresh install behavior
			// Note: This always resets, even if setupDatabase was called before
			// After this runs, we reset the flag so the next test file will
			// trigger setupDatabase again to restore credentials
			// eslint-disable-next-line no-console
			console.log(
				'\n  🔄 Resetting to FRESH database (no credentials)...'
			);

			await execWp( [
				'plugin activate wp-reset',
				'reset reset --yes',
				'plugin deactivate --all',
				// eslint-disable-next-line max-len
				'plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported beyondwords-mock-rest-api-responses',
			] );

			// Reset the flag so next test file will run setupDatabase again
			// This ensures subsequent tests get credentials configured
			hasSetupDatabase = false;

			// eslint-disable-next-line no-console
			console.log(
				'  ✓ Fresh database ready (credentials NOT configured)\n'
			);
			return null;
		},

		async activatePlugin( plugin ) {
			await execWp( `plugin activate ${ plugin }` );
			return null;
		},

		async deactivatePlugin( plugin ) {
			await execWp( `plugin deactivate ${ plugin }` );
			return null;
		},

		async uninstallPlugin( plugin ) {
			await execWp( `plugin uninstall --deactivate ${ plugin }` );
			return null;
		},

		async deleteAllPosts( searchTerm ) {
			// eslint-disable-next-line max-len
			const wpCmd = `post delete $(wp post list --post_type=post,page,cpt_active --s='${ searchTerm }' --format=ids) --force`;

			try {
				await execWp( wpCmd );
			} catch ( error ) {
				// Ignore errors if no posts found
			}
			return null;
		},

		async createPost( options ) {
			const {
				title = 'Test Post',
				content = '',
				status = 'publish',
				postType = 'post',
			} = options;

			// Escape single quotes in title and content
			const escapedTitle = title.replace( /'/g, "'\\''" );
			const escapedContent = content.replace( /'/g, "'\\''" );

			// eslint-disable-next-line max-len
			const wpCmd = `post create --post_type=${ postType } --post_status=${ status } --post_title='${ escapedTitle }' --post_content='${ escapedContent }' --porcelain`;

			const result = await execWp( wpCmd, { returnResult: true } );
			return parseInt( result.stdout.trim(), 10 );
		},

		async setPostMeta( options ) {
			const { postId, metaKey, metaValue } = options;
			await execWp(
				`post meta set ${ postId } ${ metaKey } '${ metaValue }'`
			);
			return null;
		},

		async updateOption( args ) {
			const { name, value } = args;
			await execWp( `option update ${ name } '${ value }'` );
			return null;
		},

		async deleteOption( optionName ) {
			try {
				await execWp( `option delete ${ optionName }` );
			} catch ( error ) {
				// Ignore errors if option doesn't exist
			}
			return null;
		},

		async deleteOptionsByPattern( options ) {
			const { pattern, exclude = [] } = options;

			try {
				// Get all options matching pattern
				const listCmd = `option list --search='${ pattern }' --field=option_name`;
				const result = await execWp( listCmd, { returnResult: true } );

				const optionNames = result.stdout
					.trim()
					.split( '\n' )
					.filter( Boolean );

				// Delete each option (except excluded ones)
				for ( const optionName of optionNames ) {
					if ( ! exclude.includes( optionName ) ) {
						await execWp( `option delete ${ optionName }` );
					}
				}
			} catch ( error ) {
				// Ignore errors if no options found
			}

			return null;
		},
	} );
}
