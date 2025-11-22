#!/usr/bin/env node

/**
 * Test Suite: Clickable Areas & WCAG Touch Targets
 *
 * Validates that radio buttons, checkboxes (campo-multiple), and Likert scales
 * have enlarged clickable areas that meet WCAG 2.1 AA requirements (44×44px).
 *
 * Critical for: Mobile accessibility and usability in clinical research forms
 *
 * Changes tested:
 * - Removed pointer-events: none from inputs (was breaking accessibility)
 * - Added proper visually-hidden pattern (sr-only)
 * - Ensured label wrappers have min-height: 44px
 * - Ensured label wrappers have width: 100%
 * - Maintained keyboard focus and screen reader support
 */

const fs = require( 'fs' );
const path = require( 'path' );

// ANSI colors for terminal output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

const log = {
	header: ( msg ) =>
		console.log(
			`\n${ colors.bright }${ colors.blue }${ msg }${ colors.reset }`
		),
	success: ( msg ) =>
		console.log( `${ colors.green }✅ ${ msg }${ colors.reset }` ),
	error: ( msg ) =>
		console.log( `${ colors.red }❌ ${ msg }${ colors.reset }` ),
	warning: ( msg ) =>
		console.log( `${ colors.yellow }⚠️  ${ msg }${ colors.reset }` ),
	info: ( msg ) =>
		console.log( `${ colors.cyan }ℹ️  ${ msg }${ colors.reset }` ),
	detail: ( msg ) => console.log( `   ${ msg }` ),
};

class TestRunner {
	constructor() {
		this.tests = [];
		this.passed = 0;
		this.failed = 0;
	}

	test( name, fn ) {
		this.tests.push( { name, fn } );
	}

	async run() {
		log.header( '🧪 Clickable Areas & WCAG Touch Targets Test Suite' );
		log.info( `Running ${ this.tests.length } tests...\n` );

		for ( const { name, fn } of this.tests ) {
			try {
				await fn();
				log.success( name );
				this.passed++;
			} catch ( error ) {
				log.error( name );
				log.detail( colors.red + error.message + colors.reset );
				this.failed++;
			}
		}

		this.printSummary();
	}

	printSummary() {
		const total = this.passed + this.failed;
		const passRate = ( ( this.passed / total ) * 100 ).toFixed( 1 );

		log.header( '📊 Test Summary' );
		console.log( `Total Tests: ${ total }` );
		console.log(
			`${ colors.green }Passed: ${ this.passed }${ colors.reset }`
		);
		console.log(
			`${ colors.red }Failed: ${ this.failed }${ colors.reset }`
		);
		console.log( `Pass Rate: ${ passRate }%\n` );

		if ( this.failed === 0 ) {
			log.success( 'All tests passed! 🎉' );
		} else {
			log.error( `${ this.failed } test(s) failed.` );
			process.exit( 1 );
		}
	}
}

// Utility: Read file and check content
function readFile( filePath ) {
	return fs.readFileSync( filePath, 'utf8' );
}

function assertFileContains( filePath, searchString, errorMsg ) {
	const content = readFile( filePath );
	if ( ! content.includes( searchString ) ) {
		throw new Error(
			errorMsg || `File ${ filePath } does not contain: ${ searchString }`
		);
	}
}

function assertFileNotContains( filePath, searchString, errorMsg ) {
	const content = readFile( filePath );
	if ( content.includes( searchString ) ) {
		throw new Error(
			errorMsg ||
				`File ${ filePath } should not contain: ${ searchString }`
		);
	}
}

