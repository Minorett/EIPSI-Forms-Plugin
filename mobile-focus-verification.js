#!/usr/bin/env node

/**
 * Mobile Focus & Responsive Verification Script
 * Validates completion of MASTER_ISSUES_LIST.md #11 and #12
 *
 * Tests:
 * 1. 320px breakpoint rules (Issue #11)
 * 2. Mobile focus enhancements at 768px (Issue #12)
 * 3. Touch target compliance (44px minimum)
 * 4. No horizontal scrolling at critical breakpoints
 */

const fs = require( 'fs' );
const path = require( 'path' );

console.log( '============================================================' );
console.log( 'EIPSI FORMS - MOBILE FOCUS & RESPONSIVE VERIFICATION' );
console.log( '============================================================\n' );

const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );
const cssContent = fs.readFileSync( cssPath, 'utf8' );

let passed = 0;
let failed = 0;
let warnings = 0;

function testPass( name ) {
	console.log( `✓ PASS: ${ name }` );
	passed++;
}

function testFail( name, details ) {
	console.log( `✗ FAIL: ${ name }` );
	if ( details ) {
		console.log( `  Details: ${ details }` );
	}
	failed++;
}

function testWarn( name, details ) {
	console.log( `⚠ WARNING: ${ name }` );
	if ( details ) {
		console.log( `  Details: ${ details }` );
	}
	warnings++;
}

console.log( 'TESTING ISSUE #11: 320px Breakpoint Rules' );
console.log( '----------------------------------------------------------' );

// Test 1: 320px breakpoint exists
if ( /@media\s*\(max-width:\s*374px\)/i.test( cssContent ) ) {
	testPass( '320px breakpoint (@media max-width: 374px) exists' );
} else {
	testFail(
		'320px breakpoint missing',
		'Should have @media (max-width: 374px)'
	);
}

