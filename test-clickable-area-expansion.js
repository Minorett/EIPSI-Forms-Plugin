#!/usr/bin/env node

/**
 * VALIDATION TEST: Clickable Area Expansion for Likert & Multiple Choice
 *
 * Tests that the HTML structure uses proper <label> wrapping for expanded clickable areas
 * and that styles ensure WCAG AA minimum touch target size (44x44px)
 */

const fs = require( 'fs' );
const path = require( 'path' );

const COLORS = {
	reset: '\x1b[0m',
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

function log( color, symbol, message ) {
	console.log( `${ color }${ symbol }${ COLORS.reset } ${ message }` );
}

function pass( message ) {
	log( COLORS.green, '✓', message );
}

function fail( message ) {
	log( COLORS.red, '✗', message );
}

function info( message ) {
	log( COLORS.blue, 'ℹ', message );
}

function section( message ) {
	console.log( `\n${ COLORS.cyan }▶ ${ message }${ COLORS.reset }` );
}

let totalTests = 0;
let passedTests = 0;

function test( description, testFn ) {
	totalTests++;
	try {
		testFn();
		pass( description );
		passedTests++;
		return true;
	} catch ( error ) {
		fail( `${ description }: ${ error.message }` );
		return false;
	}
}

function assert( condition, message ) {
	if ( ! condition ) {
		throw new Error( message );
	}
}

// ============================================================================
// TEST SUITE
// ============================================================================

section( 'Testing Likert Block (campo-likert)' );

// Test Likert edit.js
const likertEditPath = path.join(
	__dirname,
	'src/blocks/campo-likert/edit.js'
);
const likertEditContent = fs.readFileSync( likertEditPath, 'utf8' );

test( 'Likert edit.js: Uses <label> with className="likert-label-wrapper"', () => {
	assert(
		likertEditContent.includes( '<label' ),
		'No <label> element found'
	);
	assert(
		likertEditContent.includes( 'className="likert-label-wrapper"' ),
		'Label does not have className="likert-label-wrapper"'
	);
} );

test( 'Likert edit.js: Input is inside the label element', () => {
	const labelMatch = likertEditContent.match(
		/<label[^>]*className="likert-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="radio"/
	);
	assert(
		labelMatch,
		'Input is not nested inside label with className="likert-label-wrapper"'
	);
} );

test( 'Likert edit.js: Uses <span className="likert-label-text"> for text', () => {
	assert(
		likertEditContent.includes( '<span className="likert-label-text">' ),
		'No span with className="likert-label-text" found'
	);
} );

test( 'Likert edit.js: Label wraps both input and text span', () => {
	const pattern =
		/<label[^>]*className="likert-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="radio"[\s\S]*?<span className="likert-label-text">/;
	assert(
		pattern.test( likertEditContent ),
		'Label does not properly wrap both input and text span'
	);
} );

// Test Likert save.js
const likertSavePath = path.join(
	__dirname,
	'src/blocks/campo-likert/save.js'
);
const likertSaveContent = fs.readFileSync( likertSavePath, 'utf8' );

test( 'Likert save.js: Uses <label> with className="likert-label-wrapper"', () => {
	assert(
		likertSaveContent.includes( '<label' ),
		'No <label> element found'
	);
	assert(
		likertSaveContent.includes( 'className="likert-label-wrapper"' ),
		'Label does not have className="likert-label-wrapper"'
	);
} );

test( 'Likert save.js: Input is inside the label element', () => {
	const labelMatch = likertSaveContent.match(
		/<label[^>]*className="likert-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="radio"/
	);
	assert(
		labelMatch,
		'Input is not nested inside label with className="likert-label-wrapper"'
	);
} );

test( 'Likert save.js: Uses <span className="likert-label-text"> for text', () => {
	assert(
		likertSaveContent.includes( '<span className="likert-label-text">' ),
		'No span with className="likert-label-text" found'
	);
} );

// Test Likert styles
const likertStylePath = path.join(
	__dirname,
	'src/blocks/campo-likert/style.scss'
);
const likertStyleContent = fs.readFileSync( likertStylePath, 'utf8' );

test( 'Likert style.scss: Has .likert-label-wrapper styles', () => {
	assert(
		likertStyleContent.includes( '.likert-label-wrapper' ),
		'No .likert-label-wrapper styles found'
	);
} );

test( 'Likert style.scss: Has .likert-label-text styles', () => {
	assert(
		likertStyleContent.includes( '.likert-label-text' ),
		'No .likert-label-text styles found'
	);
} );

test( 'Likert style.scss: Hides input with position: absolute; opacity: 0', () => {
	const hiddenInputPattern =
		/input\[type="radio"\][^}]*{[^}]*position:\s*absolute[^}]*opacity:\s*0/s;
	assert(
		hiddenInputPattern.test( likertStyleContent ),
		'Input is not properly hidden with position: absolute and opacity: 0'
	);
} );