function assertCSSProperty( filePath, selector, property, expectedValue, errorMsg ) {
	const content = readFile( filePath );
	
	// Match selector and extract its properties
	const selectorRegex = new RegExp(
		`${ selector.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) }\\s*\\{([^}]+)\\}`,
		'g'
	);
	
	let match;
	let found = false;
	
	while ( ( match = selectorRegex.exec( content ) ) !== null ) {
		const properties = match[ 1 ];
		const propertyRegex = new RegExp(
			`${ property }\\s*:\\s*([^;]+)`,
			'i'
		);
		const propMatch = propertyRegex.exec( properties );
		
		if ( propMatch ) {
			found = true;
			const actualValue = propMatch[ 1 ].trim();
			if ( actualValue !== expectedValue ) {
				throw new Error(
					errorMsg ||
						`CSS property mismatch in ${ filePath }.\nSelector: ${ selector }\nProperty: ${ property }\nExpected: ${ expectedValue }\nActual: ${ actualValue }`
				);
			}
		}
	}
	
	if ( ! found ) {
		throw new Error(
			errorMsg ||
				`CSS property not found in ${ filePath }.\nSelector: ${ selector }\nProperty: ${ property }`
		);
	}
}

// Test Runner Instance
const runner = new TestRunner();

// ============================================================================
// TEST SUITE: CAMPO-RADIO (Radio Buttons)
// ============================================================================

runner.test( 'campo-radio/style.scss: Label wrapper has min-height 44px', () => {
	const filePath = '/home/engine/project/src/blocks/campo-radio/style.scss';
	assertFileContains(
		filePath,
		'min-height: 44px;',
		'Label wrapper should have min-height: 44px for WCAG AA compliance'
	);
} );

runner.test( 'campo-radio/style.scss: Label wrapper has width 100%', () => {
	const filePath = '/home/engine/project/src/blocks/campo-radio/style.scss';
	assertFileContains(
		filePath,
		'width: 100%;',
		'Label wrapper should have width: 100% for full clickable area'
	);
} );

runner.test( 'campo-radio/style.scss: Input does NOT have pointer-events none', () => {
	const filePath = '/home/engine/project/src/blocks/campo-radio/style.scss';
	assertFileNotContains(
		filePath,
		'pointer-events: none;',
		'Input should NOT have pointer-events: none (breaks accessibility)'
	);
} );

runner.test( 'campo-radio/style.scss: Input has proper visually-hidden pattern', () => {
	const filePath = '/home/engine/project/src/blocks/campo-radio/style.scss';
	const content = readFile( filePath );
	
	// Check for sr-only pattern components
	const hasClip = content.includes( 'clip: rect(0, 0, 0, 0);' );
	const hasOverflow = content.includes( 'overflow: hidden;' );
	const hasWhitespace = content.includes( 'white-space: nowrap;' );
	
	if ( ! hasClip || ! hasOverflow || ! hasWhitespace ) {
		throw new Error(
			'Input should use proper visually-hidden pattern (clip, overflow, white-space)'
		);
	}
} );

runner.test( 'campo-radio/save.js: Label wraps input correctly', () => {
	const filePath = '/home/engine/project/src/blocks/campo-radio/save.js';
	const content = readFile( filePath );
	
	// Check structure: <label htmlFor={id}><input id={id} /><span>
	const hasLabelWithHtmlFor = content.includes( 'htmlFor={ radioId }' );
	const hasInputWithId = content.includes( 'id={ radioId }' );
	const hasClassName = content.includes( 'className="radio-label-wrapper"' );
	
	if ( ! hasLabelWithHtmlFor || ! hasInputWithId || ! hasClassName ) {
		throw new Error(
			'Label should properly wrap input with htmlFor/id association'
		);
	}
} );

// ============================================================================
// TEST SUITE: CAMPO-MULTIPLE (Checkboxes)
// ============================================================================

runner.test( 'campo-multiple/style.scss: Label wrapper has min-height 44px', () => {
	const filePath = '/home/engine/project/src/blocks/campo-multiple/style.scss';
	assertFileContains(
		filePath,
		'min-height: 44px;',
		'Label wrapper should have min-height: 44px for WCAG AA compliance'
	);
} );

runner.test( 'campo-multiple/style.scss: Label wrapper has width 100%', () => {
	const filePath = '/home/engine/project/src/blocks/campo-multiple/style.scss';
	assertFileContains(
		filePath,
		'width: 100%;',
		'Label wrapper should have width: 100% for full clickable area'
	);
} );

