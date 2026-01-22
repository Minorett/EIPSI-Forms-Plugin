/**
 * EIPSI Forms - Central Entry Point
 *
 * This is the main entry point for the EIPSI Forms plugin.
 * It imports and registers all WordPress dependencies and blocks.
 *
 * webpack entry: src/index.js → build/index.js
 */

// Import WordPress dependencies
import '@wordpress/blocks';
import '@wordpress/block-editor';
import '@wordpress/components';
import '@wordpress/element';
import '@wordpress/i18n';

// Import global styles
import './style.scss';
import './editor.scss';

// NOTE: Individual blocks are loaded via their own entry points (src/blocks/*/index.js)
// This central entry point ensures all WordPress dependencies are available globally.
// Block registration happens in each block's index.js file.

// Optionally: Add global initialization or shared utilities here
if ( process.env.NODE_ENV === 'development' ) {
	// console.log('EIPSI Forms loaded');
}
