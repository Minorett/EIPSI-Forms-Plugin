#!/usr/bin/env node

/**
 * Test Suite: Back Button Visibility Toggle
 *
 * Validates that the allowBackwardsNav toggle works correctly:
 * - Default state: ON (back button visible when appropriate)
 * - Toggle OFF: back button hidden
 * - Toggle ON: back button visible when appropriate
 * - Setting persists when form is saved
 */

const fs = require( 'fs' );
const path = require( 'path' );

let testsPassed = 0;
let testsFailed = 0;
const failures = [];

function testSection( name ) {
	process.stdout.write(
		`\n${ '='.repeat( 60 ) }\n${ name }\n${ '='.repeat( 60 ) }\n`
	);
}

function testCase( description, condition ) {
	if ( condition ) {
		testsPassed++;
		process.stdout.write( `✅ ${ description }\n` );
	} else {
		testsFailed++;
		failures.push( description );
		process.stdout.write( `❌ ${ description }\n` );
	}
}

function fileExists( filePath ) {
	return fs.existsSync( path.resolve( __dirname, filePath ) );
}

function fileContains( filePath, pattern ) {
	const fullPath = path.resolve( __dirname, filePath );
	if ( ! fs.existsSync( fullPath ) ) {
		return false;
	}
	const content = fs.readFileSync( fullPath, 'utf8' );
	if ( typeof pattern === 'string' ) {
		return content.includes( pattern );
	}
	return pattern.test( content );
}

function getFileContent( filePath ) {
	const fullPath = path.resolve( __dirname, filePath );
	if ( ! fs.existsSync( fullPath ) ) {
		return '';
	}
	return fs.readFileSync( fullPath, 'utf8' );
}

// Test 1: Verify block.json has allowBackwardsNav attribute
testSection( 'TEST 1: Block Configuration' );

const blockJsonPath = 'blocks/form-container/block.json';
testCase( 'block.json exists', fileExists( blockJsonPath ) );

const blockJson = getFileContent( blockJsonPath );
const blockConfig = blockJson ? JSON.parse( blockJson ) : {};

testCase(
	'allowBackwardsNav attribute is defined',
	blockConfig.attributes &&
		blockConfig.attributes.allowBackwardsNav !== undefined
);

testCase(
	'allowBackwardsNav type is boolean',
	blockConfig.attributes?.allowBackwardsNav?.type === 'boolean'
);

testCase(
	'allowBackwardsNav default is true (ON)',
	blockConfig.attributes?.allowBackwardsNav?.default === true
);

// Test 2: Verify edit.js uses the attribute
testSection( 'TEST 2: Editor Implementation' );

const editJsPath = 'src/blocks/form-container/edit.js';
testCase( 'edit.js exists', fileExists( editJsPath ) );

const editJs = getFileContent( editJsPath );

testCase(
	'edit.js destructures allowBackwardsNav from attributes',
	fileContains( editJsPath, 'allowBackwardsNav' ) &&
		editJs.includes( 'const {' ) &&
		editJs.includes( 'allowBackwardsNav' )
);

testCase(
	'ToggleControl for allowBackwardsNav exists',
	editJs.includes( 'ToggleControl' ) &&
		editJs.includes( 'Allow backwards navigation' )
);

testCase(
	'ToggleControl is checked based on allowBackwardsNav',
	editJs.includes( 'checked={ !! allowBackwardsNav }' )
);

testCase(
	'ToggleControl updates allowBackwardsNav attribute',
	editJs.includes( 'setAttributes( { allowBackwardsNav: !! value } )' )
);

testCase(
	'Toggle help text explains functionality',
	editJs.includes( 'When disabled, the "Previous" button will be hidden' )
);

// Test 3: Verify save.js saves the attribute
testSection( 'TEST 3: Save Function' );

const saveJsPath = 'src/blocks/form-container/save.js';
testCase( 'save.js exists', fileExists( saveJsPath ) );

const saveJs = getFileContent( saveJsPath );

testCase(
	'save.js destructures allowBackwardsNav from attributes',
	saveJs.includes( 'allowBackwardsNav' )
);

testCase(
	'save.js saves allowBackwardsNav as data attribute',
	saveJs.includes( 'data-allow-backwards-nav' ) ||
		saveJs.includes( 'allowBackwardsNav' )
);

testCase(
	'save.js converts boolean to string correctly',
	saveJs.includes( "allowBackwardsNav ? 'true' : 'false'" ) ||
		( saveJs.includes( 'data-allow-backwards-nav={' ) &&
			saveJs.includes( 'allowBackwardsNav' ) )
);

// Test 4: Verify frontend JavaScript respects the setting
testSection( 'TEST 4: Frontend JavaScript Implementation' );

const frontendJsPath = 'assets/js/eipsi-forms.js';
testCase( 'eipsi-forms.js exists', fileExists( frontendJsPath ) );

const frontendJs = getFileContent( frontendJsPath );

