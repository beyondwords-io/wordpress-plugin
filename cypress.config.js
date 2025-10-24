const { defineConfig } = require( 'cypress' );
const util = require( 'util' );
const exec = util.promisify( require( 'child_process' ).exec );

// Track if we've done the one-time setup for this test run
let hasSetupDatabase = false;

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
		async 'wp-env:clean'() {
			return await exec( 'yarn wp-env clean' );
		},

		async run( command ) {
			if ( process.env.CI ) {
				return await exec( command );
			}

			return await exec(
				`yarn wp-env run tests-cli ${ JSON.stringify(
					String( command )
				) }`
			);
		},

		async reset() {
			if ( process.env.CI ) {
				await exec( 'wp plugin activate wp-reset' );
				await exec( 'wp reset reset --yes' );
				await exec( 'wp plugin deactivate --all' );
				await exec(
					// eslint-disable-next-line max-len
					'wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported'
				);
			} else {
				await exec(
					`yarn wp-env run tests-cli wp plugin activate wp-reset`
				);
				await exec( `yarn wp-env run tests-cli wp reset reset --yes` );
				await exec(
					`yarn wp-env run tests-cli wp plugin deactivate --all`
				);
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported`
				);
			}
			return null;
		},

		async ensureTestPlugins() {
			// Ensure required test plugins are activated (without full DB reset)
			const plugins =
				'speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported';

			if ( process.env.CI ) {
				await exec( `wp plugin activate ${ plugins }` );
			} else {
				await exec(
					`yarn wp-env run tests-cli wp plugin activate ${ plugins }`
				);
			}
			return null;
		},

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
			console.log( '  → Running one-time database setup...' );

			// Reset database and activate plugins
			if ( process.env.CI ) {
				await exec( 'wp plugin activate wp-reset' );
				await exec( 'wp reset reset --yes' );
				await exec( 'wp plugin deactivate --all' );
				await exec(
					// eslint-disable-next-line max-len
					'wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported'
				);

				// Configure plugin credentials for most tests
				await exec(
					`wp option update beyondwords_api_key '${ apiKey }'`
				);
				await exec(
					`wp option update beyondwords_project_id '${ projectId }'`
				);
				await exec(
					`wp option add beyondwords_valid_api_connection '2025-01-01T00:00:00+00:00'`
				);
				// Set defaults for options NOT synced from API
				await exec( `wp option add beyondwords_player_ui enabled` );
				await exec(
					'wp option add beyondwords_preselect \'{"post":"1","page":"1"}\' --format=json'
				);
			} else {
				await exec(
					`yarn wp-env run tests-cli wp plugin activate wp-reset`
				);
				await exec( `yarn wp-env run tests-cli wp reset reset --yes` );
				await exec(
					`yarn wp-env run tests-cli wp plugin deactivate --all`
				);
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported`
				);

				// Configure plugin credentials for most tests
				// Note: These are read from cypress.env.json or environment
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp option update beyondwords_api_key '${ apiKey }'`
				);
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp option update beyondwords_project_id '${ projectId }'`
				);
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp option add beyondwords_valid_api_connection '2025-01-01T00:00:00+00:00'`
				);
				// Set defaults for options NOT synced from API
				await exec(
					`yarn wp-env run tests-cli wp option add beyondwords_player_ui enabled`
				);
				await exec(
					// eslint-disable-next-line max-len
					'yarn wp-env run tests-cli wp option add beyondwords_preselect \'{"post":"1","page":"1"}\' --format=json'
				);
			}

			hasSetupDatabase = true;
			// eslint-disable-next-line no-console
			console.log( '  ✓ Database setup complete with credentials!' );
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
				'\n  🔄 Resetting to FRESH database (no credentials) for fresh-install test...'
			);

			if ( process.env.CI ) {
				await exec( 'wp plugin activate wp-reset' );
				await exec( 'wp reset reset --yes' );
				await exec( 'wp plugin deactivate --all' );
				await exec(
					// eslint-disable-next-line max-len
					'wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported'
				);
			} else {
				await exec(
					`yarn wp-env run tests-cli wp plugin activate wp-reset`
				);
				await exec( `yarn wp-env run tests-cli wp reset reset --yes` );
				await exec(
					`yarn wp-env run tests-cli wp plugin deactivate --all`
				);
				await exec(
					// eslint-disable-next-line max-len
					`yarn wp-env run tests-cli wp plugin activate speechkit Basic-Auth cpt-active cpt-inactive cpt-unsupported`
				);
			}

			// Reset the flag so next test file will run setupDatabase again
			// This ensures subsequent tests get credentials configured
			hasSetupDatabase = false;

			// eslint-disable-next-line no-console
			console.log(
				'  ✓ Fresh database ready (credentials NOT configured - ready for fresh-install test)\n'
			);
			return null;
		},

		async 'wp:plugin:activate'( plugin ) {
			if ( process.env.CI ) {
				return await exec( `wp plugin activate ${ plugin }` );
			}

			return await exec(
				`yarn wp-env run tests-cli wp plugin activate ${ plugin }`
			);
		},

		async 'wp:plugin:deactivate'( plugin ) {
			if ( process.env.CI ) {
				return await exec( `wp plugin deactivate ${ plugin }` );
			}

			return await exec(
				`yarn wp-env run tests-cli wp plugin deactivate ${ plugin }`
			);
		},

		async 'wp:plugin:uninstall'( plugin ) {
			if ( process.env.CI ) {
				return await exec(
					`wp plugin uninstall --deactivate ${ plugin }`
				);
			}

			return await exec(
				`yarn wp-env run tests-cli wp plugin uninstall --deactivate ${ plugin }`
			);
		},

		async 'wp:post:deleteAll'( searchTerm ) {
			// eslint-disable-next-line max-len
			const wpCmd = `wp post delete $(wp post list --post_type=post,page --s='${ searchTerm }' --format=ids) --force`;

			if ( process.env.CI ) {
				try {
					await exec( wpCmd );
				} catch ( error ) {
					// Ignore errors if no posts found
				}
				return null;
			}

			try {
				await exec( `yarn wp-env run tests-cli ${ wpCmd }` );
			} catch ( error ) {
				// Ignore errors if no posts found
			}
			return null;
		},

		async 'wp:post:create'( options ) {
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
			const wpCmd = `wp post create --post_type=${ postType } --post_status=${ status } --post_title='${ escapedTitle }' --post_content='${ escapedContent }' --porcelain`;

			if ( process.env.CI ) {
				const result = await exec( wpCmd );
				return parseInt( result.stdout.trim(), 10 );
			}

			const result = await exec( `yarn wp-env run tests-cli ${ wpCmd }` );
			return parseInt( result.stdout.trim(), 10 );
		},

		async 'wp:post:setMeta'( options ) {
			const { postId, metaKey, metaValue } = options;

			const wpCmd = `wp post meta set ${ postId } ${ metaKey } '${ metaValue }'`;

			if ( process.env.CI ) {
				await exec( wpCmd );
			} else {
				await exec( `yarn wp-env run tests-cli ${ wpCmd }` );
			}

			return null;
		},

		async 'wp:option:delete'( optionName ) {
			const wpCmd = `wp option delete ${ optionName }`;

			if ( process.env.CI ) {
				try {
					await exec( wpCmd );
				} catch ( error ) {
					// Ignore errors if option doesn't exist
				}
			} else {
				try {
					await exec( `yarn wp-env run tests-cli ${ wpCmd }` );
				} catch ( error ) {
					// Ignore errors if option doesn't exist
				}
			}

			return null;
		},

		async 'wp:options:deleteByPattern'( options ) {
			const { pattern, exclude = [] } = options;

			// Get all options matching pattern and delete them
			const listCmd = `wp option list --search='${ pattern }' --field=option_name`;

			try {
				let result;
				if ( process.env.CI ) {
					result = await exec( listCmd );
				} else {
					result = await exec(
						`yarn wp-env run tests-cli ${ listCmd }`
					);
				}

				const optionNames = result.stdout
					.trim()
					.split( '\n' )
					.filter( Boolean );

				for ( const optionName of optionNames ) {
					// Skip excluded options
					if ( exclude.includes( optionName ) ) {
						continue;
					}

					const deleteCmd = `wp option delete ${ optionName }`;
					if ( process.env.CI ) {
						await exec( deleteCmd );
					} else {
						await exec(
							`yarn wp-env run tests-cli ${ deleteCmd }`
						);
					}
				}
			} catch ( error ) {
				// Ignore errors if no options found
			}

			return null;
		},
	} );
}
