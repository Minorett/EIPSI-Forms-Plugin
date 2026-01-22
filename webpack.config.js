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

    // Add individual entries for each block
    // NOTE: We do NOT include a main index entry point here because:
    // 1. WordPress already enqueues frontend scripts from assets/js directly
    // 2. Gutenberg blocks are loaded via their individual entry points
    // 3. Including both causes orphan modules (duplicate code never used)
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
            cacheGroups: {
                // Extract common @wordpress dependencies
                wordpress: {
                    test: /[\\/]node_modules[\\/]@wordpress[\\/]/,
                    name: 'wordpress',
                    priority: 10,
                    reuseExistingChunk: true,
                },
                // Extract other node_modules
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    priority: 5,
                    reuseExistingChunk: true,
                },
                // Extract common EIPSI components
                common: {
                    minChunks: 2,
                    priority: 0,
                    reuseExistingChunk: true,
                },
            },
        },
    },
};