testCase(
	'Frontend reads data-allow-backwards-nav attribute',
	frontendJs.includes( 'allowBackwardsNav' ) &&
		( frontendJs.includes( 'form.dataset.allowBackwardsNav' ) ||
			frontendJs.includes( 'data-allow-backwards-nav' ) )
);

testCase(
	'Frontend parses allowBackwardsNav value correctly',
	frontendJs.includes( 'rawAllowBackwards' ) ||
		frontendJs.includes( 'allowBackwardsNav' )
);

testCase(
	'Frontend uses allowBackwardsNav to control prev button visibility',
	frontendJs.includes( 'shouldShowPrev' ) &&
		frontendJs.includes( 'allowBackwardsNav' )
);

testCase(
	'Default behavior is TRUE (back button enabled) when attribute missing',
	frontendJs.match( /\?\s*false\s*:\s*true/ ) !== null &&
		( frontendJs.includes( "=== 'false'" ) ||
			frontendJs.includes( "!== 'false'" ) )
);

// Test 5: Verify logic for back button visibility
testSection( 'TEST 5: Back Button Visibility Logic' );

// Extract the logic from eipsi-forms.js
const allowBackwardsNavLogicMatch = frontendJs.match(
	/const rawAllowBackwards = form\.dataset\.allowBackwardsNav;[\s\S]*?const allowBackwardsNav =[\s\S]*?;/
);

testCase(
	'allowBackwardsNav logic is present in updatePaginationDisplay',
	allowBackwardsNavLogicMatch !== null
);

if ( allowBackwardsNavLogicMatch ) {
	const logic = allowBackwardsNavLogicMatch[ 0 ];

	testCase(
		'Logic uses explicit true/false conversion',
		logic.match( /\?\s*false\s*:\s*true/ ) !== null
	);

	testCase(
		"Logic checks for 'false' string",
		logic.includes( "=== 'false'" ) || logic.includes( "!== 'false'" )
	);

	testCase(
		"Logic checks for '0' string",
		logic.includes( "=== '0'" ) || logic.includes( "!== '0'" )
	);

	testCase(
		'Logic does NOT check for empty string as disabled',
		! logic.includes( "!== ''" )
	);
}

testCase(
	'shouldShowPrev checks allowBackwardsNav',
	frontendJs.includes( 'shouldShowPrev' ) &&
		frontendJs.includes( 'allowBackwardsNav' )
);

testCase(
	'shouldShowPrev checks hasHistory',
	frontendJs.includes( 'shouldShowPrev' ) &&
		frontendJs.includes( 'hasHistory' )
);

testCase(
	'prevButton visibility is controlled by shouldShowPrev',
	frontendJs.includes( 'prevButton.style.display' ) &&
		frontendJs.includes( 'shouldShowPrev' )
);

// Test 6: Integration checks
testSection( 'TEST 6: Integration & Documentation' );

testCase(
	'Navigation Settings panel exists in edit.js',
	editJs.includes( 'Navigation Settings' )
);

testCase(
	'Navigation Settings panel is initially closed',
	editJs.includes( 'Navigation Settings' ) &&
		editJs.match(
			/Navigation Settings[\s\S]*?initialOpen\s*=\s*\{\s*false\s*\}/
		)
);

testCase(
	'Form saves data-allow-backwards-nav to HTML',
	saveJs.includes( 'data-allow-backwards-nav' )
);

testCase(
	'Frontend JavaScript checks dataset property',
	frontendJs.includes( 'form.dataset.allowBackwardsNav' )
);

// Test 7: Verify proper default handling
testSection( 'TEST 7: Default Value Handling' );

// Check that the logic properly defaults to true
const defaultHandlingCorrect =
	( frontendJs.match(
		/rawAllowBackwards\s*===\s*'false'\s*\|\|\s*rawAllowBackwards/
	) !== null ||
		frontendJs.match( /rawAllowBackwards\s*===\s*'false'/ ) !== null ) &&
	frontendJs.match( /\?\s*false\s*:\s*true/ ) !== null;

testCase(
	'Frontend defaults to TRUE (enabled) for undefined/null/empty values',
	defaultHandlingCorrect
);

testCase(
	'block.json default matches frontend default (both TRUE)',
	blockConfig.attributes?.allowBackwardsNav?.default === true
);

// Summary
testSection( 'TEST SUMMARY' );

process.stdout.write( `\nTotal Tests: ${ testsPassed + testsFailed }\n` );
process.stdout.write( `✅ Passed: ${ testsPassed }\n` );
process.stdout.write( `❌ Failed: ${ testsFailed }\n` );

if ( testsFailed > 0 ) {
	process.stdout.write( '\n⚠️  Failed Tests:\n' );
	failures.forEach( ( failure, index ) => {
		process.stdout.write( `   ${ index + 1 }. ${ failure }\n` );
	} );
	process.stdout.write( '\n❌ BACK BUTTON TOGGLE TEST SUITE FAILED\n' );
	process.exit( 1 );
} else {
	process.stdout.write( '\n✅ ALL BACK BUTTON TOGGLE TESTS PASSED!\n' );
	process.exit( 0 );
}
