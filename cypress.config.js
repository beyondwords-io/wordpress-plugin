const { defineConfig } = require( 'cypress' );
const util = require( 'util' );
const exec = util.promisify( require( 'child_process' ).exec );

module.exports = defineConfig( {
	projectId: 'd5g7ep',
	defaultCommandTimeout: 8000,
	downloadsFolder: 'tests/cypress/downloads',
	env: {
		wpUsername: 'admin',
		wpPassword: 'password',
	},
	experimentalMemoryManagement: true,
	fixturesFolder: 'tests/fixtures',
	includeShadowDom: true,
	reporter: 'cypress-multi-reporters',
	reporterOptions: {
		configFile: 'tests/cypress/reporter.config.json',
	},
	retries: {
		runMode: 3,
		openMode: 0,
	},
	screenshotsFolder: 'tests/cypress/screenshots',
	screenshotOnRunFailure: true,
	video: false,
	videosFolder: 'tests/cypress/videos',
	e2e: {
		setupNodeEvents( on, config ) {
			return setupNodeEvents( on, config );
		},
		baseUrl: 'http://localhost:8889',
		specPattern: [ 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}' ],
		supportFile: 'tests/cypress/support/e2e.js',
	},
} );

function setupNodeEvents( on, config ) {
	require( 'cypress-terminal-report/src/installLogsPrinter' )( on );
	// require( 'cypress-fail-fast/plugin' )( on, config );

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

		async 'wp:post:create'( postType ) {
			if ( process.env.CI ) {
				return await exec(
					// eslint-disable-next-line max-len
					`wp post create --post_type=${ postType.slug } --post_status=published --post_title='A sample post' --porcelain`
				);
			}

			return await exec(
				// eslint-disable-next-line max-len
				`yarn wp-env run tests-cli wp post create --post_type=${ postType.slug } --post_status=published --post_title='A sample post' --porcelain`
			);
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
	} );
}
