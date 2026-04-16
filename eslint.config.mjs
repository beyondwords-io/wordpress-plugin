import wordpress from "@wordpress/eslint-plugin";

export default [
	...wordpress.configs.recommended,
	{
		rules: {
			"max-len": [ "error", { code: 100 } ],
			"implicit-arrow-linebreak": 0,
			"import/no-unresolved": [
				"error",
				{
					ignore: [ "^@wordpress/" ],
				},
			],
			"import/no-extraneous-dependencies": "off",
		},
	},
];
