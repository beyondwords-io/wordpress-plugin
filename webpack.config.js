/**
 * External Dependencies
 */
const dotenv = require( 'dotenv' );
const path = require( 'path' );
/* eslint-disable-next-line import/no-extraneous-dependencies */
const webpack = require( 'webpack' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

dotenv.config();

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( process.cwd(), 'src', 'index.js' ),
		settings: path.resolve(
			process.cwd(),
			'src',
			'Component',
			'Settings',
			'index.js'
		),
		elementor: path.resolve(
			process.cwd(),
			'src',
			'Compatibility',
			'Elementor',
			'js',
			'index.js'
		),
	},
	plugins: [
		...defaultConfig.plugins,
		new webpack.DefinePlugin( {
			'process.env.BEYONDWORDS_BACKEND_URL': JSON.stringify(
				process.env.BEYONDWORDS_BACKEND_URL ||
					'https://audio.beyondwords.io'
			),
			'process.env.BEYONDWORDS_API_URL': JSON.stringify(
				process.env.BEYONDWORDS_API_URL ||
					'https://api.beyondwords.io/v1'
			),
			'process.env.BEYONDWORDS_DASHBOARD_URL': JSON.stringify(
				process.env.BEYONDWORDS_DASHBOARD_URL ||
					'https://dash.beyondwords.io'
			),
			'process.env.BEYONDWORDS_JS_SDK_URL': JSON.stringify(
				process.env.BEYONDWORDS_JS_SDK_URL ||
					'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js'
			),
		} ),
	],
	resolve: {
		...defaultConfig.resolve,
	},
};
