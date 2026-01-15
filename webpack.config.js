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
    const entries = {
        // Base entry point for frontend functionality
        index: './src/index.js',
    };

    // Add individual entries for each block
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
    // Generate individual entry points for each block + main bundle
    entry: generateBlockEntries(),
};
