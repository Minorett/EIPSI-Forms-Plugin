/**
 * ESLint Configuration
 *
 * @since 1.5.5
 */
module.exports = {
    root: true,
    extends: [
        'plugin:@wordpress/eslint-plugin/recommended'
    ],
    ignorePatterns: [
        'final-audit-v1.2.2.js',
        'test-*.js',
        '*-audit.js',
        '*-validation.js',
        'check-*.js',
        'stress-test*.js',
        'E2E_TEST_REPORT_v1.2.2.md',
        'QA_VALIDATION_v1.2.2_REPORT.md',
        'FINAL_AUDIT_v1.2.2_REPORT.md',
        'build/**',
        'node_modules/**',
        'assets/**',
        'wordpress/**',
    ],
    rules: {
        '@wordpress/no-unused-vars-before-return': 'off',
        'react-hooks/exhaustive-deps': 'off',
        'react-hooks/rules-of-hooks': 'off',
        'no-unused-vars': 'warn',
    },
    overrides: [
        {
            // Test files - allow console and relax JSDoc requirements
            files: [
                'test-*.js',
                'scripts/test-*.js',
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
        {
            // Email Log - allow alert/confirm for user confirmations
            files: ['admin/js/email-log.js'],
            rules: {
                'no-alert': 'off',
            },
        },
    ],
    globals: {
        'jQuery': 'readonly',
        '$': 'readonly',
        'wp': 'readonly',
        'ajaxurl': 'readonly',
        'eipsi': 'readonly',
        'eipsiWavesManager': 'readonly',
        'eipsiAdminConfig': 'readonly',
        'eipsiEditorData': 'readonly',
        'eipsiAuth': 'readonly',
        'eipsiFormsConfig': 'readonly',
        'eipsiTrackingConfig': 'readonly',
        'eipsiRandomization': 'readonly',
        'eipsiWizard': 'readonly',
        'eipsiStudyDash': 'readonly',
        'eipsiConfigL10n': 'readonly',
        'eipsiWavesManagerData': 'readonly',
    },
    env: {
        'browser': true,
        'node': true,
        'jquery': true,
    }
};
