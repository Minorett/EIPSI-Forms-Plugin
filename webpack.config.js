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
const fs = require( 'fs' );
const path = require( 'path' );

// Dynamically generate entry points for each block
function generateBlockEntries() {
    const blocksDir = './src/blocks';
    const entries = {};

    // ✅ CENTRAL ENTRY POINT - Standard WordPress architecture
    // Ensures all WordPress dependencies are available globally
    if (fs.existsSync('./src/index.js')) {
        entries['index'] = './src/index.js';
    }

    // Individual block entry points
    // Each block can import what it needs; duplicates are de-duped by webpack
    if (fs.existsSync(blocksDir)) {
        const blockFolders = fs.readdirSync(blocksDir, { withFileTypes: true })
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);

        blockFolders.forEach(blockName => {
            const blockPath = `./src/blocks/${blockName}/index.js`;
            if (fs.existsSync(blockPath)) {
                // IMPORTANT: use /index as entry name so default WP Scripts output
                // generates build/blocks/<block>/index.js (matching block.json file:./index.js)
                entries[`blocks/${blockName}/index`] = blockPath;
            }
        });
    }

    return entries;
}

module.exports = {
    ...defaultConfig,
    performance: {
        ...( defaultConfig.performance || {} ),
        hints: false,
    },
    // Generate individual entry points for each block
    entry: generateBlockEntries(),
    // Enable aggressive tree-shaking and dead code elimination
    optimization: {
        ...( defaultConfig.optimization || {} ),
        usedExports: true,
        sideEffects: false,
        minimize: true,
        splitChunks: {
            chunks: 'all',
            minSize: 20000,  // ← Only split chunks > 20KB
            cacheGroups: {
                // ✅ WORDPRESS - Priority 10 (highest)
                // Shared WordPress deps extracted to single chunk
                wordpress: {
                    test: /[\\/]node_modules[\\/]@wordpress[\\/]/,
                    name: 'wordpress',
                    priority: 10,
                    reuseExistingChunk: true,
                    enforce: true,  // ← Force extraction
                },
                // ✅ VENDORS - Priority 5 (medium)
                // Other node_modules extracted separately
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    priority: 5,
                    reuseExistingChunk: true,
                    enforce: true,  // ← Force extraction
                },
                // ✅ COMMON - Priority 0 (lowest)
                // Code shared between 2+ entry points
                // IMPORTANT: minChunks: 2 ensures no orphan code
                common: {
                    minChunks: 2,  // Only extract if used in 2+ chunks
                    priority: 0,
                    reuseExistingChunk: true,
                    minSize: 0,  // ← Even small shared code
                },
            },
        },
    },
};