runner.test( 'campo-multiple/style.scss: Input does NOT have pointer-events none', () => {
	const filePath = '/home/engine/project/src/blocks/campo-multiple/style.scss';
	assertFileNotContains(
		filePath,
		'pointer-events: none;',
		'Input should NOT have pointer-events: none (breaks accessibility)'
	);
} );

runner.test( 'campo-multiple/style.scss: Input has proper visually-hidden pattern', () => {
	const filePath = '/home/engine/project/src/blocks/campo-multiple/style.scss';
	const content = readFile( filePath );
	
	// Check for sr-only pattern components
	const hasClip = content.includes( 'clip: rect(0, 0, 0, 0);' );
	const hasOverflow = content.includes( 'overflow: hidden;' );
	const hasWhitespace = content.includes( 'white-space: nowrap;' );
	
	if ( ! hasClip || ! hasOverflow || ! hasWhitespace ) {
		throw new Error(
			'Input should use proper visually-hidden pattern (clip, overflow, white-space)'
		);
	}
} );

runner.test( 'campo-multiple/save.js: Label wraps input correctly', () => {
	const filePath = '/home/engine/project/src/blocks/campo-multiple/save.js';
	const content = readFile( filePath );
	
	// Check structure: <label htmlFor={id}><input id={id} /><span>
	const hasLabelWithHtmlFor = content.includes( 'htmlFor={ checkboxId }' );
	const hasInputWithId = content.includes( 'id={ checkboxId }' );
	const hasClassName = content.includes( 'className="checkbox-label-wrapper"' );
	
	if ( ! hasLabelWithHtmlFor || ! hasInputWithId || ! hasClassName ) {
		throw new Error(
			'Label should properly wrap input with htmlFor/id association'
		);
	}
} );

// ============================================================================
// TEST SUITE: CAMPO-LIKERT (Likert Scales)
// ============================================================================

runner.test( 'campo-likert/style.scss: Label wrapper has min-height 44px', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/style.scss';
	assertFileContains(
		filePath,
		'min-height: 44px;',
		'Label wrapper should have min-height: 44px for WCAG AA compliance'
	);
} );

runner.test( 'campo-likert/style.scss: Label wrapper has width 100%', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/style.scss';
	assertFileContains(
		filePath,
		'width: 100%;',
		'Label wrapper should have width: 100% for full clickable area'
	);
} );

runner.test( 'campo-likert/style.scss: Input does NOT have pointer-events none', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/style.scss';
	assertFileNotContains(
		filePath,
		'pointer-events: none;',
		'Input should NOT have pointer-events: none (breaks accessibility)'
	);
} );

runner.test( 'campo-likert/style.scss: Input has proper visually-hidden pattern', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/style.scss';
	const content = readFile( filePath );
	
	// Check for sr-only pattern components
	const hasClip = content.includes( 'clip: rect(0, 0, 0, 0);' );
	const hasOverflow = content.includes( 'overflow: hidden;' );
	const hasWhitespace = content.includes( 'white-space: nowrap;' );
	
	if ( ! hasClip || ! hasOverflow || ! hasWhitespace ) {
		throw new Error(
			'Input should use proper visually-hidden pattern (clip, overflow, white-space)'
		);
	}
} );

runner.test( 'campo-likert/save.js: Label wraps input correctly', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/save.js';
	const content = readFile( filePath );
	
	// Check structure: <label htmlFor={id}><input id={id} /><span>
	const hasLabelWithHtmlFor = content.includes( 'htmlFor={ optionId }' );
	const hasInputWithId = content.includes( 'id={ optionId }' );
	const hasClassName = content.includes( 'className="likert-label-wrapper"' );
	
	if ( ! hasLabelWithHtmlFor || ! hasInputWithId || ! hasClassName ) {
		throw new Error(
			'Label should properly wrap input with htmlFor/id association'
		);
	}
} );

