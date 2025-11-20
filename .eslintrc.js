module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	overrides: [
		{
			// Test files - allow console and relax JSDoc requirements
			files: [
				'test-*.js',
				'*-validation.js',
				'*-audit.js',
				'*.test.js',
				'**/__tests__/**/*.js',
			],
			rules: {
				'no-console': 'off',
				'no-unused-vars': 'off',
				'jsdoc/require-param-type': 'off',
				'jsdoc/require-param-description': 'off',
				'no-bitwise': 'off',
				'no-shadow': 'off',
				'no-nested-ternary': 'off',
			},
		},
	],
};
