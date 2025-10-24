const { defineConfig } = require( 'cypress' );
const util = require( 'util' );
const exec = util.promisify( require( 'child_process' ).exec );

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

		async 'ensureTestPlugins'() {
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

		async 'wp:post:deleteAll'( searchTerm ) {
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
					result = await exec( `yarn wp-env run tests-cli ${ listCmd }` );
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
						await exec( `yarn wp-env run tests-cli ${ deleteCmd }` );
					}
				}
			} catch ( error ) {
				// Ignore errors if no options found
			}

			return null;
		},
	} );
}