runner.test( 'campo-likert/save.js: Numeric value is inside label wrapper', () => {
	const filePath = '/home/engine/project/src/blocks/campo-likert/save.js';
	const content = readFile( filePath );
	
	// Check that the span with label text is inside the label wrapper
	const labelWrapperPattern = /<label[^>]*className="likert-label-wrapper"[^>]*>[\s\S]*?<span className="likert-label-text">/;
	
	if ( ! labelWrapperPattern.test( content ) ) {
		throw new Error(
			'Likert label text should be inside the label wrapper for full clickable area'
		);
	}
} );

// ============================================================================
// TEST SUITE: BUILD OUTPUT (Compiled CSS)
// ============================================================================

runner.test( 'Build: Radio label wrapper compiled with min-height 44px', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Check minified CSS for .radio-label-wrapper with min-height
	if ( ! content.includes( '.radio-label-wrapper{' ) ) {
		throw new Error( 'Compiled CSS missing .radio-label-wrapper selector' );
	}
	
	if ( ! content.includes( 'min-height:44px' ) ) {
		throw new Error( 'Compiled CSS missing min-height:44px for radio labels' );
	}
} );

runner.test( 'Build: Checkbox label wrapper compiled with min-height 44px', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Check minified CSS for .checkbox-label-wrapper with min-height
	if ( ! content.includes( '.checkbox-label-wrapper{' ) ) {
		throw new Error( 'Compiled CSS missing .checkbox-label-wrapper selector' );
	}
	
	if ( ! content.includes( 'min-height:44px' ) ) {
		throw new Error( 'Compiled CSS missing min-height:44px for checkbox labels' );
	}
} );

runner.test( 'Build: Likert label wrapper compiled with min-height 44px', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Check minified CSS for .likert-label-wrapper with min-height
	if ( ! content.includes( '.likert-label-wrapper{' ) ) {
		throw new Error( 'Compiled CSS missing .likert-label-wrapper selector' );
	}
	
	if ( ! content.includes( 'min-height:44px' ) ) {
		throw new Error( 'Compiled CSS missing min-height:44px for likert labels' );
	}
} );

runner.test( 'Build: Radio input does NOT have pointer-events:none', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract radio input styles
	const radioInputPattern = /\.radio-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	const matches = content.match( radioInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing radio input styles' );
	}
	
	// Check that none of the matches contain pointer-events:none
	for ( const match of matches ) {
		if ( match.includes( 'pointer-events:none' ) ) {
			throw new Error( 'Compiled CSS should NOT have pointer-events:none for radio inputs' );
		}
	}
} );

runner.test( 'Build: Checkbox input does NOT have pointer-events:none', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract checkbox input styles
	const checkboxInputPattern = /\.checkbox-label-wrapper input\[type=checkbox\]\{[^}]+\}/g;
	const matches = content.match( checkboxInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing checkbox input styles' );
	}
	
	// Check that none of the matches contain pointer-events:none
	for ( const match of matches ) {
		if ( match.includes( 'pointer-events:none' ) ) {
			throw new Error( 'Compiled CSS should NOT have pointer-events:none for checkbox inputs' );
		}
	}
} );

runner.test( 'Build: Likert input does NOT have pointer-events:none', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract likert input styles
	const likertInputPattern = /\.likert-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	const matches = content.match( likertInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing likert input styles' );
	}
	
	// Check that none of the matches contain pointer-events:none
	for ( const match of matches ) {
		if ( match.includes( 'pointer-events:none' ) ) {
			throw new Error( 'Compiled CSS should NOT have pointer-events:none for likert inputs' );
		}
	}
} );

runner.test( 'Build: Radio input has visually-hidden properties', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract radio input styles
	const radioInputPattern = /\.radio-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	const matches = content.match( radioInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing radio input styles' );
	}
	
	const styles = matches.join( '' );
	
	// Check for sr-only pattern
	if ( ! styles.includes( 'clip:rect(0,0,0,0)' ) ) {
		throw new Error( 'Radio input should have clip:rect(0,0,0,0)' );
	}
	if ( ! styles.includes( 'overflow:hidden' ) ) {
		throw new Error( 'Radio input should have overflow:hidden' );
	}
	if ( ! styles.includes( 'white-space:nowrap' ) ) {
		throw new Error( 'Radio input should have white-space:nowrap' );
	}
} );

