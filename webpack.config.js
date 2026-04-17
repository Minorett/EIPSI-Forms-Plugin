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

// v2.5.2 - FIX: Webpack 4 needs explicit CSS/SCSS loaders
// Extract CSS loader config from default or define explicitly
const getCSSLoaders = () => {
    // Try to find existing CSS rules in default config
    const defaultRules = defaultConfig.module?.rules || [];
    const cssRule = defaultRules.find(r => r.test?.toString().includes('css') || r.test?.toString().includes('scss'));
    
    if (cssRule) {
        return defaultRules;
    }
    
    // Fallback: define CSS/SCSS loaders explicitly for webpack 4
    return [
        {
            test: /\.css$/,
            use: [
                'style-loader',
                {
                    loader: 'css-loader',
                    options: { importLoaders: 1 }
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        postcssOptions: {
                            plugins: [
                                require('autoprefixer')
                            ]
                        }
                    }
                }
            ]
        },
        {
            test: /\.scss$/,
            use: [
                'style-loader',
                {
                    loader: 'css-loader',
                    options: { importLoaders: 2 }
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        postcssOptions: {
                            plugins: [
                                require('autoprefixer')
                            ]
                        }
                    }
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sassOptions: {
                            outputStyle: 'compressed',
                            quietDeps: true
                        }
                    }
                }
            ]
        },
        // Include all other default rules (JS, images, fonts, etc.)
        ...defaultRules.filter(r => !r.test?.toString().includes('css') && !r.test?.toString().includes('scss'))
    ];
};

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
    // v2.5.2 - FIX: Explicit CSS/SCSS loaders for webpack 4 compatibility
    module: {
        ...(defaultConfig.module || {}),
        rules: getCSSLoaders()
    },
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
