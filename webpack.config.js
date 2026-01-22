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
    //
    // IMPORTANT (WordPress/Gutenberg reality):
    // Gutenberg enqueues each block entry script individually via block.json.
    // If we generate extra shared chunks (splitChunks), WordPress does NOT know
    // it must also enqueue those chunk files — resulting in blocks that simply
    // never register in the editor ("Tu sitio no es compatible con el bloque...").
    //
    // So we keep bundles self-contained per entry point.
    optimization: {
        ...( defaultConfig.optimization || {} ),
        usedExports: true,
        sideEffects: false,
        minimize: true,
        splitChunks: false,
        runtimeChunk: false,
    },
};
