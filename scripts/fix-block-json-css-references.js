#!/usr/bin/env node

/* eslint-disable no-console */

/**
 * Fix Block JSON CSS References
 *
 * This script fixes the issue where block.json files reference .scss files
 * that don't exist in the build directory. After webpack compilation, only
 * .css files exist, so we need to update the references.
 *
 * @package
 * @version 1.3.13
 */

const fs = require( 'fs' );
const path = require( 'path' );

const BLOCKS_DIR = path.join( __dirname, '..', 'build', 'blocks' );

/**
 * Update block.json to reference .css files instead of .scss files
 *
 * @param {string} blockDir - Path to block directory
 * @return {boolean} True if block.json was modified
 */
function fixBlockJsonCssReferences( blockDir ) {
	const blockJsonPath = path.join( blockDir, 'block.json' );

	if ( ! fs.existsSync( blockJsonPath ) ) {
		return false;
	}

	const blockJson = JSON.parse( fs.readFileSync( blockJsonPath, 'utf8' ) );
	let modified = false;

	// Fix editorStyle: file:./editor.scss → file:./index.css
	if ( blockJson.editorStyle === 'file:./editor.scss' ) {
		blockJson.editorStyle = 'file:./index.css';
		modified = true;
		console.log(
			`  ✓ Fixed editorStyle: file:./editor.scss → file:./index.css`
		);
	}

	// Fix style: file:./style.scss → file:./index.css
	if ( blockJson.style === 'file:./style.scss' ) {
		blockJson.style = 'file:./index.css';
		modified = true;
		console.log( `  ✓ Fixed style: file:./style.scss → file:./index.css` );
	}

	if ( modified ) {
		fs.writeFileSync(
			blockJsonPath,
			JSON.stringify( blockJson, null, 2 ) + '\n',
			'utf8'
		);
		return true;
	}

	return false;
}

/**
 * Main function
 */
function main() {
	console.log( '\n🔧 Fixing Block JSON CSS References...\n' );

	if ( ! fs.existsSync( BLOCKS_DIR ) ) {
		console.error( `❌ Build directory not found: ${ BLOCKS_DIR }` );
		console.error( '   Run "npm run build" first.\n' );
		process.exit( 1 );
	}

	const blockDirs = fs
		.readdirSync( BLOCKS_DIR, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isDirectory() )
		.map( ( dirent ) => path.join( BLOCKS_DIR, dirent.name ) );

	let fixedCount = 0;

	blockDirs.forEach( ( blockDir ) => {
		const blockName = path.basename( blockDir );
		console.log( `📦 Processing block: ${ blockName }` );

		if ( fixBlockJsonCssReferences( blockDir ) ) {
			fixedCount++;
		} else {
			console.log( `  → Already fixed or no changes needed` );
		}
	} );

	console.log( `\n✅ Fixed ${ fixedCount } block.json files\n` );
}

// Run
main();