test( 'Likert style.scss: Creates visual indicator with ::before pseudo-element', () => {
	assert(
		likertStyleContent.includes( '&::before' ),
		'No ::before pseudo-element found for visual indicator'
	);
	assert(
		/border-radius:\s*50%/.test( likertStyleContent ),
		'No circular indicator (border-radius: 50%) found'
	);
} );

test( 'Likert style.scss: Has checked state styling', () => {
	assert(
		likertStyleContent.includes( ':checked' ),
		'No :checked state styling found'
	);
} );

section( 'Testing Multiple Choice Block (campo-multiple)' );

// Test Multiple Choice edit.js
const multipleEditPath = path.join(
	__dirname,
	'src/blocks/campo-multiple/edit.js'
);
const multipleEditContent = fs.readFileSync( multipleEditPath, 'utf8' );

test( 'Multiple Choice edit.js: Uses <label> with className="checkbox-label-wrapper"', () => {
	assert(
		multipleEditContent.includes( '<label' ),
		'No <label> element found'
	);
	assert(
		multipleEditContent.includes( 'className="checkbox-label-wrapper"' ),
		'Label does not have className="checkbox-label-wrapper"'
	);
} );

test( 'Multiple Choice edit.js: Input is inside the label element', () => {
	const labelMatch = multipleEditContent.match(
		/<label[^>]*className="checkbox-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="checkbox"/
	);
	assert(
		labelMatch,
		'Input is not nested inside label with className="checkbox-label-wrapper"'
	);
} );

test( 'Multiple Choice edit.js: Uses <span className="checkbox-label-text"> for text', () => {
	assert(
		multipleEditContent.includes(
			'<span className="checkbox-label-text">'
		),
		'No span with className="checkbox-label-text" found'
	);
} );

test( 'Multiple Choice edit.js: Label wraps both input and text span', () => {
	const pattern =
		/<label[^>]*className="checkbox-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="checkbox"[\s\S]*?<span className="checkbox-label-text">/;
	assert(
		pattern.test( multipleEditContent ),
		'Label does not properly wrap both input and text span'
	);
} );

// Test Multiple Choice save.js
const multipleSavePath = path.join(
	__dirname,
	'src/blocks/campo-multiple/save.js'
);
const multipleSaveContent = fs.readFileSync( multipleSavePath, 'utf8' );

test( 'Multiple Choice save.js: Uses <label> with className="checkbox-label-wrapper"', () => {
	assert(
		multipleSaveContent.includes( '<label' ),
		'No <label> element found'
	);
	assert(
		multipleSaveContent.includes( 'className="checkbox-label-wrapper"' ),
		'Label does not have className="checkbox-label-wrapper"'
	);
} );

test( 'Multiple Choice save.js: Input is inside the label element', () => {
	const labelMatch = multipleSaveContent.match(
		/<label[^>]*className="checkbox-label-wrapper"[^>]*>[\s\S]*?<input[^>]*type="checkbox"/
	);
	assert(
		labelMatch,
		'Input is not nested inside label with className="checkbox-label-wrapper"'
	);
} );

test( 'Multiple Choice save.js: Uses <span className="checkbox-label-text"> for text', () => {
	assert(
		multipleSaveContent.includes(
			'<span className="checkbox-label-text">'
		),
		'No span with className="checkbox-label-text" found'
	);
} );