runner.test( 'Build: Checkbox input has visually-hidden properties', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract checkbox input styles
	const checkboxInputPattern = /\.checkbox-label-wrapper input\[type=checkbox\]\{[^}]+\}/g;
	const matches = content.match( checkboxInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing checkbox input styles' );
	}
	
	const styles = matches.join( '' );
	
	// Check for sr-only pattern
	if ( ! styles.includes( 'clip:rect(0,0,0,0)' ) ) {
		throw new Error( 'Checkbox input should have clip:rect(0,0,0,0)' );
	}
	if ( ! styles.includes( 'overflow:hidden' ) ) {
		throw new Error( 'Checkbox input should have overflow:hidden' );
	}
	if ( ! styles.includes( 'white-space:nowrap' ) ) {
		throw new Error( 'Checkbox input should have white-space:nowrap' );
	}
} );

runner.test( 'Build: Likert input has visually-hidden properties', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Extract likert input styles
	const likertInputPattern = /\.likert-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	const matches = content.match( likertInputPattern );
	
	if ( ! matches ) {
		throw new Error( 'Compiled CSS missing likert input styles' );
	}
	
	const styles = matches.join( '' );
	
	// Check for sr-only pattern
	if ( ! styles.includes( 'clip:rect(0,0,0,0)' ) ) {
		throw new Error( 'Likert input should have clip:rect(0,0,0,0)' );
	}
	if ( ! styles.includes( 'overflow:hidden' ) ) {
		throw new Error( 'Likert input should have overflow:hidden' );
	}
	if ( ! styles.includes( 'white-space:nowrap' ) ) {
		throw new Error( 'Likert input should have white-space:nowrap' );
	}
} );

// ============================================================================
// TEST SUITE: KEYBOARD ACCESSIBILITY
// ============================================================================

runner.test( 'All blocks: Focus styles are preserved', () => {
	const files = [
		'/home/engine/project/src/blocks/campo-radio/style.scss',
		'/home/engine/project/src/blocks/campo-multiple/style.scss',
		'/home/engine/project/src/blocks/campo-likert/style.scss',
	];
	
	for ( const filePath of files ) {
		const content = readFile( filePath );
		
		if ( ! content.includes( ':focus-within' ) && ! content.includes( ':focus' ) ) {
			throw new Error( `${ filePath } should have focus styles for keyboard navigation` );
		}
	}
} );

runner.test( 'All blocks: Inputs are not display:none or visibility:hidden', () => {
	const filePath = '/home/engine/project/build/style-index.css';
	const content = readFile( filePath );
	
	// Check that inputs are not hidden with display:none or visibility:hidden
	const radioPattern = /\.radio-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	const checkboxPattern = /\.checkbox-label-wrapper input\[type=checkbox\]\{[^}]+\}/g;
	const likertPattern = /\.likert-label-wrapper input\[type=radio\]\{[^}]+\}/g;
	
	const allMatches = [
		...( content.match( radioPattern ) || [] ),
		...( content.match( checkboxPattern ) || [] ),
		...( content.match( likertPattern ) || [] ),
	];
	
	for ( const match of allMatches ) {
		if ( match.includes( 'display:none' ) ) {
			throw new Error( 'Inputs should NOT use display:none (breaks accessibility)' );
		}
		if ( match.includes( 'visibility:hidden' ) ) {
			throw new Error( 'Inputs should NOT use visibility:hidden (breaks accessibility)' );
		}
	}
} );

// ============================================================================
// RUN ALL TESTS
// ============================================================================

runner.run().catch( ( err ) => {
	console.error( 'Test runner error:', err );
	process.exit( 1 );
} );
