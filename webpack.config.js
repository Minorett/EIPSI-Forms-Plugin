/* eslint-disable */
/**
 * Custom webpack config for @wordpress/scripts.
 *
 * We keep WordPress defaults, but disable performance hints so `npm run build`
 * stays warning-free (CI-friendly) while we continue to optimize bundle size.
 *
 * Also configures entry points to generate both base files and individual blocks.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    performance: {
        ...( defaultConfig.performance || {} ),
        hints: false,
    },
    // Ensure base entry point generates index.js, index.css, and style-index.css
    entry: {
        index: './src/index.js',
    },
};
