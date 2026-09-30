module.exports = {
	preset: '@wordpress/jest-preset-default',
	reporters: [ 'default', 'github-actions' ],
	transform: {
		'\\.[jt]sx?$': [
			'babel-jest',
			{ presets: [ '@wordpress/babel-preset-default' ] },
		],
	},
};