// Test Multiple Choice styles
const multipleStylePath = path.join(
	__dirname,
	'src/blocks/campo-multiple/style.scss'
);
const multipleStyleContent = fs.readFileSync( multipleStylePath, 'utf8' );

test( 'Multiple Choice style.scss: Has .checkbox-label-wrapper styles', () => {
	assert(
		multipleStyleContent.includes( '.checkbox-label-wrapper' ),
		'No .checkbox-label-wrapper styles found'
	);
} );

test( 'Multiple Choice style.scss: Has .checkbox-label-text styles', () => {
	assert(
		multipleStyleContent.includes( '.checkbox-label-text' ),
		'No .checkbox-label-text styles found'
	);
} );

test( 'Multiple Choice style.scss: Hides input with position: absolute; opacity: 0', () => {
	const hiddenInputPattern =
		/input\[type="checkbox"\][^}]*{[^}]*position:\s*absolute[^}]*opacity:\s*0/s;
	assert(
		hiddenInputPattern.test( multipleStyleContent ),
		'Input is not properly hidden with position: absolute and opacity: 0'
	);
} );

test( 'Multiple Choice style.scss: Creates visual indicator with ::before pseudo-element', () => {
	assert(
		multipleStyleContent.includes( '&::before' ),
		'No ::before pseudo-element found for visual indicator'
	);
	assert(
		/border-radius:\s*4px/.test( multipleStyleContent ),
		'No square indicator (border-radius: 4px) found'
	);
} );

test( 'Multiple Choice style.scss: Has checked state styling', () => {
	assert(
		multipleStyleContent.includes( ':checked' ),
		'No :checked state styling found'
	);
} );

test( 'Multiple Choice style.scss: Has checkmark (::after) for checked state', () => {
	assert(
		multipleStyleContent.includes( '&::after' ),
		'No ::after pseudo-element found for checkmark'
	);
	const checkmarkPattern = /&::after[\s\S]*?rotate\(45deg\)/;
	assert(
		checkmarkPattern.test( multipleStyleContent ),
		'No checkmark styling found (border with 45deg rotation)'
	);
} );

test( 'Multiple Choice style.scss: Ensures minimum touch target (min-height: 44px)', () => {
	assert(
		/min-height:\s*44px/.test( multipleStyleContent ),
		'No min-height: 44px found for WCAG AA compliance'
	);
} );

section( 'Testing Accessibility & UX' );

test( 'Likert: Has hover state for better UX', () => {
	assert( likertStyleContent.includes( ':hover' ), 'No hover state found' );
} );

test( 'Likert: Has focus-within for keyboard navigation', () => {
	assert(
		likertStyleContent.includes( ':focus-within' ),
		'No focus-within state found for keyboard navigation'
	);
} );

test( 'Multiple Choice: Has hover state for better UX', () => {
	assert( multipleStyleContent.includes( ':hover' ), 'No hover state found' );
} );

test( 'Multiple Choice: Has focus-within for keyboard navigation', () => {
	assert(
		multipleStyleContent.includes( ':focus-within' ),
		'No focus-within state found for keyboard navigation'
	);
} );

test( 'Likert: Uses :has() for modern CSS container queries', () => {
	assert(
		likertStyleContent.includes( ':has(' ),
		'No :has() pseudo-class found for modern CSS'
	);
} );

test( 'Multiple Choice: Uses :has() for modern CSS container queries', () => {
	assert(
		multipleStyleContent.includes( ':has(' ),
		'No :has() pseudo-class found for modern CSS'
	);
} );

// ============================================================================
// RESULTS
// ============================================================================

console.log( '\n' + '='.repeat( 70 ) );
if ( passedTests === totalTests ) {
	log(
		COLORS.green,
		'✓',
		`ALL TESTS PASSED: ${ passedTests }/${ totalTests }`
	);
	console.log( '='.repeat( 70 ) );
	process.exit( 0 );
} else {
	log(
		COLORS.red,
		'✗',
		`TESTS FAILED: ${ passedTests }/${ totalTests } passed`
	);
	console.log( '='.repeat( 70 ) );
	process.exit( 1 );
}