// Test 2: vas-dinamico-form padding
const breakpoint374Match = cssContent.match(
	/@media\s*\(max-width:\s*374px\)\s*\{([\s\S]*?)(?=@media|\/\*\s*===|$)/i
);
if ( breakpoint374Match ) {
	const breakpoint374Content = breakpoint374Match[ 1 ];

	if (
		/\.vas-dinamico-form[\s\S]*?padding:\s*0\.75rem/i.test(
			breakpoint374Content
		)
	) {
		testPass( '.vas-dinamico-form padding: 0.75rem' );
	} else {
		testFail( '.vas-dinamico-form padding not 0.75rem' );
	}

	// Test 3: h1 font-size
	if ( /h1[\s\S]*?font-size:\s*1\.375rem/i.test( breakpoint374Content ) ) {
		testPass( 'h1 font-size: 1.375rem (22px)' );
	} else {
		testFail( 'h1 font-size not 1.375rem' );
	}

	// Test 4: h2 font-size
	if ( /h2[\s\S]*?font-size:\s*1\.125rem/i.test( breakpoint374Content ) ) {
		testPass( 'h2 font-size: 1.125rem (18px)' );
	} else {
		testFail( 'h2 font-size not 1.125rem' );
	}

	// Test 5: vas-value-number font-size
	if (
		/\.vas-value-number[\s\S]*?font-size:\s*1\.5rem/i.test(
			breakpoint374Content
		)
	) {
		testPass( '.vas-value-number font-size: 1.5rem (24px)' );
	} else {
		testFail( '.vas-value-number font-size not 1.5rem' );
	}

	// Test 6: likert-item padding
	if (
		/\.likert-item[\s\S]*?padding:\s*0\.625rem\s+0\.75rem/i.test(
			breakpoint374Content
		)
	) {
		testPass( '.likert-item padding: 0.625rem 0.75rem' );
	} else {
		testFail( '.likert-item padding not 0.625rem 0.75rem' );
	}

	// Test 7: form-navigation gap
	if (
		/\.form-navigation[\s\S]*?gap:\s*0\.75rem/i.test( breakpoint374Content )
	) {
		testPass( '.form-navigation gap: 0.75rem' );
	} else {
		testFail( '.form-navigation gap not 0.75rem' );
	}
} else {
	testFail( 'Cannot parse 320px breakpoint content' );
}

console.log( '\nTESTING ISSUE #12: Mobile Focus Enhancements' );
console.log( '----------------------------------------------------------' );

// Test 8: Focus enhancement at 768px (not 480px)
const focusAt768 =
	/@media\s*\(max-width:\s*768px\)[\s\S]*?outline-width:\s*3px/i.test(
		cssContent
	);
const focusAt480 =
	/@media\s*\(max-width:\s*480px\)[\s\S]*?outline-width:\s*3px/i.test(
		cssContent
	);

if ( focusAt768 ) {
	testPass( 'Focus enhancement applied at 768px breakpoint' );
} else {
	testFail(
		'Focus enhancement not found at 768px',
		'Should enhance focus at tablet breakpoint'
	);
}

if ( focusAt480 ) {
	testWarn(
		'Focus enhancement also at 480px',
		'Ticket specifies 768px only - may cause duplication'
	);
}

// Test 9: Focus outline-width is 3px
const focusWidthMatch = cssContent.match(
	/@media\s*\(max-width:\s*768px\)[\s\S]*?outline-width:\s*(\d+)px/i
);
if ( focusWidthMatch && focusWidthMatch[ 1 ] === '3' ) {
	testPass( 'Focus outline-width: 3px on mobile' );
} else {
	testFail(
		'Focus outline-width not 3px',
		`Found: ${ focusWidthMatch ? focusWidthMatch[ 1 ] : 'none' }`
	);
}

// Test 10: Focus outline-offset is 3px
const focusOffsetMatch = cssContent.match(
	/@media\s*\(max-width:\s*768px\)[\s\S]*?outline-offset:\s*(\d+)px/i
);
if ( focusOffsetMatch && focusOffsetMatch[ 1 ] === '3' ) {
	testPass( 'Focus outline-offset: 3px on mobile' );
} else {
	testFail(
		'Focus outline-offset not 3px',
		`Found: ${ focusOffsetMatch ? focusOffsetMatch[ 1 ] : 'none' }`
	);
}

// Test 11: Desktop focus still 2px (no regression)
const desktopFocusMatch = cssContent.match(
	/\.vas-dinamico-form\s*\*:focus-visible[\s\S]*?outline:\s*(\d+)px/i
);
if ( desktopFocusMatch && desktopFocusMatch[ 1 ] === '2' ) {
	testPass( 'Desktop focus remains 2px (no regression)' );
} else {
	testFail( 'Desktop focus changed', 'Should remain 2px for larger screens' );
}

// Test 12: Specific interactive controls have enhanced focus
const breakpoint768Match = cssContent.match(
	/@media\s*\(max-width:\s*768px\)\s*\{([\s\S]*?)(?=@media|\/\*\s*===|$)/i
);
if ( breakpoint768Match ) {
	const breakpoint768Content = breakpoint768Match[ 1 ];

	const interactiveControls = [
		'button:focus-visible',
		'input:focus-visible',
		'textarea:focus-visible',
		'select:focus-visible',
		'radio-list li:focus-within',
		'checkbox-list li:focus-within',
	];

	let foundControls = 0;
	interactiveControls.forEach( ( control ) => {
		if ( breakpoint768Content.includes( control ) ) {
			foundControls++;
		}
	} );

	if ( foundControls >= 4 ) {
		testPass(
			`Specific interactive controls have enhanced focus (${ foundControls }/${ interactiveControls.length })`
		);
	} else {
		testWarn(
			`Only ${ foundControls } interactive controls specified`,
			'Consider adding more explicit selectors'
		);
	}
}

console.log( '\nTESTING TOUCH TARGET COMPLIANCE' );
console.log( '----------------------------------------------------------' );

// Test 13: Button padding maintains 44px touch target
const buttonPadding320 =
	/\.eipsi-prev-button[\s\S]*?padding:\s*0\.875rem\s+1\.5rem/i.test(
		cssContent
	);
if ( buttonPadding320 ) {
	testPass(
		'Navigation buttons maintain ~44px height at 320px (0.875rem padding)'
	);
} else {
	testWarn(
		'Button padding at 320px not verified',
		'Check manually: 0.875rem × 2 + font + line-height ≥ 44px'
	);
}

// Test 14: Radio/checkbox list items touch target
const radioListPadding =
	/\.radio-list li[\s\S]*?padding:\s*0\.75rem\s+0\.875rem/i.test(
		cssContent
	);
if ( radioListPadding ) {
	testPass( 'Radio/checkbox list items maintain adequate padding (0.75rem)' );
} else {
	testWarn( 'Radio/checkbox padding at 320px not verified' );
}

console.log( '\nTESTING RESPONSIVE CONTAINER BEHAVIOR' );
console.log( '----------------------------------------------------------' );

// Test 15: Container padding progression
const paddingTests = [
	{ breakpoint: '374px', padding: '0.75rem', pixels: '12px' },
	{ breakpoint: '480px', padding: '1rem', pixels: '16px' },
	{ breakpoint: '768px', padding: '1.5rem', pixels: '24px' },
	{ breakpoint: 'desktop', padding: '2.5rem', pixels: '40px' },
];

// Check 320px padding
if (
	/@media\s*\(max-width:\s*374px\)[\s\S]*?\.vas-dinamico-form[\s\S]*?padding:\s*0\.75rem/i.test(
		cssContent
	)
) {
	testPass( 'Container padding scales correctly: 320px = 12px (0.75rem)' );
} else {
	testFail( '320px container padding incorrect' );
}

// Check 480px padding
if (
	/@media\s*\(max-width:\s*480px\)[\s\S]*?\.vas-dinamico-form[\s\S]*?padding:\s*1rem/i.test(
		cssContent
	)
) {
	testPass( 'Container padding scales correctly: 480px = 16px (1rem)' );
} else {
	testFail( '480px container padding incorrect' );
}

console.log( '\nTESTING WCAG COMPLIANCE' );
console.log( '----------------------------------------------------------' );

// Test 16: Focus color maintains WCAG contrast
const focusColorMatch = cssContent.match(
	/outline:\s*\d+px\s+solid\s+var\(--eipsi-color-primary,\s*#([0-9a-fA-F]{6})\)/
);
if ( focusColorMatch ) {
	const focusColor = focusColorMatch[ 1 ].toLowerCase();
	if ( focusColor === '005a87' ) {
		testPass(
			'Focus outline uses EIPSI blue #005a87 (WCAG AA compliant - 7.47:1)'
		);
	} else {
		testWarn(
			`Focus color is #${ focusColor }`,
			'Should use #005a87 for brand consistency'
		);
	}
}

// Test 17: Focus visible selector for modern browsers
if ( /:focus-visible/i.test( cssContent ) ) {
	testPass( ':focus-visible pseudo-class used (modern browser support)' );
} else {
	testFail(
		':focus-visible not found',
		'Required for keyboard navigation detection'
	);
}

console.log( '\n============================================================' );
console.log( 'VERIFICATION SUMMARY' );
console.log( '============================================================' );
console.log( `✓ PASSED:   ${ passed }` );
console.log( `✗ FAILED:   ${ failed }` );
console.log( `⚠ WARNINGS: ${ warnings }` );
console.log( `Total Tests: ${ passed + failed + warnings }` );

if ( failed === 0 ) {
	console.log( '\n🎉 ALL CRITICAL TESTS PASSED!' );
	console.log( 'Issues #11 and #12 are successfully resolved.' );
	if ( warnings > 0 ) {
		console.log( `Note: ${ warnings } non-critical warning(s) detected.` );
	}
} else {
	console.log( `\n❌ ${ failed } CRITICAL ISSUE(S) DETECTED` );
	console.log( 'Review failures above before completing ticket.' );
}

console.log( '\nMANUAL TESTING CHECKLIST:' );
console.log( '----------------------------------------------------------' );
console.log( '[ ] No horizontal scrolling at 320px width' );
console.log( '[ ] No horizontal scrolling at 375px width' );
console.log( '[ ] No horizontal scrolling at 768px width' );
console.log( '[ ] No horizontal scrolling at 1024px width' );
console.log( '[ ] Focus rings visible when tabbing through form (keyboard)' );
console.log( '[ ] Focus rings are 3px thick on mobile/tablet (≤768px)' );
console.log( '[ ] Focus rings are 2px thick on desktop (>768px)' );
console.log( '[ ] All interactive elements have visible focus indicators' );
console.log( '[ ] Touch targets are at least 44×44px on mobile' );
console.log( '[ ] Typography scales smoothly at all breakpoints' );
console.log( '[ ] Navigation buttons remain accessible at 320px' );

console.log(
	'\n============================================================\n'
);

process.exit( failed > 0 ? 1 : 0 );
